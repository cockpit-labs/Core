<?php
/*
 * Core
 * APIFoldersTest.php
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

namespace App\Tests\API;

use App\Entity\Folder;
use App\Entity\QuestionChoice;
use App\Entity\QuestionnairePDFMedia;
use App\Entity\TplFolder;
use App\Entity\UserMedia;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once('_ApiTest.php');

class APIFoldersTest extends ApiTest
{
    private function addImage()
    {
        $imageFile = __DIR__ . '/GrumpyBear.png';
        $this->setViewClient()->setNormalUser();
        $this->preTest();

        $response = $this->doGetRequest(UserMedia::class);

        $uploadedFile = new UploadedFile(
            $imageFile,
            'GrumpyBear.'
        );

        $file     = ['foo' => $uploadedFile];
        $response = $this->doUploadFileRequest(UserMedia::class, $file);
        $this->assertResponseStatusCodeSame(400);

        $file     = ['file' => $uploadedFile];
        $response = $this->doUploadFileRequest(UserMedia::class, $file);
        $this->assertResponseStatusCodeSame(201);

        $this->assertMatchesResourceCollectionJsonSchema(UserMedia::class);
        $this->assertNotEmpty(json_decode($response->getContent()));

        $imageResponse = json_decode($response->getContent(), true);
        $imageId       = $imageResponse['id'];
        return $imageId;

    }

    /**
     * @group Default
     */
    public function testCreateFolder()
    {
        // get TplFolder id
        $this->setNormalUser()->setViewClient();
        $TplFolderId = $this->getAnId(TplFolder::class);
        $this->assertTrue(Uuid::isValid($TplFolderId), "This is not an valid UUID");
        $TplFolderIri = $this->findIriBy(TplFolder::class, ['id' => $TplFolderId]);

        // get a target
        $response = $this->doDirectRequest("GET", "/api/targets");
        $targets  = $response->getContent();
        $targets  = json_decode($targets, true);
        $targetId = $targets[0]['id'];
        $data     = [
            'tplFolder' => $TplFolderIri,
            'target'    => $targetId
        ];
        $response = $this->doPostRequest(Folder::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains($data);
        $data = json_decode($response->getContent(), true);


        // test modification
        foreach ($data['questionnaires'] as &$questionnaire) {
            foreach ($questionnaire['blocks'] as &$block) {
                foreach ($block['questionAnswers'] as &$answer) {
                    // set first choice as answer
                    if (isset($answer['question']['choices'][0])) {
                        $choice            = $answer['question']['choices'][0];
                        $answer['choices'] = [$this->getIri(QuestionChoice::class, $choice['id'])];
                        // add a photo
                        for ($i = 0; $i <= random_int(0, 5); $i++) {
                            $answer['photos'][$i] = $this->addImage();
                        }
                    } else {
                        $choice = "";
                    }
                }
            }
        }
        $response = $this->doPatchRequest(Folder::class, $data['id'], $data);
        $this->assertResponseStatusCodeSame(200);

        // try to update with another user
        $this->setAdminClient()->setAdminUser();
        $response = $this->doPatchRequest(Folder::class, $data['id'], $data);
        $this->assertResponseStatusCodeSame(403);

        $this->setNormalUser()->setViewClient();
        $response = $this->doPatchWithActionRequest(Folder::class, $data['id'], $data, 'submit');
        $this->assertResponseStatusCodeSame(200);

        // try to patch an SUBMITTED Folder
        $response = $this->doPatchRequest(Folder::class, $data['id'], $data);
        $this->assertResponseStatusCodeSame(403);

    }

    /**
     * @group Default
     */
    public function testFolders()
    {
        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest(Folder::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Folder::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
            foreach ($result['questionnaires'] as $questionnaire) {
                // load pdf
                $pdfId = $this->getIdFromIri($questionnaire['pdf']);
                $res=$this->doGetSubresourceRequest(QuestionnairePDFMedia::class, $pdfId, 'content');
            }

        }
    }
}
