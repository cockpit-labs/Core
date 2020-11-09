<?php
/*
 * Core
 * PermissionsExtension.php
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

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\CentralAdmin\KeycloakConnector;
use App\DataProvider\CommonDataProvider;
use App\Entity\Calendar;
use App\Entity\Folder\FolderTplPermission;
use App\Traits\stateableEntity;
use Doctrine\ORM\QueryBuilder;

final class PermissionsExtension extends CommonDataProvider implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private $operationName = "";

    /**
     *
     * add SQL Constraint for cockpitview Keycloak Client
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($this->getUser() != null && $this->getUser()->getClient() === \App\Service\CCETools::param($this->getParameters(),
                                                                                                       'CCE_viewclient')) {
            $addWhereMethod = explode('\\', $resourceClass);
            $addWhereMethod = end($addWhereMethod) . "AddWhere";
            if (method_exists($this, $addWhereMethod)) {
                $this->$addWhereMethod($queryBuilder, $resourceClass);
            }
        }
    }

    /**
     *
     * add filters for Folder
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function folderAddWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $allFolderTpls = true;
        if (!empty($this->getRequest()->get('all'))) {
            $allFolderTpls = strtolower($this->getRequest()->get('all')) === 'true' ?? false;
        }

        $currentUserName        = $this->getUser()->getUsername();
        $downmembershipGroups = $this->getKeycloakConnector()->getUserGroups($this->getUser()->id,
                                                                             KeycloakConnector::DOWNMEMBERSHIP);
        $downmembershipGroups = implode(',', array_map(function ($val) {
            return  sprintf("'%s'", $val['id']);
        }, $downmembershipGroups));

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->distinct(true)
                     ->andWhere("$rootAlias.target in ($downmembershipGroups)")
                     ->join("$rootAlias.folderTpl", "t")->andWhere("t.id=$rootAlias.folderTpl")
                     ->join("$rootAlias.questionnaires", "q")->andWhere("q.folder=$rootAlias.id");

        if ($allFolderTpls === false) {
            $queryBuilder->join("$rootAlias.calendars", "c")->andWhere("c.valid=1");
        }

        $state = stateableEntity::getStateDraft();
        if ($this->operationName === "getdraft") {
            $queryBuilder->andWhere("$rootAlias.createdBy='$currentUserName'");
        } else {
//            $userRoles = implode(
//                ', ',
//                array_map(function ($val) {
//                    return sprintf("'%s'", $val);
//                }, $this->getUser()->getRoles()));

            $state = stateableEntity::getStateSubmitted();
            // user must have *at least* a rigth on the folder to know its existence
            $queryBuilder->join("t.permissions", "p");
//            $queryBuilder->andWhere(sprintf("p.right='%s'", FolderTplPermission::RIGHT_VIEW));
        }

        $queryBuilder->andWhere("${rootAlias}.state='${state}'");
        $sql = $queryBuilder->getQuery()->getSQL();

        /*
         * use this to get sql query : $sql=$queryBuilder->getQuery()->getSQL();
        $sql = $queryBuilder->getQuery()->getSQL();
         */
    }

    /**
     *
     * Add filter for Folder
     *      filter only submitted Folder
     *      filter only creatable Folder for user (user must have RIGHT_CREATE on this object =>
     *          table FolderTplPermission give ROLE/RIGHT/Folder)
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function folderTplAddWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {

        $permissions_right = '';
        if (!empty($this->getRequest()->get('permissions_right'))) {
            $permissions_right = strtoupper($this->getRequest()->get('permissions_right'));
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $userRoles = implode(
            ', ',
            array_map(function ($val) {
                return sprintf("'%s'", $val);
            }, $this->getUser()->getRoles()));

        $queryBuilder->distinct(true)
                     ->andWhere(sprintf("%s.state='%s'", $rootAlias,
                                        stateableEntity::getStateSubmitted()));
        $allFolderTpls = true;
        if (!empty($this->getRequest()->get('all'))) {
            $allFolderTpls = strtolower($this->getRequest()->get('all')) === 'true' ?? false;
        }
        if ($allFolderTpls === false) {
            $queryBuilder->join(Calendar::class, "c")->andWhere("c.valid=1");
        }
        // user must have *at least* a rigth on the folderTpl to know its existence
        $queryBuilder->join(FolderTplPermission::class, "p")->andWhere(sprintf('p.role in (%s)', $userRoles));
        if (!empty($this->permissions_right)) {
            $queryBuilder->andWhere(sprintf("p.right='%s'", $this->permissions_right));
        }

        /*
         * use this to get sql query : $sql=$queryBuilder->getQuery()->getSQL();
        $sql = $queryBuilder->getQuery()->getSQL();  $a   = 1;
         */
    }

    /**
     *
     * add filters for Folder template permission
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function folderTplPermissionAddWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $userRoles = implode(
            ', ',
            array_map(function ($val) {
                return sprintf("'%s'", $val);
            }, $this->getUser()->getRoles()));
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->andWhere("$rootAlias.roles IN (:roles)")->setParameter('roles', $userRoles);
    }

    /**
     * filter tasks list on user (createdBy, responsible or informed)
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function taskAddWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $userId = $this->getUser()->getUsername();
        $queryBuilder->distinct(true)
                     ->andWhere("$rootAlias.createdBy='$userId' OR $rootAlias.responsibleId='${userId}' OR $rootAlias.informedIds like '%${userId}%'");

        /*
         * use this to get sql query : $sql=$queryBuilder->getQuery()->getSQL();
        $sql = $queryBuilder->getQuery()->getSQL();  $a   = 1;
         */
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder                                             $queryBuilder
     * @param \ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface $queryNameGenerator
     * @param string                                                                 $resourceClass
     * @param string|null                                                            $operationName
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if ($this->getUser() === null) {
            return;
        }

        $this->operationName = $operationName;
        $this->addWhere($queryBuilder, $resourceClass);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder                                             $queryBuilder
     * @param \ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface $queryNameGenerator
     * @param string                                                                 $resourceClass
     * @param array                                                                  $identifiers
     * @param string|null                                                            $operationName
     * @param array                                                                  $context
     */
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        string $operationName = null,
        array $context = []
    ) {
    }

}
