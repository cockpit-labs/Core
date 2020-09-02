<?php
/*
 * Core
 * Target.php
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
use App\Traits\resourceableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Target
 *
 */
class Target
{

    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * Unique identifier for the object.
     * @ApiProperty(identifier=true)
     *
     * @var string | null
     *
     * @Groups({"Target:Read"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     */
    public $id;

    /**
     * @var string
     *
     * @Groups({"Target:Read"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     *
     */
    private $parent;

    /**
     * @var string
     *
     * @Groups({"Target:Read"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     *
     */
    private $name;

    /**
     * @var string
     *
     * @Groups({"Target:Read"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     *
     */
    private $type;

    /**
     * @var array
     *
     * @Groups({"Target:Read"})
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     */
    private $rights;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(?string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getRights(): ?array
    {
        return $this->rights;
    }

    public function setRights(array $rights): self
    {
        $this->rights = $rights;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
