<?php
/*
 * Core
 * APICategoriesTest.php
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
use Faker\Factory;

require_once('_ApiTest.php');

class APICategoriesTest extends ApiTest
{
    /**
     * @group Default
     */
    public function testCategories()
    {
        $this->setViewClient()->setNormalUser();
        $response = $this->doGetRequest(Category::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Category::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
        }
    }

    /**
     * @group Default
     */
    public function testCommon()
    {
        $this->commonTest(Category::class);
    }

    /**
     * @group Default
     */
    public function testCreateCategories()
    {
        $faker = Factory::create();
        $data  = [
            "label" => "test Category " . $faker->city
        ];
        $this->setAdminClient()->setAdminUser();
        $response = $this->doPostRequest(Category::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(Category::class);
        $this->assertJsonContains($data);
        $data = [
            "label"       => "test Category " . $faker->city,
            "description" => "desc = " . $faker->domainName
        ];
        $this->setAdminClient()->setAdminUser();
        $response = $this->doPostRequest(Category::class, $data);
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceCollectionJsonSchema(Category::class);
        $this->assertJsonContains($data);
    }
}
