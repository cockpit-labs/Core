<?php
/*
 * Core
 * MediaOwner.php
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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * MediaOwner
 *
 * @ORM\Table(name="MediaOwners")
 * @ORM\Entity(repositoryClass="App\Repository\MediaOwnerRepository")
 *
 */
class MediaOwner
{

    /**
     *
     * @ORM\Id
     * @var string
     * @ORM\Column(name="owner", type="guid", nullable=false)
     * @Groups({"Media"})
     * @Groups({"Folder:Update"})
     */
    public $owner;

    /**
     * @ORM\Id
     * @var Media|null
     *
     * @ORM\ManyToOne(targetEntity="Media", inversedBy="owners", cascade={"remove"})
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id")
     * ApiProperty(iri="http://schema.org/image")
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"Folder:Update"})
     */
    public $media;

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
