<?php
/*
 * Core
 * FolderDataTransformer.php
 *
 * Copyright (c) 2020 Sentinelo
 *
 * @author  Christophe AGNOLA
 * @license MIT License (https://mit-license.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the “Software”), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\DataProvider\CommonDataProvider;
use App\Entity\Folder\Folder;
use App\Entity\Media\Media;
use App\Entity\Media\MediaOwner;
use App\Entity\Media\QuestionnairePDFMedia;
use App\Entity\Media\UserMedia;
use App\Entity\User;
use App\Service\CCETools;
use App\Traits\stateableEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use TheCodingMachine\Gotenberg\Client;
use TheCodingMachine\Gotenberg\DocumentFactory;
use TheCodingMachine\Gotenberg\HTMLRequest;
use TheCodingMachine\Gotenberg\Request;
use Twig\TwigFunction;

final class FolderDataTransformer extends CommonDataProvider implements DataTransformerInterface
{

    private $context;

    /**
     * @param \App\Entity\Media\Media $media
     * @param array                   $owners
     */
    private function addMediaOwner(?Media $media, array $owners)
    {
        if (empty($media)) {
            return;
        }
        $repo = $this->getEntityManager()->getRepository(MediaOwner::class);
        foreach ($owners as $owner) {
            $mediaOwner = new MediaOwner();
            $mediaOwner->setMedia($media);
            $mediaOwner->setOwner($owner);
            if (!$repo->exists($mediaOwner)) {
                $this->getEntityManager()->persist($mediaOwner);
            }
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @param $folder
     */
    private function checkGrants($folder): void
    {
        if ($folder->getCreatedBy() !== null
            && $folder->getCreatedBy() !== $this->getUser()->getUsername()) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @param \App\Entity\Folder\Folder $data
     *
     * @return \App\Entity\Folder\Folder
     * @throws \Exception
     */
    private function createFolder(Folder $data): Folder
    {
        $target = $this->getKeycloakConnector()->getGroup($data->getTarget());
        // get target parent
        $parentTargets   = array_map(function ($val) {
            return sprintf("%s", $val['id']);
        }, $this->getKeycloakConnector()->getParentGroups($target));
        $parentTargets[] = $target['id'];

        $folderTpl = $data->getFolderTpl();
        $this->getEntityManager()->initializeObject($folderTpl);

        $folder = $folderTpl->instantiate();
        $folder->setCreatedBy($this->getUser()->getUsername())
               ->setUpdatedBy($this->getUser()->getUsername())
               ->setTarget($data->getTarget())
               ->setParentTargets(implode('/', $parentTargets));

        return $folder;
    }

    /**
     * @param \App\Entity\Folder\Folder $folder
     *
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @throws \TheCodingMachine\Gotenberg\ClientException
     * @throws \TheCodingMachine\Gotenberg\RequestException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function generatePDF(Folder $folder)
    {

        $selectedChoices = new TwigFunction('getSelectedChoices', function ($question) {
            $selectedChoicesLabels = [];
            if (!empty($question['answers'])) {
                $selectedChoicesLabels = array_map(function ($answer) {
                    return $answer['choice']['label'] ?? '';
                }, $question['answers']);
            }

            return $selectedChoicesLabels;
        });

        $this->getTwig()->addFunction($selectedChoices);

        $photoLibs = [];
        $photoIri  = str_replace('/', '\/', $this->getIriService()->getIriFromResourceClass(UserMedia::class) . '/');
        foreach ($folder->getQuestionnaires() as $questionnaire) {
            // check all photos in answers
            foreach ($questionnaire->getBlocks() as $block) {
                foreach ($block->getQuestions() as $question) {
                    foreach ($question->getAnswers() as $answer) {
                        if (!empty($answer->getMedia())) {
                            $photoLibs[$photoIri . $answer->getMedia()->getId()] =
                                base64_encode($this->getMediaFS()->read($answer->getMedia()->getFileName()));
                        }
                    }
                    foreach ($question->getPhotos() as $photo) {
                        $photoLibs[$photoIri . $photo->getId()] =
                            base64_encode($this->getMediaFS()->read($photo->getFileName()));
                    }
                }
            }
            // generate PDF
            $this->context['groups'][] = "Label";
            $this->context['groups'][] = "Description";
            $this->context['groups'][] = "Folder:Read";
            $this->context['groups'][] = "Timestamp";
            $this->context['groups'][] = "Blame";
            $this->context['skip_null_values'] = false;

            $data = $this->getNormalizer()->normalize($questionnaire, null, $this->context);

            // replace photo IRI with base64 image
            $datajson = json_encode($data);
            foreach ($photoLibs as $iri => $base64) {
                $datajson = str_replace($iri, $base64, $datajson);
            }
            $data = json_decode($datajson, true);

            $gotenbergURL = CCETools::param($this->getParameters(), 'CCE_GOTENBERGURL', 'http://localhost:3000');
            $client       = new Client($gotenbergURL, new \Http\Adapter\Guzzle6\Client());
            $kcuser       = $this->getKeycloakConnector()->getUser($this->getUserId());
            $user         = new User();
            $user->populateUser($kcuser);
            $user = $this->getNormalizer()->normalize($user);

            // render data with twig

            $htmlData = $this->getTwig()->render('questionnaire.html.twig',
                                                 [
                                                     'questionnaire' => $data,
                                                     'user'          => $user,
                                                     'locale'        => 'en'
                                                 ]);

            $html    = DocumentFactory::makeFromString('qpdf.html', $htmlData);
            $request = new HTMLRequest($html);
            $request->setMargins(Request::NO_MARGINS);

            $pdfTempFile = $this->getKernel()->getLocalTmpDir() . '/QPDF-' . $questionnaire->getId() . '.pdf';
            $client->store($request, $pdfTempFile);

            // create an 'uploaded' PDF
            $pdfFile = new UploadedFile($pdfTempFile, 'Q' . $questionnaire->getId() . '.pdf', 'application/pdf',
                                        filesize($pdfTempFile), true);

            // prepare new PDF
            $pdf = new QuestionnairePDFMedia();
            $pdf->setFile($pdfFile);

            $oldPdf = $questionnaire->getPdf();

            // set new PDF document, and remove old one
            $questionnaire->setPdf($pdf);
            if ($oldPdf && !empty($this->getEntityManager()->find(Media::class, $oldPdf->getId()))) {
                $this->getEntityManager()->remove($oldPdf);
            }
            $this->getEntityManager()->persist($pdf);
            $this->getEntityManager()->flush();

            // force old PDF Document removal (hardDelete) by removing a second time
            if ($oldPdf && !empty($this->getEntityManager()->find(Media::class, $oldPdf->getId()))) {
                $this->getEntityManager()->remove($oldPdf);
                $this->getEntityManager()->persist($pdf);
                $this->getEntityManager()->flush();
            }
            unlink($pdfTempFile);
        }
    }

    /**
     * @param $folder
     *
     * @return mixed
     */
    private function owningPhotosUpdate(Folder $folder)
    {
        // create or update photo owning information
        foreach ($folder->getQuestionnaires() as $questionnaire) {
            foreach ($questionnaire->getBlocks() as $block) {
                foreach ($block->getQuestions() as $question) {
                    $owners = [
                        $folder->getTarget(),
                        $question->getId(),
                        $block->getId(),
                        $folder->getId(),
                        $folder->getFolderTpl()->getId()
                    ];
                    foreach ($question->getAnswers() as $answer) {
                        if (!empty($answer->getMedia())) {
                            $this->addMediaOwner($answer->getMedia(), $owners);
                            $answer->getMedia()->setTarget($folder->getTarget());
                            $answer->getMedia()->setFolder($folder);
                        }
                    }
                    foreach ($question->getPhotos() as $photo) {
                        $this->addMediaOwner($photo, $owners);
                        if (!empty($folder->getTarget())) {
                            $photo->setTarget($folder->getTarget());
                            $photo->setFolder($folder);
                        }
                    }
                }
            }
        }
        return $folder;
    }

    /**
     * @param $folder
     */
    private function submit(Folder &$folder)
    {
        // force group
        $this->context['groups'][] = "State";
        $folder->setState(stateableEntity::getStateSubmitted());
        foreach ($folder->getQuestionnaires() as $questionnaire) {
            foreach ($questionnaire->getTasks() as &$task) {
                $task->setState(stateableEntity::getStateSubmitted());
            }
        }
    }

    /**
     * @param array|object $data
     * @param string       $to
     * @param array        $context
     *
     * @return bool
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        // transform only Folder creation and update
        return (
            Folder::class === $to
            && ($context['input']['class'] ?? null) !== null
            && in_array($context[$context['operation_type'] . '_operation_name'], ['create', 'update', 'submit'])
        );
    }

    /**
     * @param object $data
     * @param string $to
     * @param array  $context
     *
     * @return \App\Entity\Folder\Folder|object
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @throws \TheCodingMachine\Gotenberg\ClientException
     * @throws \TheCodingMachine\Gotenberg\RequestException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function transform($data, string $to, array $context = [])
    {
        $folder        = $data;
        $this->context = $context;

        $this->checkGrants($folder);

        switch ($context[$context['operation_type'] . '_operation_name']) {
            case 'create':
                // it's a folder creation
                $this->context['groups'][] = "Label";
                $this->context['groups'][] = "Description";
                $folder                    = $this->createFolder($folder);
                break;

            case 'update':
                // it's folder update
                // a folder can be updated if not in SUBMITTED state
                if ($folder->getState() !== stateableEntity::getStateDraft()) {
                    throw new AccessDeniedHttpException();
                }
                break;

            case 'submit':
                $folder->processScore();
                $this->owningPhotosUpdate($folder);
                $this->submit($folder);
                $this->generatePDF($folder);
                break;

            default:
                // what?
                // an unknown operation?
                break;
        }
        return $folder;
    }
}
