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
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
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
use App\DataProvider\KeycloakDataProvider;
use App\Entity\AnswerValue;
use App\Entity\Block;
use App\Entity\Folder;
use App\Entity\MediaOwner;
use App\Entity\QuestionAnswer;
use App\Entity\Questionnaire;
use App\Entity\TplBlock;
use App\Entity\TplQuestion;
use App\Entity\TplQuestionnaire;
use App\Traits\stateableEntity;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class FolderDataTransformer extends KeycloakDataProvider implements DataTransformerInterface
{

    private $context;
    private $expressionEngine;

    private function checkGrants($folder): void
    {
        if ($folder->getCreatedBy() !== null
            && $folder->getCreatedBy() !== $this->getUser()->getUsername()) {
            throw new AccessDeniedHttpException();
        }
    }

    private function createAnswer(TplQuestion $question): QuestionAnswer
    {

        $answer = new QuestionAnswer();
        $answer->setWeight($question->getWeight());
        $q = $this->getNormalizer()->normalize($question, null, $this->context);
        $answer->setQuestion($q);
        foreach ($question->getChildren() as $child) {
            $childrenAnswer = $this->createAnswer($child);
            $childrenAnswer->setParent($answer);
            $answer->addChild($childrenAnswer);
        }

        return $answer;
    }

    private function createBlock(TplBlock $tplBlock): Block
    {
        $block = new Block();
        $block->setDescription($tplBlock->getDescription());
        $block->setLabel($tplBlock->getLabel());
        foreach ($tplBlock->getTplQuestions() as $question) {
            $this->getEntityManager()->initializeObject($question);
            $answer = $this->createAnswer($question);
            $block->addQuestionAnswer($answer);
        }
        return $block;
    }

    private function createFolder(Folder &$folder): Folder
    {

        $tplFolder = $folder->getTplFolder();
        $this->getEntityManager()->initializeObject($tplFolder);

        if (!empty($tplFolder->getDescription())) {
            $folder->setDescription($tplFolder->getDescription());
        }

        // get min and max dates from calendars
        foreach ($tplFolder->getCalendars() as $calendar) {
            $this->getEntityManager()->initializeObject($calendar);
            $folder->setPeriodEnd(max($calendar->getPeriodEnd(), $folder->getPeriodEnd()));
            // start cannot be null, so force it to first end value, if it is null
            $folder->setPeriodStart($folder->getPeriodStart() ?? $folder->getPeriodEnd());
            $folder->setPeriodStart(min($calendar->getPeriodStart(), $folder->getPeriodStart()));
        }

        if (!empty($tplFolder->getLabel())) {
            $folder->setLabel($tplFolder->getLabel());
        }
        $folder->setCreatedBy($this->getUser()->getUsername());
        $folder->setUpdatedBy($this->getUser()->getUsername());
        $tplQuestionnaires = $tplFolder->getTplQuestionnaires();
        // instanciate questionnaires for this new folder
        foreach ($tplQuestionnaires as $questionnaireTmpl) {
            // instanciate the questionnaire and add it to the new folder
            $this->getEntityManager()->initializeObject($questionnaireTmpl);
            $questionnaire = $this->createQuestionnaire($questionnaireTmpl);
            $folder->addQuestionnaire($questionnaire);
        }
        return $folder;
    }

    private function createQuestionnaire(TplQuestionnaire $tplQuestionnaire): Questionnaire
    {
        $questionnaire = new Questionnaire();
        if (!empty($tplQuestionnaire->getDescription())) {
            $questionnaire->setDescription($tplQuestionnaire->getDescription());
        }
        if (!empty($tplQuestionnaire->getLabel())) {
            $questionnaire->setLabel($tplQuestionnaire->getLabel());
        }
        $questionnaire->setTplQuestionnaire($tplQuestionnaire);

        $tplBlocks = $tplQuestionnaire->getTplQuestionnaireBlocks();
        foreach ($tplBlocks as $tplBlockAssoc) {
            $this->getEntityManager()->initializeObject($tplBlockAssoc);
            $tplBlock   = $tplBlockAssoc->getTplBlock();
            $tplBlockId = $tplBlock->getId();
            $tplBlock   = $this->getEntityManager()->find(TplBlock::class, $tplBlockId);
            $block      = $this->createBlock($tplBlock);
            $block->setQuestionnaire($questionnaire);
            $block->setPosition($tplBlockAssoc->getPosition());
            $block->setTplBlockId($tplBlockId);
            $questionnaire->addBlock($block);
        }
        return $questionnaire;
    }

    /**
     * Process scores for folder and sub entities
     *
     * @param \App\Entity\Folder $folder
     *
     * @return \App\Entity\Folder
     */
    private function processScore(Folder &$folder): Folder
    {
        $this->expressionEngine = new ExpressionLanguage();

        foreach ($folder->getQuestionnaires() as &$questionnaire) {
            foreach ($questionnaire->getBlocks() as &$block) {
                foreach ($block->getQuestionAnswers() as &$answer) {
                    foreach ($answer->getAnswerValues() as &$answerValue) {
                        // calculate value with rawvalue and valueformula
                        $this->processValueFormula($answerValue);
                        if ($answer->getWeight() > 0) {
                            $score  = $answer->getScore() ?? 0;
                            $weight = $answer->getWeight() ?? 0;
                            $points = (double)$answerValue->getValue() ?? 0;
                            $score  = $score + ($points * $weight);
                        } else {
                            $answer->setScoreDivider(0);
                            $score = null;
                        }
                        $answer->setScore($score);
                    }
                    $block->setScoreDivider($block->getScoreDivider() + $answer->getScoreDivider());
                    $block->setScore($block->getScore() + $answer->getScore());
                }
                $questionnaire->setScoreDivider($questionnaire->getScoreDivider() + $block->getScoreDivider());
                $questionnaire->setScore($questionnaire->getScore() + $block->getScore());
            }
            $folder->setScoreDivider($folder->getScoreDivider() + $questionnaire->getScoreDivider());
            $folder->setScore($folder->getScore() + $questionnaire->getScore());
        }
        return $folder;
    }

    private function processValueFormula(AnswerValue &$answerValue)
    {
        if (empty($answerValue->getChoice())) {
            return;
        }
        $expression = $answerValue->setValueFormula($answerValue->getChoice()->getValueFormula())->getValueFormula();
        $expression = $expression['expression'] ?? '$value';

        //do some replacement
        $rawValue = $answerValue->getRawValue();
        if (is_numeric($rawValue)) {
            $rawValue = floatval($rawValue);
        } else {
            $rawValue = '"' . $rawValue . '"';
        }

        $position   = $answerValue->getChoice()->getPosition();
        $expression = preg_replace('/\$\brank\b/i', $position, $expression);

        $expression = preg_replace('/\$\bvalue\b/i', $rawValue, $expression);

        $result = $this->expressionEngine->evaluate($expression);

        $answerValue->setValue($result);

    }

    private function updateFolder(&$folder)
    {
        $this->expressionEngine = new ExpressionLanguage();
        $em                     = $this->getEntityManager();
        foreach ($folder->getQuestionnaires() as &$questionnaire) {
            foreach ($questionnaire->getBlocks() as &$block) {
                foreach ($block->getQuestionAnswers() as &$answer) {
                    foreach ($answer->getAnswerValues() as &$answerValue) {
                        if (!empty($answerValue->getChoice())) {
                            $formula = $answerValue->getChoice()->getValueFormula() ?? [];
                            $answerValue->setValueFormula($formula);
                        }
                    }
                    foreach ($answer->getPhotos() as &$photo) {
                        $owners = [
                            $folder->getTarget(),
                            $answer->getId(),
                            $block->getId(),
                            $folder->getId(),
                            $folder->getTplFolder()->getId()
                        ];
                        foreach ($owners as $owner) {
                            $mediaOwner = new MediaOwner();
                            $mediaOwner->setMedia($photo);
                            $mediaOwner->setOwner($owner);
                            $repo = $em->getRepository(MediaOwner::class);
                            if (!$repo->exists($mediaOwner)) {
                                $em->persist($mediaOwner);
                            }
                        }
                    }
                }
            }
        }
        return $folder;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
                // let's process folder and questionnaire score
                // a folder can be updated if not in SUBMITTED state
                if ($folder->getState() === stateableEntity::getStateDraft()) {
                    $this->context['groups'][] = "Label";
                    $this->context['groups'][] = "Description";
                    $this->context['groups'][] = "Score";
                    $folder                    = $this->processScore($folder);
                    $folder                    = $this->updateFolder($folder);
                } else {
                    throw new AccessDeniedHttpException();
                }
                break;
            case 'submit':
                // force group
                $this->context['groups'][] = "State";
                $folder->setState(stateableEntity::getStateSubmitted());
                break;
            default:
                // what?
                // an unknown operation?
                break;
        }
        return $folder;
    }
}
