<?php
/*
 * Core
 * TplFolderPermission.php
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

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use App\CentralAdmin\KeycloakConnector;
use App\Traits\resourceableEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * TplFolderPermission
 *
 *  *
 *  uniqueConstraints={@ORM\UniqueConstraint(name="unique_permission_idx", columns={"rightlevel", "role",
 *  "tplFolder_id"})}
 * @ORM\Table(
 *     name="TplFolderPermissions",
 * )
 * @UniqueEntity(
 *      fields={"right","role", "tplFolder"},
 *      message="permission already exists in database."
 * )
 *
 * @ORM\Entity
 *
 */
class TplFolderPermission
{
    public const RIGHT_CREATE   = 'CREATE';
    public const RIGHT_UPDATE   = 'UPDATE';
    public const RIGHT_ANNOTATE = 'ANNOTATE';
    public const RIGHT_VALIDATE = 'VALIDATE';
    public const RIGHT_SUBMIT   = 'SUBMIT';
    public const RIGHT_DELETE   = 'DELETE';
    public const RIGHT_VIEW     = 'VIEW';
    public const RIGHT_STATS    = 'STATS';


    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     * @Groups({"TplFolderPermission:Read"})
     * @Groups({"TplFolderPermission:Update"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     */
    public $id;


    /**
     * @var string|null
     *
     * @ORM\Column(name="rightlevel", type="string", length=40, nullable=true)
     * @Groups({"TplFolderPermission:Read"})
     * @Groups({"TplFolderPermission:Update"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     */
    private $right;

    /**
     * @var string|null
     *
     * @ORM\Column(name="role", type="string", length=255, nullable=true)
     * @Groups({"TplFolderPermission:Read"})
     * @Groups({"TplFolderPermission:Update"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     */
    private $role;

    /**
     * @var tplFolder
     *
     * @ORM\ManyToOne(targetEntity="TplFolder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplFolder_id", referencedColumnName="id")
     * })
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"TplFolder:Update"})
     * @Groups({"TplFolderPermission:Read"})
     * @Groups({"TplFolderPermission:Update"})
     */
    private $tplFolder;

    public function getId()
    {
        return $this->id;
    }

    public function getRight(): ?string
    {
        return $this->right;
    }

    public function setRight(?string $right): self
    {
        $this->right = $right;

        return $this;
    }

    public function getRole(): ?string
    {
        return KeycloakConnector::toKeycloakRole($this->role);
    }

    public function setRole(?string $role): self
    {
        $this->role = KeycloakConnector::toSymfonyRole($role);

        return $this;
    }

    public function getTplFolder(): ?TplFolder
    {
        return $this->tplFolder;
    }

    public function setTplFolder(?TplFolder $tplFolder): self
    {
        $this->tplFolder = $tplFolder;

        return $this;
    }
}
