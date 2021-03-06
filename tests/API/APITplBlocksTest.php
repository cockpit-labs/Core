<?php
/*
 * Core
 * APITplBlocksTest.php
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

use App\Entity\Block\BlockTpl;
use App\Entity\Category;
use App\Entity\Media\MediaTpl;
use App\Entity\Question\QuestionTpl;
use Faker\Factory;
use JSONSchemaFaker\Faker;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once('_ApiTest.php');

class APITplBlocksTest extends ApiTest
{

    private $questionList = [];


    /**
     * @return mixed
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function createQuestion()
    {

        $faker      = Factory::create();
        $jsonSchema = json_decode(file_get_contents(__DIR__ . '/rendererSchema.json'));
        $data       = [
            "weight"        => $faker->numberBetween(1, 100),
            "position"      => $faker->numberBetween(1, 100),
            "hiddenLabel"   => $faker->boolean(90),
            "mandatory"     => $faker->boolean(50),
            "hasComment"    => $faker->boolean(50),
            "maxPhotos"     => $faker->numberBetween(1, 5),
            "readRenderer"  => (new Faker())->generate($jsonSchema),
            "writeRenderer" => (new Faker())->generate($jsonSchema),
            "validator"     => (new Faker())->generate($jsonSchema),
            "label"         => $faker->sentence(6),
            "choiceTpls"    => [
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
            "trigger"       => (new Faker())->generate($jsonSchema)
        ];


        $this->setViewClient()->setNormalUser();
        $this->doPostRequest(QuestionTpl::class, $data);
        $this->assertResponseStatusCodeSame(403);

        $this->setAdminClient()->setAdminUser();
        $response = $this->doPostRequest(QuestionTpl::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(QuestionTpl::class);
        unset($data['readRenderer']);
        unset($data['writeRenderer']);
        unset($data['validator']);
        unset($data['trigger']);
        unset($data['choices']);
        $data     = json_decode(json_encode($data), true);
        $response = json_decode($response->getContent(), true);
        $this->assertArraySubset($data, $response);

        $id                   = $response['id'];
        $this->questionList[] = $id;
        return $id;
    }

    /**
     *
     */
    public function tearDown(): void
    {
        foreach ($this->questionList as $questionId) {
            $this->doDeleteRequest(QuestionTpl::class, $questionId);
        }
        parent::tearDown();
    }

    /**
     * @group Default
     */
    public function testCommon()
    {
        $this->commonTest(BlockTpl::class);
    }

    /**
     * @return String
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testCreateTplBlocks()
    {

        $faker      = Factory::create();
        $jsonSchema = json_decode(file_get_contents(__DIR__ . '/rendererSchema.json'));

        $data = [
            "label"        => $faker->sentence(6),
            "description"  => $faker->sentence(15),
            "tplQuestions" => [
                [
                    "weight"        => $faker->numberBetween(1, 100),
                    "readRenderer"  => (new Faker())->generate($jsonSchema),
                    "writeRenderer" => (new Faker())->generate($jsonSchema),
                    "validator"     => (new Faker())->generate($jsonSchema),
                    "position"      => $faker->numberBetween(1, 100),
                    "hiddenLabel"   => $faker->boolean(90),
                    "mandatory"     => $faker->boolean(50),
                    "hasComment"    => $faker->boolean(50),
                    "maxPhotos"     => $faker->numberBetween(1, 5),
                    "label"         => $faker->sentence(6),
                    "choiceTpls"    => [
                        [
                            "label"        => $faker->sentence(6),
                            "valueFormula" => (new Faker())->generate($jsonSchema)
                        ]
                    ],
                    "trigger"       => (new Faker())->generate($jsonSchema)
                ]
            ]
        ];

        $this->setAdminClient()->setAdminUser();
        $response = $this->doPostRequest(BlockTpl::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(BlockTpl::class);
        $this->assertJsonContains(['label' => $data['label']]);
        $response = json_decode($response->getContent(), true);
        return $this->getIri(BlockTpl::class, $response['id']);
    }

    /**
     * @group Default
     */
    public function testGetTplBlocks()
    {
        $response = $this->doGetRequest(BlockTpl::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(BlockTpl::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
        }
    }

    /**
     * @group Default
     */
    public function testUpdateTplBlocks()
    {
        $label       = "modified label";
        $description = "modified description";
        $data        = [
            "label"       => $label,
            "description" => $description,
        ];

        $this->setAdminClient()->setAdminUser();
        $this->doGetRequest(BlockTpl::class);
        $id       = $this->getAnId(BlockTpl::class);
        $this->doPatchRequest(BlockTpl::class, $id, $data);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(BlockTpl::class);
        $this->assertJsonContains($data);

        $this->doGetRequest(BlockTpl::class);

        $category = static::findIriBy(Category::class, ['id' => $this->getAnId(Category::class)]);
        $q1       = $this->getIri(QuestionTpl::class, $this->createQuestion());
        $q2       = $this->getIri(QuestionTpl::class, $this->createQuestion());

        $jsonSchema = json_decode(file_get_contents(__DIR__ . '/rendererSchema.json'));

        $data     = [
            "label"        => $label,
            "description"  => $description,
            "tplQuestions" => [
                [
                    "weight"        => 0,
                    "readRenderer"  => (new Faker())->generate($jsonSchema),
                    "writeRenderer" => (new Faker())->generate($jsonSchema),
                    "position"      => 1,
                    "hiddenLabel"   => false,
                    "mandatory"     => true,
                    "hasComment"    => false,
                    "maxPhotos"     => 0,
                    "category"      => $category,
                    "label"         => "TplQuestion number SEVEN",
                    "choiceTpls"    => [
                        [
                            "label"        => "Choice ONE for TplQuestion SEVEN",
                            "valueFormula" => (new Faker())->generate($jsonSchema)
                        ]
                    ],
                    "children"      => [$q1, $q2],
                    "trigger"       => (new Faker())->generate($jsonSchema)
                ]
            ]
        ];
        $this->doPatchRequest(
            BlockTpl::class,
            $this->getAnId(BlockTpl::class),
            $data
        );
        $this->assertResponseStatusCodeSame(200);
        unset($data['tplQuestions']);
        $this->assertJsonContains($data);

        $data     = [
            "label"        => $label,
            "description"  => $description,
            "tplQuestions" =>
                [
                    $this->getIri(QuestionTpl::class, $this->createQuestion()),
                    $this->getIri(QuestionTpl::class, $this->createQuestion()),
                    $this->getIri(QuestionTpl::class, $this->createQuestion()),
                    $this->getIri(QuestionTpl::class, $this->createQuestion()),
                ]
        ];
        $this->doPatchRequest(
            BlockTpl::class,
            $this->getAnId(BlockTpl::class),
            $data
        );
        $this->assertResponseStatusCodeSame(200);
        $data     = [
            "label"        => $label,
            "description"  => $description,
            "tplQuestions" => []
        ];
        $this->doPatchRequest(
            BlockTpl::class,
            $this->getAnId(BlockTpl::class),
            $data
        );
        $this->assertResponseStatusCodeSame(200);
    }
}
