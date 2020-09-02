<?php
/*
 * Core
 * KeycloakConnectorTest.php
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

namespace App\Tests\CentralAdmin;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\CentralAdmin\KeycloakConnector;
use Ramsey\Uuid\Uuid;

class KeycloakConnectorTest extends ApiTestCase
{
    private $kc = null;

    private function init()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->kc  = new KeycloakConnector(
            \App\Service\CCETools::param($container,'CCE_KEYCLOAKURL'),
            \App\Service\CCETools::param($container,'CCE_KEYCLOAKSECRET'),
            \App\Service\CCETools::param($container,'CCE_coreclient'),
            \App\Service\CCETools::param($container,'CCE_KEYCLOAKREALM')
        );
    }

    /**
     * @group CentralAdmin
     */
    public function testGetGroupId()
    {
        $this->init();
        $this->assertInstanceOf(KeycloakConnector::class, $this->kc);
        $groupId = $this->kc->getGroupId('/Functions/Store Manager');
        $this->assertEquals(true, Uuid::isValid($groupId));
        $groupId = $this->kc->getGroupId('grosbidule');
        $this->assertEquals(false, Uuid::isValid($groupId));
    }

    /**
     * @group CentralAdmin
     */
    public function testGetGroupMembers()
    {
        $this->init();
        $this->assertInstanceOf(KeycloakConnector::class, $this->kc);
        $members = $this->kc->getGroupMembers('dummy');
        $this->assertEquals([], $members);
        $groupId = $this->kc->getGroupId('/Functions/District Manager');
        $members = $this->kc->getGroupMembers($groupId);
        $this->assertNotEquals([], $members);
        $this->assertCount(4, $members);

    }

    /**
     * @group CentralAdmin
     */
    public function testGetUserGroups()
    {
        $this->init();
        $this->assertInstanceOf(KeycloakConnector::class, $this->kc);
        $userGroups = $this->kc->getUserGroups($this->kc->getUserId('audie.fritsch'));
        $this->assertIsArray($userGroups);
    }

    /**
     * @group CentralAdmin
     */
    public function testGetUserId()
    {
        $this->init();
        $this->assertInstanceOf(KeycloakConnector::class, $this->kc);
        $userId = $this->kc->getUserId('audie.fritsch');
        $this->assertEquals(true, Uuid::isValid($userId));
        $userId = $this->kc->getUserId('dummyuser');
        $this->assertEquals(false, Uuid::isValid($userId));
    }
}
