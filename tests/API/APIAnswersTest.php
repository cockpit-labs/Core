<?php
/*
 * Core
 * APIAnswersTest.php
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

use App\Entity\Calendar;
use App\Entity\Folder\Folder;
use App\Entity\Media\UserMedia;
use App\Entity\Folder\FolderTpl;
use CronExpressionGenerator\FakerProvider;
use Faker\Factory;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;


require_once('_ApiTest.php');

class APIAnswersTest extends ApiTest
{
    public function createFolder(): string
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
        $response=$response->getContent();
        $this->assertJsonContains($data);
        $data = json_decode($response, true);
        return $data['id'];

    }

    /**
     * @group Default
     */
    public function testAnswers()
    {
        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest(Calendar::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
        }
    }

    /**
     * @group Default
     */
    public function testCreateAnswers()
    {
        $faker = Factory::create();
        $faker->addProvider(new FakerProvider($faker));

        $folderId = $this->createFolder();
        $response = $this->doGetRequest(Folder::class, $folderId);
        $data     = json_decode($response->getContent());

        foreach ($data->questionnaires as &$questionnaire) {
            foreach ($questionnaire->blocks as &$block) {
                foreach ($block->questions as &$question) {
                    $question->comment = "Answer comments is that => " . $faker->sentence(40);
                }
                for ($i = 0; $i <= random_int(0, 5); $i++) {
                    $question->photos[] = $this->getIri(UserMedia::class, $this->addImage(UserMedia::class, true));
                }
            }
        }
        $data     = json_encode($data);
        $response = $this->doPatchRequest(Folder::class, $folderId, json_decode($data), true);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Folder::class);

    }
}
