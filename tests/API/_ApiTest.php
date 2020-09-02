<?php
/*
 * Core
 * _ApiTest.php
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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\CentralAdmin\KeycloakConnector;
use App\Entity\Calendar;
use App\Traits\stateableEntity;
use Faker\Generator;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Faker\Factory;
use App\Service\CCETools;

/**
 * Class ApiTest
 *
 * @package App\Tests\API
 */
class ApiTest extends ApiTestCase
{
    public  $browserClient = null;
    private $iriConverter  = null;
    /**
     * @var string
     */
    private $token = '';
    /**
     * @var array
     */
    private $options       = [];
    private $headers       = [];
    private $cockpitClient = 'cockpitview'; // normal user. Not admin
    private $user          = "kallie.dibbert";
    private $password      = "kallie.dibbert";
    private $kc            = null;
    private $faker      = null;

    private $roleView  = [
        "roles" => [
            "CCEDashboard",
            "CCEUser",
            "RMMensuel_write",
            "RMHebdo_write",
            "RMJour_write",
        ]
    ];
    private $roleAdmin = [
        "roles" => [
            "CCEDashboard",
            "CCEUser",
            "CCEAdmin",
            "RMMensuel_write",
            "RMHebdo_write",
            "RMJour_write",
        ]
    ];

    private $roles;
    private $userId;

    private function doRequest($url, $operation, $options, $file = [])
    {
        return $this->getbrowserClient()->request($operation, $url, $options, $file);
    }

    /**
     * @return string
     */
    private function getToken(): string
    {
        if (empty($this->token)) {
            $this->requestToken();
        }
        return $this->token;
    }

    /**
     *
     */
    private function requestToken()
    {
        $this->token = $this->getKc()->requestToken($this->cockpitClient, $this->user, $this->user);
    }

    private function updateDescription($entity)
    {
        $description = "modified description";
        $data        = ["description" => $description];

        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest($entity);
        $id       = $this->getAnId($entity);
        $response = $this->doPatchRequest($entity, $id, $data);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertJsonContains($data);

    }

    private function updateLabel($entity)
    {
        $label = "modified label";
        $data  = ["label" => $label];

        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest($entity);
        $id       = $this->getAnId($entity);
        if(!empty($id)) {
            $response = $this->doPatchRequest($entity, $id, $data);
            $this->assertResponseStatusCodeSame(200);
            $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
            $this->assertJsonContains($data);
        }

    }

    private function updateState($entity)
    {
        $state = stateableEntity::getStateDraft();
        $data  = ["state" => $state];

        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest($entity);
        $id       = $this->getAnId($entity);
        $response = $this->doPatchRequest($entity, $id, $data);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertJsonContains($data);

        $state = stateableEntity::getStateSubmitted();
        $data  = [
            "state" => $state,
        ];

        $this->setAdminClient()->setAdminUser();
        $response = $this->doGetRequest($entity);
        $id       = $this->getAnId($entity);
        $response = $this->doPatchRequest($entity, $id, $data);
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(Calendar::class);
        $this->assertJsonContains($data);

    }

    public function commonTest($entity)
    {
        $this->preTest();
        if (property_exists($entity, "state")) {

        }
        if (property_exists($entity, "description")) {
            $this->updateDescription($entity);
        }
        if (property_exists($entity, "label")) {
            $this->updateLabel($entity);
        }
    }

    public function doDeleteRequest($class, $id, $headers = [])
    {
        $this->preTest();
        $options = empty($headers) ? $this->headers : $headers;
        if (is_array($id)) {
            $iri = static::findIriBy($class, $id);
        } else {
            $iri = $this->getIriConverter()->getIriFromResourceClass($class);
            if (!empty($id)) {
                $iri = "$iri/$id";
            }
        }
        return $this->doRequest($iri, 'DELETE', $options);
    }

    public function doDirectRequest($operation, $url)
    {
        return $this->getbrowserClient()->request($operation, $url, $this->headers);
    }

    /**
     * @param       $route
     * @param array $headers
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\browserClient\ResponseInterface
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    public function doGetRequest($class, $id = null, $headers = [], $files = [])
    {
        $this->preTest();
        $options = empty($headers) ? $this->headers : $headers;
        if (is_array($id)) {
            $iri = static::findIriBy($class, $id);
        } else {
            $iri = $this->getIriConverter()->getIriFromResourceClass($class);
            if (!empty($id)) {
                $iri = "$iri/$id";
            }
        }
        self::assertIsString($iri, "Wrong IRI");
        return $this->doRequest($iri, 'GET', $options, $files);
    }

    public function doGetSubresourceRequest($class, $id, $sub, $headers = [], $files = [])
    {
        $this->preTest();
        $options = empty($headers) ? $this->headers : $headers;
        $this->assertIsString($id);
        $this->assertIsString($sub);
        $iri = $this->getIriConverter()->getIriFromResourceClass($class);

        $iri = $iri . '/' . $id . '/' . $sub;

        return $this->doRequest($iri, 'GET', $options, $files);
    }

    /**
     * @param $route
     * @param $data
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\browserClient\ResponseInterface
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    public function doPatchRequest($class, $id, $data, $headers = [])
    {
        $this->preTest();
        $headers                            = $this->headers;
        $headers['headers']['content-type'] = 'application/merge-patch+json';
        $options                            = array_merge($headers, ['body' => json_encode($data)]);

        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest("$iri/$id", 'PATCH', $options);
    }

    /**
     * @param       $class
     * @param       $id
     * @param       $data
     * @param       $action
     * @param array $headers
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\HttpClient\ResponseInterface
     */
    public function doPatchWithActionRequest($class, $id, $data, $action, $headers = [])
    {
        $this->preTest();
        $headers                            = $this->headers;
        $headers['headers']['content-type'] = 'application/merge-patch+json';
        $options                            = array_merge($headers, ['body' => json_encode($data)]);

        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest("$iri/$id/$action", 'PATCH', $options);
    }

    /**
     * @param $route
     * @param $data
     *
     * @return \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response|\Symfony\Contracts\browserClient\ResponseInterface
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    public function doPostRequest($class, $data)
    {
        $this->preTest();
        $options = array_merge($this->headers, ['body' => json_encode($data)]);
        $iri     = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest($iri, 'POST', $options);
    }

    public function doUploadFileRequest($class, $files)
    {
        $this->preTest();
        $headers                            = $this->headers;
        $headers['headers']['content-type'] = 'multipart/formdata';

        $iri = $this->getIriConverter()->getIriFromResourceClass($class);
        return $this->doRequest($iri, 'POST', $headers, $files);
    }

    public function getAnId($class, $restrict = null)
    {
        $response = $this->doGetRequest($class, $restrict);
        $data     = json_decode($response->getContent(), true);
        if (!is_array($data)) {
            return '';
        } elseif (!empty($data[0]['id'])) {
            return $data[0]['id'];
        } elseif (!empty($data['id'])) {
            return $data['id'];
        } else {
            return '';
        }
    }

    public function getBrowserClient()
    {
        if (empty($this->browserClient)) {
            $this->browserClient = static::createClient();
        }
        return $this->browserClient;
    }

    /**
     * @return null
     */
    public function getFaker(): Generator
    {
        if($this->faker === null){
            $this->faker = Factory::create();
        }
        return $this->faker;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param String $class
     * @param string $id
     *
     * @return String
     */
    public function getIri(String $class, $id = ""): String
    {
        $iri = static::$container->get('api_platform.iri_converter')->getIriFromResourceClass($class);
        if (($id ?? null) !== null) {
            $iri .= '/' . $id;
        }
        return $iri;
    }

    /**
     * @return null
     */
    public function getIriConverter()
    {
        if ($this->iriConverter === null) {
            $this->iriConverter = static::$container->get('api_platform.iri_converter');
        }
        return $this->iriConverter;
    }

    /**
     * @return null
     */
    public function getKc()
    {
        if (empty($this->kc)) {
            $this->kc = new KeycloakConnector(
                CCETools::param($this->getbrowserClient()->getContainer(),'CCE_KEYCLOAKURL'),
                CCETools::param($this->getbrowserClient()->getContainer(),'CCE_KEYCLOAKSECRET'),
                CCETools::param($this->getbrowserClient()->getContainer(),'CCE_coreclient'),
                CCETools::param($this->getbrowserClient()->getContainer(),'CCE_KEYCLOAKREALM')
            );
        }
        return $this->kc;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        if (empty($this->userId)) {
            $this->userId = $this->getKc()->getUserId($this->user);
        }
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }

    public function preTest()
    {
        $this->options = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getToken(),
            'CONTENT_TYPE'       => 'application/json',
        ];
        $this->headers = [
            'headers' => [
                'content-type'  => 'application/json',
                'accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $this->getToken()
            ]
        ];
    }

    /**
     * @return $this
     */
    public function setAdminClient()
    {
        $this->setCockpitClient('cockpitadmin');
        return $this;
    }

    /**
     * @return $this
     */
    public function setAdminUser()
    {
        $this->setPassword("audie.fritsch");
        $this->setUser("audie.fritsch");
        $this->setUserId($this->getKc()->getUserId('audie.fritsch'));
        $this->token = '';
        $this->roles = $this->roleAdmin;
        return $this;
    }

    /**
     * @param string $cockpitClient
     *
     * @return $this
     */
    public function setCockpitClient(string $cockpitClient)
    {
        $this->cockpitClient = $cockpitClient;
        $this->token         = null;
        return $this;
    }

    /**
     * @return $this
     */
    public function setNormalUser()
    {
        $this->setPassword("kallie.dibbert");
        $this->setUser("kallie.dibbert");
        $this->setUserId($this->getKc()->getUserId('kallie.dibbert'));
        $this->token = '';
        $this->roles = $this->roleView;
        return $this;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
        $this->token    = null;
        return $this;
    }

    /**
     * @param string $user
     *
     * @return $this
     */
    public function setUser(string $user)
    {
        $this->user  = $user;
        $this->token = null;
        return $this;
    }

    /**
     * @return $this
     */
    public function setViewClient()
    {
        $this->setCockpitClient('cockpitview');
        return $this;
    }

    /**
     *
     */
    public function testToken()
    {
        $response = $this->doGetRequest(Calendar::class);
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    public function testWithBadToken()
    {
        $headers = [
            'headers' => [
                'content-type'  => 'application/json',
                'Authorization' => 'Bearer toto'
            ]
        ];

        $this->doGetRequest(Calendar::class, [], $headers);
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @throws \Symfony\Contracts\browserClient\Exception\TransportExceptionInterface
     */
    public function testWithoutToken()
    {
        $headers = [
            'headers' => [
                'content-type' => 'application/json',
            ]
        ];

        $this->doGetRequest(Calendar::class, [], $headers);
        $this->assertResponseStatusCodeSame(401);
    }
}
