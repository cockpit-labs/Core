<?php
/*
 * Core
 * GetQuestionnairePDFContentAction.php
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

namespace App\Controller;

use App\Entity\Questionnaire;
use App\Entity\QuestionnairePDFMedia;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GetQuestionnairePDFContentAction extends GetMediaContentAction
{
    /**
     * GetTplMediaContentAction constructor.
     *
     * @param \League\Flysystem\FilesystemInterface $mediafsFilesystem
     */
    public function __construct(FilesystemInterface $mediafsFilesystem)
    {

        $this->setMediaClass(QuestionnairePDFMedia::class);
        $this->setDownload(false);
        parent::__construct($mediafsFilesystem);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function __invoke(Request $request): Response
    {
        // it's a Questionnaire id
        // we need to find the QuestionnairePdfMedia id

        $id = $request->get('id');
        $this->setDownload($request->get('download', 'false') != 'false');
        $questionnaire=$this->getDoctrine()->getRepository(Questionnaire::class)->find($id);
        if (!$questionnaire) {
            throw $this->createNotFoundException(
                'No questionnaire found for id ' . $id
            );
        }

        $this->setId($questionnaire->getPdf()->getId());

        return parent::__invoke($request);
    }
}