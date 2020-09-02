<?php
/*
 * Core
 * RoleDataProvider.php
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

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\CentralAdmin\KeycloakConnector;
use App\Entity\Role;
use Generator;
use Ramsey\Uuid\Uuid;

/**
 * Class RoleDataProvider
 */
final class RoleDataProvider extends KeycloakDataProvider implements CollectionDataProviderInterface, ItemDataProviderInterface, RestrictedDataProviderInterface
{

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     *
     * @return \Generator
     */
    public function getCollection(string $resourceClass, string $operationName = null): Generator
    {
        $kcRoles = $this->getKeycloakConnector()->getRoles();
        foreach ($kcRoles as $kcrole) {
            $kcroleObject = (object)$kcrole;
            $role         = new Role();
            $role->setId($kcroleObject->id);
            $role->setName($kcroleObject->name);
            yield $role;
        }
        return [];
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Role
    {
        $item = null;
        // retrieve user OR group

        if (!Uuid::isValid($id)) {
            // maybe it's a name
            $id   = KeycloakConnector::toKeycloakRole($id);
            $data = $this->getKeycloakConnector()->getRoleByName($id);
        } else {
            $data = $this->getKeycloakConnector()->getRoleById($id);
        }


        if (!empty($data)) {
            $item = new Role();
            $item->setId($data['id']);
            $item->setName($data['name']);
        }

        return $item;
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     * @param array       $context
     *
     * @return bool
     */
    public function supports(
        string $resourceClass,
        string $operationName = null,
        array $context = []
    ): bool {
        return Role::class === $resourceClass;
    }
}
