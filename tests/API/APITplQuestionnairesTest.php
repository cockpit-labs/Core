<?php
/*
 * Core
 * APITplQuestionnairesTest.php
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

use App\Entity\Questionnaire\QuestionnaireTpl;

require_once('_ApiTest.php');

class APITplQuestionnairesTest extends ApiTest
{
    /**
     * @group Default
     */
    public function testCommon()
    {
        $this->commonTest(QuestionnaireTpl::class);
    }

    /**
     * @group Default
     */
    public function testTplQuestionnaires()
    {
        $response = $this->doGetRequest(QuestionnaireTpl::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(QuestionnaireTpl::class);
        $this->assertNotEmpty(json_decode($response->getContent()));
        foreach (json_decode($response->getContent(), true) as $result) {
            $this->assertNotEmpty($result);
        }
    }

    /**
     * @group Default
     */
//    public function testTplQuestionnairesUpdateBlocks()
//    {
//        $this->setAdminClient()->setAdminUser();
//        $response = $this->doGetRequest(QuestionnaireTpl::class);
//        $this->assertResponseStatusCodeSame(200);
//        $this->assertMatchesResourceCollectionJsonSchema(QuestionnaireTpl::class);
//        $questionnaires = json_decode($response->getContent(),true);
//        foreach ($questionnaires as &$questionnaire) {
//
//            // reverse blocks position
//            foreach ($questionnaire['tplBlocks'] as &$tplBlock) {
//                $tplBlock['label']="test tplBlock77";
//                $tplBlock['position']+=77;
//                unset($tplBlock['tplQuestions']);
//            }
//            $response = $this->doPatchRequest(QuestionnaireTpl::class, $questionnaire['id'],$questionnaire);
//        }
//
//    }
    public function testTplQuestionnairesUpdateLabel()
    {
        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest(QuestionnaireTpl::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(QuestionnaireTpl::class);
        $questionnaires = json_decode($response->getContent(),true);
        foreach ($questionnaires as &$questionnaire) {
            $testQuestionnaire['id']=$questionnaire['id'];
            $testQuestionnaire['label']='updated label';
            $response = $this->doPatchRequest(QuestionnaireTpl::class, $testQuestionnaire['id'], $testQuestionnaire);
            $updatedQuestionnaire = json_decode($response->getContent(), true);
            unset($questionnaire['label']);
            unset($updatedQuestionnaire['label']);
            $this->assertEquals($questionnaire, $updatedQuestionnaire);
        }
    }
    /**
     * @group Default
     */
    public function testTplQuestionnairesAddBlock()
    {
        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest(QuestionnaireTpl::class);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(QuestionnaireTpl::class);
        $questionnaires = json_decode($response->getContent(), true);
        foreach ($questionnaires as &$questionnaire) {
            foreach ($questionnaire['blockTpls'] as &$tplBlock) {
                unset($tplBlock['tplQuestions']);
            }
            unset($questionnaire['questionnaireTplsBlockTpls']);
            // add block
            $newBlock['label']=$this->getFaker()->word();
            $newBlock['position']=777;

            $questionnaire['blockTpls'][]=$newBlock;
            $response = $this->doPatchRequest(QuestionnaireTpl::class, $questionnaire['id'], $questionnaire);
        }// read again
        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest(QuestionnaireTpl::class);


    }
}
