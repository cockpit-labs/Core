<?php
/*
 * Core
 * KeycloakConnector.php
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

namespace App\CentralAdmin;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Ramsey\Uuid\Uuid;

/**
 * Class KeycloakConnector
 *
 * @package App\CentralAdmin
 */
class KeycloakConnector
{

    public const NOLEGACYMEMBERSHIP = 'NO';
    public const UPMEMBERSHIP       = 'UP';
    public const DOWNMEMBERSHIP     = 'DOWN';

    public const  KCSUBGROUPS      = "subGroups";
    public const  KCREALMROLES     = "realmRoles";
    private const ATTRIBUTE_HIDDEN = "hidden";
    private const ATTRIBUTES       = "attributes";
    /**
     * @var array
     */
    private $flatGroups = [];

    private $keycloakSecret    = '';
    private $username          = '';
    private $password          = '';
    private $keycloakClientApp = '';
    /**
     * @var array
     */
    private $tree = [];
    /**
     * @var string
     */
    private $token = "";
    /**
     * @var array
     */
    private $pathList = [];

    /**
     * keycloakConnector constructor.
     *
     * @param String $keycloakUrl
     * @param String $keycloakSecret
     * @param String $keycloakClient
     * @param String $keycloakRealm
     *
     * @throws \Exception
     */
    public function __construct(
        string $keycloakUrl,
        $keycloakSecret,
        string $keycloakClient,
        string $keycloakRealm
    ) {
        // set keycloak env
        if (is_array($keycloakSecret) && !empty($keycloakSecret['username']) && !empty($keycloakSecret['password'])) {
            $this->username = $keycloakSecret['username'];
            $this->password = $keycloakSecret['password'];
        } else {
            $this->keycloakSecret = $keycloakSecret;
        }
        $this->keycloakUrl       = $keycloakUrl;
        $this->keycloakClient    = $keycloakClient;
        $this->keycloakRealm     = $keycloakRealm;
        $this->keycloakApiPath   = $keycloakUrl . '/auth/admin/realms/' . $this->keycloakRealm;
        $this->keycloakTokenPath =
            $keycloakUrl . '/auth/realms/' . $this->keycloakRealm . '/protocol/openid-connect/token';

        // Get Core token from KeyCloak
        $this->requestToken($this->keycloakClient, $this->username, $this->password);

        // init http client
        if ($_ENV['APP_ENV'] == 'test' || $_ENV['APP_ENV'] == 'dev') {
            $options['verify'] = false;
        }

        $options['base_uri'] = $keycloakUrl;
        $options['headers']  = ['Authorization' => 'Bearer ' . $this->token];
        $this->httpClient    = new Client($options);

        // init groups
        $this->childrenGroups = [];
    }

    /**
     * @param        $resource
     * @param string $operation
     * @param null   $id
     * @param null   $subResource
     * @param array  $param
     *
     * @return array
     */
    private function callAdminAPI($resource, $operation = 'GET', $id = null, $subResource = null, $param = []): array
    {
        if (!empty($resource)) {
            $route = $this->keycloakApiPath . "/$resource";
            if (!empty($id)) {
                $route .= "/$id";
                if (!empty($subResource)) {
                    $route .= "/$subResource";
                }
            }
            if (!empty($param)) {
                $route .= '?';
                foreach ($param as $name => $value) {
                    $route .= "$name=$value&";
                }
                $route = rtrim($route, "&");
            }
        } else {
            return [];
        }

        try {
            $response = $this->httpClient->request(
                $operation,
                $route
            );
            return json_decode($response->getBody(), true);

        } catch (ClientException $e) {
            // en cas d'erreur 4xx
            return [];
        }

    }

    /**
     * @param String $rootGroup
     *
     * @return array
     */
    private function callGetGroups(string $rootGroup = ""): array
    {
        // get group list from KeyCloak
        return $this->callAdminAPI('groups', 'GET', $rootGroup);
    }

    /**
     * @param String $userId
     *
     * @return array
     */
    private function callGetUserGroups(string $userId): array
    {
        // get user's group list from KeyCloak
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->keycloakApiPath . '/users/' . $userId . '/groups'
            );
            return json_decode($response->getBody(), true);

        } catch (ClientException $e) {
            // en cas d'erreur 4xx
            return [];
        }
    }

    private function enhanceGroups(array $groups)
    {
        $enhGroups=[];
        $allGroups = $this->getFlatGroups();
        foreach ($groups as $idx=>&$group) {
            if (!empty($allGroups[$group['id']])) {
                $enhGroups[$group['id']]=$allGroups[$group['id']];
            }
        }
        return $enhGroups;
    }

    /**
     * @param      $group
     * @param null $parent
     */
    private function extendTree(&$group, $parentId = null, $parentRoles = [])
    {
        if (isset($group[KeycloakConnector::KCSUBGROUPS])) {
            foreach ($group[KeycloakConnector::KCSUBGROUPS] as $id => $subGroup) {
                $roles = array_merge($parentRoles, $group['realmRoles']);
                $this->extendTree($group[KeycloakConnector::KCSUBGROUPS][$id], $group['id'], $roles);
            }
        }
        $flatGroup                     = $group;
        $flatGroup['parent']           = $parentId;
        $flatGroup[self::KCREALMROLES] = array_merge($parentRoles, $flatGroup[self::KCREALMROLES]);
        $flatGroup['heritedRoles']     = $parentRoles;
        $flatGroup['type']             = 'GROUP';
        if (!isset($flatGroup[self::ATTRIBUTES][self::ATTRIBUTE_HIDDEN])) {
            $flatGroup[self::ATTRIBUTES][self::ATTRIBUTE_HIDDEN] = false;
        }
        unset($flatGroup[KeycloakConnector::KCSUBGROUPS]);
        $this->flatGroups[$flatGroup['id']] = $flatGroup;
    }

    private function filterHidden($list): array
    {
        // remove subGroups from root groups
        return array_filter($list, function ($item) {
            if (($item[self::ATTRIBUTES] ?? null) === null) {
                return true;
            } else {
                return !($item[self::ATTRIBUTES][self::ATTRIBUTE_HIDDEN] ?? false);
            }
        }
        );
    }

    /**
     * @return array
     */
    private function getFullGroupsTree(): void
    {
        // get all groups from keycloak
        $rootGroups = $this->callGetGroups();

        // remove subGroups from root groups
        $rootGroups = array_map(function ($group) {
            unset($group[KeycloakConnector::KCSUBGROUPS]);
            return $group;
        }, $rootGroups
        );

        // get complete groups with roles
        // and flatten tree
        foreach ($rootGroups as &$rootGroup) {
            $rootGroup = $this->callGetGroups($rootGroup['id']);
            $this->extendTree($rootGroup);
        }
        $this->tree = [
            'id'                            => 'root',
            'name'                          => 'root',
            'path'                          => '/',
            KeycloakConnector::KCREALMROLES => null,
            KeycloakConnector::KCSUBGROUPS  => $rootGroups
        ];


    }

    /**
     * @param $id
     *
     * @return bool
     */
    private function isValidId($id): bool
    {
        return Uuid::isValid($id);
    }

    public function getChildrenGroups($group)
    {
        $refPath = $group['path'];

        $func = function ($group) use ($refPath) {
            return substr($group['path'], 0, strlen($refPath)) === $refPath;
        };
        $groups=array_filter($this->getFlatGroups(), $func);
        usort($groups, function($a, $b){
            return $a['path']<=>$b['path'];
        });
        return $groups;
    }

    /**
     * @return array
     */
    public function getFlatGroups(): array
    {
        if (empty($this->flatGroups)) {
            $this->getFullGroupsTree();
            $this->flatGroups = $this->filterHidden($this->flatGroups);
        }
        return $this->flatGroups;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getGroup(string $id): array
    {
        if ($this->isValidId($id) && array_key_exists($id, $this->getFlatGroups())) {
            return $this->getFlatGroups()[$id];
        }
        return [];
    }

    /**
     * @param $groupPath
     *
     * @return string
     */
    public function getGroupId($groupPath): string
    {
        $id    = "";
        $group = array_filter(
            $this->getFlatGroups(),
            function ($g) use (&$groupPath) {
                return $g['path'] === $groupPath;
            }
        );

        if (!empty($group)) {
            $id = key($group);
        }
        return $id;
    }

    /**
     * @param String $groupId
     * @param array  $havingRoles
     * @param bool   $recurse
     *
     * @return array
     */
    public function getGroupMembers(string $groupId): array
    {
        if ($this->isValidId($groupId)) {
            return $this->callAdminAPI('groups', 'GET', $groupId, 'members');
        }
        return [];
    }

    public function getParentGroups($group)
    {
        $refPath = $group['path'];

        $func = function ($group) use ($refPath) {
            return (substr($refPath, 0, strlen($group['path'])) === $group['path']) && ($group['path'] != $refPath);
        };
        $groups=array_filter($this->getFlatGroups(), $func);
        usort($groups, function($a, $b){
            return $a['path']<=>$b['path'];
        });
        return $groups;

    }

    public function getRoleById(string $id): array
    {
        return $this->callAdminAPI('roles-by-id', 'GET', $id);
    }

    public function getRoleByName(string $name = null): array
    {
        return $this->callAdminAPI('roles', 'GET', $name);
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->filterHidden($this->callAdminAPI('roles', 'GET'));
    }

    /**
     * @return array
     */
    public function getTree(): array
    {
        if (empty($this->tree)) {
            $this->getFullGroupsTree();
        }
        return $this->tree;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getUser(string $id): array
    {
        if ($this->isValidId($id)) {
            $user = $this->callAdminAPI('users', 'GET', $id, 'members');
            if (!empty($user)) {
                $user['type'] = 'USER';
            }
        }
        return [];
    }

    /**
     * @param string $userId
     * @param bool   $children if true, add children groups for each user groups
     * @param bool   $parents  if true, add parent groups for each user groups
     * @param array  $roles    filter users groups with at least one role from this list (children and/or parents
     *                         excluded)
     *
     * @return array
     */
    public function getUserGroups(string $userId, $membershipDirection = self::NOLEGACYMEMBERSHIP, $roles = [])
    {
        if (empty($userId)) {
            $userGroups = $this->getFlatGroups();
        } else {
            // get user groups
            $userGroups = $this->callGetUserGroups($userId);
            $userGroups = $this->enhanceGroups($userGroups);

            // filter legacy groups
            $getLegacyGroupsFunc = function ($group) use ($userGroups, $membershipDirection) {
                if (!isset($group['path'])) {
                    return false;
                }
                $groupPath = $group['path'];
                foreach ($userGroups as $userGroup) {
                    $path = $userGroup['path'];
                    if ($groupPath === $path) {
                        return true;
                    } elseif ($membershipDirection == self::DOWNMEMBERSHIP && substr($groupPath, 0,
                                                                                     strlen($path)) === $path) {
                        return true;
                    } elseif ($membershipDirection == self::UPMEMBERSHIP && substr($path, 0,
                                                                                   strlen($groupPath)) === $groupPath) {
                        return true;
                    }
                }
                return false;
            };
            // add legacy groups
            if ($membershipDirection != self::NOLEGACYMEMBERSHIP) {
                $userGroups = array_filter($this->getFlatGroups(), $getLegacyGroupsFunc);
            }
            $tmpUserGroups = [];
            foreach ($userGroups as $id=>$userGroup) {
                if (empty($roles) || count(array_intersect($userGroup['realmRoles'], $roles)) > 0) {
                    $tmpUserGroups[$id] = $userGroup;
                }
            }
            $userGroups = $tmpUserGroups;

        }

        if (!empty($roles)) {
            // add filter role to legacy groups
            $addRoleFunc = function ($group) use ($roles) {
                $group['realmRoles'] = array_unique(array_merge($group['realmRoles'], $roles));
                return $group;
            };
            $userGroups  = array_map($addRoleFunc, $userGroups);
        }
        return $userGroups;
    }

    /**
     * @param $username
     *
     * @return string
     */
    public function getUserId($username): string
    {
        $users = $this->callAdminAPI("users", "GET", null, null, ['search' => $username]);

        if (isset($users[0]['id'])) {
            return $users[0]['id'];
        } else {
            return "";
        }
    }

    public function partialImport(string $realm, string $json)
    {
        $url = $this->keycloakUrl . '/auth/admin/realms/' . $realm . '/partialImport';

        try {
            $client   = new Client(); //initialize a Guzzle client
            $response = $client->request('POST', $url, [
                'body'    => $json,
                'headers' => [
                    'Accept'        => 'application/json, text/plain,*/*',
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json;charset=UTF-8',
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (ClientException $e) {
            // en cas d'erreur
            return [];
        }
    }

    /**
     * @param string $clientApp
     * @param string $user
     * @param string $pwd
     *
     * @return String
     * @throws \Exception
     */
    public function requestToken(string $clientApp = "", string $user = "", string $pwd = ""): string
    {
        try {
            $options['base_uri'] = $this->keycloakUrl;

            if ($_ENV['APP_ENV'] == 'test' || $_ENV['APP_ENV'] == 'dev') {
                $options['verify'] = false;
            }
            $formParams = [
                'form_params' => [
                    'client_id'     => $this->keycloakClient,
                    'client_secret' => $this->keycloakSecret,
                    'grant_type'    => 'client_credentials'
                ]
            ];
            if (!empty($clientApp) && !empty($user) && !empty($pwd)) {
                $formParams = [
                    'form_params' => [
                        'client_id'  => $clientApp,
                        'username'   => $user,
                        'password'   => $pwd,
                        'grant_type' => 'password',
                    ]
                ];
            }
            $client   = new Client($options);
            $response = $client->request(
                'POST',
                $this->keycloakTokenPath,
                $formParams
            );
        } catch (ClientException $e) {
            throw new Exception($e->getMessage());
        }

        $body        = json_decode($response->getBody());
        $this->token = $body->access_token;
        return $this->token;
    }

    public function setUserPassword($userid, $realm, $newpassword)
    {
        $url = $this->keycloakUrl . '/auth/admin/realms/' . $realm . '/users/' . $userid . '/reset-password';
        try {
            $client   = new Client(); //initialize a Guzzle client
            $response = $client->request('PUT', $url, [
                'json'    => ['type' => 'password', 'temporary' => false, 'value' => $newpassword],
                'headers' => [
                    'Accept'        => 'application/json, text/plain,*/*',
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ]
            ]);


            return json_decode($response->getBody(), true);

        } catch (ClientException $e) {
            // en cas d'erreur
            return [];
        }
    }

    /**
     * @param String $name
     *
     * @return String
     */
    public static function toKeycloakRole(string $name): string
    {
        $prefix = 'ROLE_';
        if (substr($name, 0, strlen($prefix)) === $prefix) {
            $name = substr($name, strlen($prefix));
        }
        return $name;
    }

    /**
     * @param String $name
     *
     * @return String
     */
    public static function toSymfonyRole(string $name): string
    {
        $prefix = 'ROLE_';
        if (!(substr($name, 0, strlen($prefix)) === $prefix)) {
            $name = $prefix . $name;
        }
        return $name;
    }
}
