<?php
/*
 * Core
 * APIQuestionnairesTest.php
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

use App\Entity\Questionnaire;
use App\Entity\Target;

require_once('_ApiTest.php');

class APIQuestionnairesTest extends ApiTest
{
    /**
     * @group Default
     */
    public function testQuestionnaires()
    {
        $response = $this->doGetRequest(Questionnaire::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Target::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
        }
    }
}
