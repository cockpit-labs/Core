<?php
/*
 * Core
 * APICalendarsTest.php
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
use CronExpressionGenerator\FakerProvider;
use Faker\Factory;

require_once('_ApiTest.php');

class APICalendarsTest extends ApiTest
{
    /**
     * @group Default
     */
    public function testCalendarsForAdminUser()
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
    public function testCalendarsForNormalUser()
    {
        // @todo verify attribute periodStart and periodEnd
        $response = $this->doGetRequest(Calendar::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertTrue(!empty($result['periodStart']));
            $this->assertNotEmpty($result);
        }
    }

    /**
     * @group Default
     */
    public function testCommon()
    {
        $this->commonTest(Calendar::class);
    }

    /**
     * @group Default
     */
    public function testCreateCalendars()
    {
        $this->setAdminClient()->setAdminUser();
        $faker = Factory::create();
        $faker->addProvider(new FakerProvider($faker));

        $data = [
            "label"     => "test Calendar " . $faker->colorName,
            "start"     => $faker->dateTime()->modify('midnight')->format('c'),
            "end"       => $faker->dateTimeBetween("+1 years", "+2 years")->format('c'),
            "cronStart" => '7 15 * * 1',
            "cronEnd"   => '0 8 * * 4',
        ];

        $response = $this->doPostRequest(Calendar::class, $data);
        unset($data['end']);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertJsonContains($data);
        $data = [
            "label"     => "test Calendar with no cron" . $faker->colorName,
            "start"     => $faker->dateTime()->modify('midnight')->format('c'),
            "end"       => $faker->dateTimeBetween("+1 years", "+2 years")->format('c'),
        ];


        $response = $this->doPostRequest(Calendar::class, $data);
        unset($data['end']);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertJsonContains($data);


    }

}
