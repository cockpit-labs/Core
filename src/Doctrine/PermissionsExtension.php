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
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
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
use App\Traits\stateableEntity;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

final class PermissionsExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private $security;
    private $user          = null;
    private $operationName = "";
    private $params        = null;
    private $allTplFolders = true;

    /**
     * PermissionsExtension constructor.
     *
     * @param \Symfony\Component\Security\Core\Security $security
     */
    public function __construct(Security $security, ParameterBagInterface $params, RequestStack $request)
    {
        $this->params = $params;
        if (!empty($request->getCurrentRequest()->get('all'))) {
            $this->allTplFolders = strtolower($request->getCurrentRequest()->get('all')) === 'true' ?? false;
        }
        $this->permissions_right = '';
        if (!empty($request->getCurrentRequest()->get('permissions_right'))) {
            $this->permissions_right = strtoupper($request->getCurrentRequest()->get('permissions_right'));
        }
        $this->security = $security;
        if (!empty($this->security)) {
            $token = $this->security->getToken();
            if ($token != null) {
                $this->user = $token->getUser();
            }
        }
    }

    /**
     *
     * add SQL Constraint for cockpitview Keycloak Client
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($this->user != null && $this->user->getClient() === \App\Service\CCETools::param($this->params,
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
        $currentUserID = $this->user->getUsername();
        $rootAlias     = $queryBuilder->getRootAliases()[0];

        $queryBuilder->join("App:TplFolder", "t");
        $queryBuilder->andWhere(sprintf("t.id=%s.tplFolder", $rootAlias));

        $queryBuilder->join("App:Calendar", "c");
        $queryBuilder->join("App:TplFolderCalendar", "fc");
        $queryBuilder->andWhere(sprintf("c.id=fc.calendar"));
        if ($this->allTplFolders === false) {
            $queryBuilder->andWhere(sprintf("c.valid=1"));
        }
        $state = stateableEntity::getStateDraft();
        if ($this->operationName === "getdraft") {
            $queryBuilder->andWhere(sprintf("%s.createdBy='%s'", $rootAlias, $currentUserID));

        } else {
            $state = stateableEntity::getStateSubmitted();

            // user must have *at least* a rigth on the folder to know its existence
            $queryBuilder->join("App:TplFolderPermission", "p");
            $queryBuilder->andWhere(sprintf("p.tplFolder=t.id"));
//            $queryBuilder->andWhere(sprintf("p.right='%s'", TplFolderPermission::RIGHT_VIEW));
        }

        $queryBuilder->join("App:Questionnaire", "q");
        $queryBuilder->andWhere(sprintf("q.folder=%s.id", $rootAlias));

        $queryBuilder->andWhere(sprintf("%s.state='%s'", $rootAlias, $state));

        /*
         * use this to get sql query : $sql=$queryBuilder->getQuery()->getSQL();
         */
    }

    /**
     *
     * Add filter for TplFolder
     *      filter only submitted TplFolder
     *      filter only creatable TplFolder for user (user must have RIGHT_CREATE on this object =>
     *          table TplFolderPermission give ROLE/RIGHT/TplFolder)
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function tplFolderAddWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $userRoles = implode(
            ', ',
            array_map(function ($val) {
                return sprintf("'%s'", $val);
            }, $this->user->getRoles()));

        $queryBuilder->join("App:Calendar", "c");
        $queryBuilder->join("App:TplFolderCalendar", "fc");
        $queryBuilder->andWhere(sprintf("fc.tplFolder=%s.id", $rootAlias));
        $queryBuilder->andWhere(sprintf("c.id=fc.calendar"));
        if ($this->allTplFolders === false) {
            $queryBuilder->andWhere(sprintf("c.valid=1"));
        }

        // user must have *at least* a rigth on the tplFolder to know its existence
        $queryBuilder->join("App:TplFolderPermission", "p");
        $queryBuilder->andWhere(sprintf("p.tplFolder=%s.id", $rootAlias));
        $queryBuilder->andWhere(sprintf('p.role in (%s)', $userRoles));
        if (!empty($this->permissions_right)) {
            $queryBuilder->andWhere(sprintf("p.right='%s'", $this->permissions_right));
        }

        $queryBuilder->andWhere(sprintf("%s.state='%s'", $rootAlias, stateableEntity::getStateSubmitted()));
        $sql = $queryBuilder->getQuery()->getSQL();
        $a   = 1;
        /*
         * use this to get sql query : $sql=$queryBuilder->getQuery()->getSQL();
         */
    }

    /**
     *
     * add filters for Folder
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string                     $resourceClass
     */
    private function tplFolderPermissionAddWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $userRoles = implode(
            ', ',
            array_map(function ($val) {
                return sprintf("'%s'", $val);
            }, $this->user->getRoles()));

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere("p.roles IN (:roles)")->setParameter('roles', $userRoles);

//            $queryBuilder->andWhere(sprintf("p.role in '%s'", TplFolderPermission::RIGHT_VIEW));
        /*
         * use this to get sql query : $sql=$queryBuilder->getQuery()->getSQL();
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
        if ($this->user === null) {
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
