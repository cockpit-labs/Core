<?php
/*
 * Core
 * APITplQuestionsTest.php
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

use App\Entity\Category;
use App\Entity\Media\MediaTpl;
use App\Entity\Question\QuestionTpl;
use Faker\Factory;
use JSONSchemaFaker\Faker;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once('_ApiTest.php');

class APITplQuestionsTest extends ApiTest
{
    private $categoryList = [];

    public function createCategory()
    {
        $faker = Factory::create();
        $data  = [
            "label"       => "test Category " . $faker->city,
            "description" => "desc = " . $faker->domainName
        ];
        $this->setAdminClient()->setAdminUser();
        $response = $this->doPostRequest(Category::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(Category::class);
        $this->assertJsonContains($data);
        $data                 = json_decode($response->getContent(), true);
        $this->categoryList[] = $data['id'];
        return $data['id'];
    }

    public function tearDown(): void
    {
        foreach ($this->categoryList as $categoryId) {
            $this->doDeleteRequest(Category::class, $categoryId);
            parent::tearDown();
        }
    }

    /**
     * @group Default
     */
    public function testCommon()
    {
        $this->commonTest(QuestionTpl::class);
    }

    public function testCreateQuestions()
    {

        $faker      = Factory::create();
        $jsonSchema = json_decode(file_get_contents(__DIR__ . '/rendererSchema.json'));
        $data       = [
            "category"        => $this->getIri(Category::class, $this->createCategory()),
            "weight"          => $faker->numberBetween(1, 100),
            "position"        => $faker->numberBetween(1, 100),
            "hiddenLabel"     => $faker->boolean(90),
            "mandatory"       => $faker->boolean(50),
            "hasComment"      => $faker->boolean(50),
            "maxPhotos"       => $faker->numberBetween(1, 5),
            "readRenderer"    => (new Faker())->generate($jsonSchema),
            "writeRenderer"   => (new Faker())->generate($jsonSchema),
            "validator"       => (new Faker())->generate($jsonSchema),
            "label"           => $faker->sentence(6),
            "choiceTpls"         => [
                [
                    "label"        => $faker->sentence(6),
                    "valueFormula" => (new Faker())->generate($jsonSchema)
                ],
                [
                    "label"        => $faker->sentence(6),
                    "valueFormula" => (new Faker())->generate($jsonSchema)
                ],
                [
                    "label"        => $faker->sentence(6),
                    "valueFormula" => (new Faker())->generate($jsonSchema)
                ]
            ],
            "trigger" => (new Faker())->generate($jsonSchema)
        ];


        $this->setNormalUser()->setViewClient();
        $this->doPostRequest(QuestionTpl::class, $data);
        $this->assertResponseStatusCodeSame(403);
        $this->setAdminClient()->setAdminUser();

        $response = $this->doPostRequest(QuestionTpl::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(QuestionTpl::class);
        unset($data['readRenderer']);
        unset($data['writeRenderer']);
        unset($data['validator']);
        unset($data['category']);
        unset($data['trigger']);
        unset($data['choiceTpls']);
        $this->assertJsonContains($data);
        $response = json_decode($response->getContent(), true);

        $id       = $response['id'];
        $response = $this->doDeleteRequest(QuestionTpl::class, $id);
        $this->assertResponseStatusCodeSame(204);


    }

    /**
     * @group Default
     */
    public function testQuestions()
    {
        $response = $this->doGetRequest(QuestionTpl::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(QuestionTpl::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
        }

    }
}
