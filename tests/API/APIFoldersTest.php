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

namespace App\Tests\API;

use App\Entity\Choice\Choice;
use App\Entity\Folder\Folder;
use App\Entity\Folder\FolderTpl;
use App\Entity\Media\QuestionnairePDFMedia;
use App\Entity\Media\UserMedia;
use Ramsey\Uuid\Uuid;

require_once('_ApiTest.php');

class APIFoldersTest extends ApiTest
{
    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testCreateFolder()
    {
        // get Folder id
        $this->setNormalUser()->setViewClient();
        $TplFolderId = $this->getAnId(FolderTpl::class);
        $this->assertTrue(Uuid::isValid($TplFolderId), "This is not an valid UUID");
        $TplFolderIri = $this->findIriBy(FolderTpl::class, ['id' => $TplFolderId]);

        // get a target
        $response = $this->doDirectRequest("GET", "/api/targets");
        $targets  = $response->getContent();
        $targets  = json_decode($targets, true);
        $targetId = $targets[0]['id'];
        $data     = [
            'folderTpl' => $TplFolderIri,
            'target'    => $targetId
        ];
        $response = $this->doPostRequest(Folder::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains($data);
        $data = json_decode($response->getContent(), true);


        // test modification
        foreach ($data['questionnaires'] as &$questionnaire) {
            foreach ($questionnaire['blocks'] as &$block) {
                foreach ($block['questions'] as &$question) {
                    // set first choice as answer
                    if (isset($question['choices'][0])) {
                        $choice              = $question['choices'][0];
                        $question['choices'] = [$this->getIri(Choice::class, $choice['id'])];
                        // add a photo
                        for ($i = 0; $i <= random_int(0, 5); $i++) {
                            $question['photos'][$i] = $this->addImage(UserMedia::class, true);
                        }
                    }
                }
            }
            // add tasks
            for ($t = 1; $t <= rand(1, 10); $t++) {
                $task          = [];
                $countInformed = rand(1, 10);
                for ($inf = 1; $inf <= $countInformed; $inf++) {
                    $users              = ['audie', 'rosendo', 'heidi', 'marguerite', 'oh'];
                    $task['informed'][] = $this->getAnUser($users[array_rand($users)]);
                }
                $task['responsible'] = $this->getAnUser('ay');
                $task['action']      = $this->getFaker()->sentence(20);
                $tasks[]             = $task;
            }
            $questionnaire['tasks'] = $tasks;
        }
        $this->doPatchRequest(Folder::class, $data['id'], $data);
        $this->assertResponseStatusCodeSame(200);

        // patch ith a bad user in tasks
        $goodUser=$data['questionnaires'][0]['tasks'][0]['responsible']['username'];
        $data['questionnaires'][0]['tasks'][0]['responsible']['username']='user.do.not.exists';
        $this->doPatchRequest(Folder::class, $data['id'], $data);
        $this->assertResponseStatusCodeSame(422);
        $data['questionnaires'][0]['tasks'][0]['responsible']['username']=$goodUser;

        // try to update with another user
        $this->setAdminClient()->setAdminUser();
        $this->doPatchRequest(Folder::class, $data['id'], $data);
        $this->assertResponseStatusCodeSame(403);

        $this->setNormalUser()->setViewClient();
        $this->doPatchWithActionRequest(Folder::class, $data['id'], $data, 'submit');
        $this->assertResponseStatusCodeSame(200);

        // try to patch an SUBMITTED Folder
        $this->doPatchRequest(Folder::class, $data['id'], $data);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
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
                $this->doGetSubresourceRequest(QuestionnairePDFMedia::class, $pdfId, 'content');
            }
        }
    }
}
