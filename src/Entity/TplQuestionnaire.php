<?php
/*
 * Core
 * TplQuestionnaire.php
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
use App\Traits\descriptionableEntity;
use App\Traits\gedmoableEntity;
use App\Traits\labelableEntity;
use App\Traits\resourceableEntity;
use App\Traits\stateableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

// gedmo annotations

/**
 * TplQuestionnaire
 *
 * @ORM\Table(
 *     name="TplQuestionnaires",
 *     indexes={
 *      @ORM\Index(name="TplQuestionnairedeleted_idx", columns={"deleted_at"})
 *     }
 * )
 * @ORM\Entity
 *
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
class TplQuestionnaire
{
    /**
     * add group (Timestamp and Blame) for TimestampableEntity and BlameableEntity
     */
    use GedmoableEntity;

    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    /**
     * Hook SoftDeleteable behavior
     * updates deletedAt field
     */
    use SoftDeleteableEntity;

    /**
     * add a state field
     */
    use stateableEntity;

    /**
     * add a label field
     */
    use labelableEntity;

    /**
     * add a description field
     */
    use descriptionableEntity;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     */
    public $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="TplFolder", mappedBy="tplQuestionnaires")
     *
     * @Groups({"TplQuestionnaire:Read"})
     */
    private $tplFolders;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="TplQuestionnaireBlock",
     *     mappedBy="tplQuestionnaire",cascade={"persist"},
     *     fetch="EAGER")
     * @ORM\OrderBy({"position" = "ASC"})
     *
     * @Groups({"TplQuestionnaire:Update"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $tplQuestionnaireBlocks;


    /**
     * @var array<\App\Entity\TplBlock>
     *
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     */
    public $tplBlocks;

    public function __construct()
    {
        $this->tplFolders             = new ArrayCollection();
        $this->tplBlocks              = new ArrayCollection();
        $this->tplQuestionnaireBlocks = new ArrayCollection();
    }

    public function addTplBlock(TplBlock $tplBlock): self
    {
        if (empty($this->tplBlocks)) {
            $this->tplBlocks = new ArrayCollection();
        }
        if (!$this->tplBlocks->contains($tplBlock)) {
            $this->tplBlocks->add($tplBlock);
        }
        return $this;
    }

    public function addTplFolder(TplFolder $tplFolder): self
    {
        if (!$this->tplFolders->contains($tplFolder)) {
            $this->tplFolders->add($tplFolder);
            $tplFolder->addTplQuestionnaire($this);
        }

        return $this;
    }

    public function addTplQuestionnaireBlocks(TplQuestionnaireBlock $tplQuestionnaireBlock): self
    {
        if (!$this->tplQuestionnaireBlocks->contains($tplQuestionnaireBlock)) {
            $tplQuestionnaireBlock->setTplQuestionnaire($this);
            $this->tplQuestionnaireBlocks[] = $tplQuestionnaireBlock;
        }

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return Collection|TplBlock[]
     */
    public function getTplBlocks(): ArrayCollection
    {
        if (empty($this->tplBlocks)) {
            $this->tplBlocks = new ArrayCollection();
        }
        // sort tplBlocks by position
        $iterator = $this->tplBlocks->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getPosition() < $b->getPosition()) ? -1 : 1;
        });
        $this->tplBlocks = new ArrayCollection(array_values(iterator_to_array($iterator)));
        return $this->tplBlocks;
    }

    /**
     * @return Collection|TplFolder[]
     */
    public function getTplFolders(): Collection
    {
        return $this->tplFolders;
    }

    /**
     * @return Collection|TplQuestionnaireBlocks[]
     */
    public function getTplQuestionnaireBlocks(): Collection
    {
        return $this->tplQuestionnaireBlocks;
    }

    public function removeTplBlock(TplBlock $tplBlock): self
    {
        if (empty($this->tplBlocks)) {
            $this->tplBlocks = new ArrayCollection();
        }
        if ($this->tplBlocks->contains($tplBlock)) {
            $this->tplBlocks->removeElement($tplBlock);
        }

        return $this;
    }

    public function removeTplFolder(TplFolder $tplFolder): self
    {
        if ($this->tplFolders->contains($tplFolder)) {
            $this->tplFolders->removeElement($tplFolder);
            $tplFolder->removeTplQuestionnaire($this);
        }

        return $this;
    }

    public function removeTplQuestionnaireBlocks(TplQuestionnaireBlock $tplQuestionnaireBlock): self
    {
        if ($this->tplQuestionnaireBlocks->contains($tplQuestionnaireBlock)) {
            $this->tplQuestionnaireBlocks->removeElement($tplQuestionnaireBlock);
            // set the owning side to null (unless already changed)
            if ($tplQuestionnaireBlock->getTplQuestionnaire() === $this) {
                $tplQuestionnaireBlock->setTplQuestionnaire(null);
            }
        }

        return $this;
    }
}
