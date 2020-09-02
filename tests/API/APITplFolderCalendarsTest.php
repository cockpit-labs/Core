<?php
/*
 * Core
 * APITplFolderCalendarsTest.php
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

//
//namespace App\Tests\API;
//
//use App\Entity\Calendar;
//use App\Entity\TplFolder;
//use App\Entity\TplFolderCalendar;
//use CronExpressionGenerator\FakerProvider;
//use Faker\Factory;
//
//require_once('_ApiTest.php');
//
//class APITplFolderCalendarsTest extends ApiTest
//{
//    /**
//     * test create a TplFolderCalendars, and delete it after
//     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
//     * @group Default
//     */
//    public function testCreateTplFolderCalendars()
//    {
//        $this->setAdminClient()->setAdminUser();
//
//        $faker = Factory::create();
//        $faker->addProvider(new FakerProvider($faker));
//
//        $this->bootKernel();
//        $TplFolderId = $this->getAnId(TplFolder::class, ['label' => 'TplFolder: no calendar']);
//        $calendarId       = $this->getAnId(Calendar::class);
//        $TplFolder   = static::findIriBy(TplFolder::class, ['id' => $TplFolderId]);
//        $calendar         = static::findIriBy(Calendar::class, ['id' => $calendarId]);
//        $data             = [
//            "TplFolder" => $TplFolder,
//            "calendar"       => $calendar
//        ];
//
//        $response = $this->doPostRequest(TplFolderCalendar::class, $data);
//        $this->assertResponseStatusCodeSame(201);
//        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
//
//
//        $response = $this->doGetRequest(
//            TplFolderCalendar::class,
//            ['calendar' => $calendarId, 'TplFolder' => $TplFolderId]
//        );
//        $this->assertResponseStatusCodeSame(200);
//        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
//        $this->assertJsonContains(
//            '{"TplFolder": "/api/Tpl_folders/' .
//            $TplFolderId . '","calendar": "/api/calendars/' . $calendarId . '"}'
//        );
//
//        $response = $this->doDeleteRequest(
//            TplFolderCalendar::class,
//            ['calendar' => $calendarId, 'TplFolder' => $TplFolderId]
//        );
//        $this->assertResponseStatusCodeSame(204);
//    }
//
//    /**
//     * Test getting TplFolderCalendars from an admin user
//     *
//     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
//     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
//     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
//     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
//     */
//    public function testTplFolderCalendarsForAdminUser()
//    {
//        $this->setAdminClient()->setAdminUser();
//        $response = $this->doGetRequest(TplFolderCalendar::class);
//        $this->assertResponseStatusCodeSame(200);
//        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
//        $this->assertNotEmpty(json_decode($response->getContent()));
//        foreach (json_decode($response->getContent(), true) as $result) {
//            $this->assertNotEmpty($result);
//        }
//    }
//
//    /**
//     * Test getting TplFolderCalendars from an normal user
//     * Must fail (403)
//     *
//     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
//     */
//    public function testTplFolderCalendarsForNormalUser()
//    {
//        $this->setViewClient()->setNormalUser();
//
//        $this->setViewClient()->setNormalUser();
//        $response = $this->doGetRequest(TplFolderCalendar::class);
//        $this->assertResponseStatusCodeSame(403);
//    }
//}
