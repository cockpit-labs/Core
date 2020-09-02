<?php
/*
 * Core
 * TargetDataProvider.php
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
use App\Entity\Target;
use Generator;
use Ramsey\Uuid\Uuid;

/**
 * Class TargetDataProvider
 */
final class TargetDataProvider extends KeycloakDataProvider implements CollectionDataProviderInterface, ItemDataProviderInterface, RestrictedDataProviderInterface
{

    public function addParentTarget($groups, &$targets, $target)
    {
        $parentId = $target['parent'] ?? null;
        if (!empty($parentId) && !empty($groups[$parentId]) && !empty($groups[$parentId]['parent'])) {
            $targets[$parentId] = $groups[$parentId];
            $this->addParentTarget($groups, $targets, $targets[$parentId]);
        }
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     *
     * @return \Generator
     */
    public function getCollection(string $resourceClass, string $operationName = null): Generator
    {
        if ($this->getUser()->getClient() === \App\Service\CCETools::param($this->getParameters(), 'CCE_viewclient')) {
            $userId = $this->getUserId();
            // we don't need all groups, just user's groups (and subgroups)
            if (!Uuid::isValid($userId)) {
                // maybe username is enough
                $userId = $this->getKeycloakConnector()->getUserId($this->getUser()->getUsername());
            }
        } elseif ($this->getUser()->getClient() === \App\Service\CCETools::param($this->getParameters(),
                                                                                 'CCE_adminclient')) {
            $userId = '';
        } else {
            return [];
        }
        $right = '';
        if (!empty($this->request->get('right'))) {
            $right = $this->request->get('right');
        }

        // extract target role
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->select('t.role')
                     ->from('App:TplFolderPermission', 'p');

        $queryBuilder->join("App:TplFolderTarget", "t");
        $queryBuilder->andWhere("p.tplFolder=t.tplFolder");
        $queryBuilder->andWhere(sprintf("p.right='%s'", $right));
//        $queryBuilder->andWhere('p.right IN (:rights)')
//                     ->setParameter('rights', $rights);

        $sql         = $queryBuilder->getQuery()->getSQL();
        $query       = $queryBuilder->getQuery();
        $targetRoles = array_map(function ($item) {
            return KeycloakConnector::toKeycloakRole($item['role']);
        }, $query->getResult());

        // get user groups
        $kcTargets = $this->getKeycloakConnector()->getUserGroups($userId, KeycloakConnector::DOWNMEMBERSHIP,
                                                                  $targetRoles);
        $parents   = [];
        foreach ($kcTargets as &$kcTarget) {
            // get parent
            $kcTarget['rights']=[$right];
            $parents = array_merge($parents, $this->getKeycloakConnector()->getParentGroups($kcTarget));
        }
        $parents = array_unique($parents, SORT_REGULAR);

        // remove all parents above common nodes
        // keep only nodes with more than one children, starting from root, stopping on first node with more than one children
        $parentCleaned=[];
        foreach ($parents as $id=>$parent) {
            $children=array_filter($parents, function($n)use ($parent){return $n['parent']==$parent['id'];});
            if(count($children)>1 || count($children)==0){
                $parentCleaned[$id]=$parent;
            }
        }
        $kcTargets = array_merge($kcTargets, $parentCleaned);

        foreach ($kcTargets as $kcTarget) {
            $kcTargetObject = (object)$kcTarget;
            $target         = new Target();
            $target->setId($kcTargetObject->id);
            $target->setName($kcTargetObject->name);
            $target->setParent($kcTargetObject->parent);
            $target->setType("GROUP");
            $target->setRights($kcTarget['rights'] ?? []);
            // add IRI
            if (method_exists(Target::class, "getIri")
                && method_exists(Target::class, "getId")
                && ($target->getId() ?? null) !== null
            ) {
                $target->setIri($this->getIriService()->getIriFromResourceClass(Target::class) . '/' . $target->getId());
            }
            yield $target;
        }
        return [];
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Target
    {
        $item = null;
        // retrieve user OR group

        if (!Uuid::isValid($id)) {
            // maybe it's an username
            $id = $this->getKeycloakConnector()->getUserId($id);
        }
        if (!Uuid::isValid($id)) {
            return [];
        }

        $data = $this->getKeycloakConnector()->getUser($id);
        if (empty($data)) {
            $data = $this->getKeycloakConnector()->getGroup($id);
        }
        if (!empty($data)) {
            $item = new Target();
            $item->setId($id);
            if (isset($data['username'])) {
                $data['name'] = $data['username'];
            }
            $item->setName($data['name']);
            if (isset($data['parent'])) {
                $item->setParent($data['parent']);
            }
            $item->setType($data['type']);
            // add IRI
            if (method_exists(Target::class, "getIri")
                && method_exists(Target::class, "getId")
                && ($item->getId() ?? null) !== null
            ) {
                $item->setIri($this->getIriService()->getIriFromResourceClass(Target::class) . '/' . $item->getId());
            }
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
        return Target::class === $resourceClass;
    }
}
