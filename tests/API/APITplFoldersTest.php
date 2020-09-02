<?php
/*
 * Core
 * APITplFoldersTest.php
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

use App\Entity\Calendar;
use App\Entity\TplFolderPermission;
use App\Entity\TplFolder;
use CronExpressionGenerator\FakerProvider;
use Faker\Factory;

require_once('_ApiTest.php');

class APITplFoldersTest extends ApiTest
{
    private $calendarList  = [];
    private $tplFolderList = [];

    public function createCalendar()
    {
        $faker = Factory::create();
        $faker->addProvider(new FakerProvider($faker));

        $data = [
            "label"     => "test Calendar " . $faker->colorName,
            "start"     => $faker->dateTime()->modify('midnight')->format('c'),
            "end"       => $faker->dateTimeBetween("+1 years", "+2 years")->format('c'),
            "cronStart" => '7 15 * * 1',
            "cronEnd"   => '0 8 * * 4',
        ];

        $this->setAdminClient()->setAdminUser();

        $response = $this->doPostRequest(Calendar::class, $data);
        unset($data['end']);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertJsonContains($data);
        $data                 = json_decode($response->getContent(), true);
        $this->calendarList[] = $data['id'];
        return $data['id'];
    }

    public function tearDown(): void
    {
        foreach ($this->calendarList as $id) {
            $this->doDeleteRequest(Calendar::class, $id);
        }
        foreach ($this->tplFolderList as $id) {
            $this->doDeleteRequest(TplFolder::class, $id);
        }
        parent::tearDown();
    }

    /**
     * @group Default
     */
    public function testCommon()
    {
        $this->commonTest(TplFolder::class);
    }

    /**
     * @group Default
     */
    public function testCreateTplFolders()
    {

        $faker = Factory::create();
        $data  = [
            "label"         => "La belle de Cadix",
            "description"   => "The description is here",
            "folderTargets" => [
                ["role" => "CCEUser"],
                ["role" => "BiduleTruc"],
            ],
            "calendars"     =>
                [
                    $this->getIri(Calendar::class, $this->createCalendar()),
                    $this->getIri(Calendar::class, $this->createCalendar()),
                    $this->getIri(Calendar::class, $this->createCalendar()),
                    $this->getIri(Calendar::class, $this->createCalendar()),
                    $this->createCalendar()
                ]
        ];

        $this->setAdminClient()->setAdminUser();
        $response = $this->doPostRequest(TplFolder::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(TplFolder::class);
        unset($data['calendars']);
        $this->assertJsonContains($data);
        $data                  = json_decode($response->getContent(), true);
        $this->tplFolderList[] = $data['id'];

    }

    public function testTplFolders()
    {
        $response = $this->doGetRequest(TplFolder::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(TplFolder::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
        }
    }

    public function testUpdateTplFolders()
    {
        $label       = "Calendar label";
        $description = "modified description";
        $faker       = Factory::create();
        $data        = [
            "calendars" =>
                [
                    $this->getIri(Calendar::class, $this->createCalendar()),
                ]
        ];

        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest(TplFolder::class);
        $id       = $this->getAnId(TplFolder::class);
        $response = $this->doPatchRequest(TplFolder::class, $id, $data);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(TplFolder::class);
        $this->assertJsonContains($data);

        $data     = [
            "label"       => $label,
            "description" => $description,
        ];
        $response = $this->doPatchRequest(TplFolder::class, $id, $data);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(TplFolder::class);
        $this->assertJsonContains($data);

        // create a permission
        $dataPermission = [
            'role'      => 'CCEUser',
            'right'     => TplFolderPermission::RIGHT_ANNOTATE,
            'tplFolder' => $this->getIri(TplFolder::class, $id),
        ];

        $response = $this->doPostRequest(TplFolderPermission::class, $dataPermission);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(TplFolderPermission::class);
        $this->assertJsonContains($dataPermission);

        // get tplfolder
        $response = $this->doGetRequest(TplFolder::class, $id);

        // modify permissions
        // only last created now
        $data                = [];
        unset($dataPermission['tplFolder']);
        $data['permissions'] = [$dataPermission];
        $response            = $this->doPatchRequest(TplFolder::class, $id, $data);
        $data                 = json_decode($response->getContent(), true);
        unset($data['permissions'][0]['resource']);
        unset($data['permissions'][0]['id']);
        unset($data['permissions'][0]['tplFolder']);
        $this->assertEquals([$dataPermission], $data['permissions']);
    }
}
