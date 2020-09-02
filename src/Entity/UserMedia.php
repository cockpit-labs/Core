<?php
/*
 * Core
 * UserMedia.php
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 */
class UserMedia extends Media
{
    /**
     * @var array|null
     * @ORM\OneToMany(targetEntity="MediaOwner", mappedBy="media", cascade={"remove"})
     * @Groups({"Folder:Update"})
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $owners;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
    }

    public function addOwner(MediaOwner $owner): self
    {
        if (!$this->owners->contains($owner)) {
            $this->owners[] = $owner;
            $owner->setMedia($this);
        }

        return $this;
    }

    /**
     * @return Collection|MediaOwner[]
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function removeOwner(MediaOwner $owner): self
    {
        if ($this->owners->contains($owner)) {
            $this->owners->removeElement($owner);
            // set the owning side to null (unless already changed)
            if ($owner->getMedia() === $this) {
                $owner->setMedia(null);
            }
        }

        return $this;
    }
}
