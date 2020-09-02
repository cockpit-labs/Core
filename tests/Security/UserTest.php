<?php
/*
 * Core
 * UserTest.php
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

namespace App\Tests\Security;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Security\User;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

class UserTest extends ApiTestCase
{
    private $username = '';
    private $payload  = [];

    public function preTest()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        // init http client
        $options['base_uri']    = \App\Service\CCETools::param($container,'CCE_KEYCLOAKURL');
        $options['verify']      = false;
        $options['http_errors'] = false;
        if (isset($_ENV['AUTOSIGNEDPEM'])) {
            $options['verify'] = $_ENV['AUTOSIGNEDPEM'];
        }
        $httpClient = new Client($options);


        $params   = [
            'form_params' => [
                'client_id'  => 'cockpitview',
                'username'   => 'kallie.dibbert',
                'password'   => 'kallie.dibbert',
                'grant_type' => 'password',
            ]
        ];
        $response = $httpClient->request(
            'POST',
            '/auth/realms/cockpit-ce/protocol/openid-connect/token',
            $params
        );
        self::assertEquals(200, $response->getStatusCode());
        $body           = json_decode($response->getBody(), true);
        $this->username = 'kallie.dibbert';
        $jwt            = \JOSE_JWT::decode($body['access_token']);
        $this->payload  = $jwt->claims;

    }

    public function testPayload()
    {
        $this->preTest();
        $user = User::createFromPayload($this->username, $this->payload);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->username, $user->getUsername());
        $this->assertIsArray($user->getRoles());
        $this->assertEquals('cockpitview', $user->getClient());
//        $this->assertEquals('en', $user->getLocale());
        $this->assertEquals(true, Uuid::isValid($user->getId()));
    }

    public function testUnusedGetters()
    {
        $this->preTest();
        $user = User::createFromPayload($this->username, $this->payload);
        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals(null, $user->getPassword());
        $this->assertEquals(null, $user->getSalt());
        $this->assertEquals(null, $user->eraseCredentials());
    }

    public function testUserCreation()
    {
        $this->preTest();
        $user = User::createFromPayload($this->username, $this->payload);
        $this->assertInstanceOf(User::class, $user);
    }
}
