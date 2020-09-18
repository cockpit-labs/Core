<?php
/*
 * Core
 * Questionnaire.php
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
use App\Traits\scorableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evence\Bundle\SoftDeleteableExtensionBundle\Mapping\Annotation as Evence;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Questionnaire
 *
 * @ORM\Table(name="Questionnaires", indexes={@ORM\Index(name="fk_Questionnaire_TplQuestionnaire_idx",
 *   columns={"TplQuestionnaire_id"}), @ORM\Index(name="fk_Questionnaire_Folder_idx", columns={"Folder_id"})})
 * @ORM\Entity
 * ORM\HasLifecycleCallbacks()
 */
class Questionnaire
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
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    /**
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

    /**
     * add a label field
     */
    use labelableEntity;

    /**
     * add a description field
     */
    use descriptionableEntity;

    /**
     * Add scoring field
     */
    use scorableEntity;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @Groups({"Submit"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     */
    public $id;
    /**
     * @var QuestionnairePDFMedia|null
     *
     * @ORM\OneToOne(targetEntity="QuestionnairePDFMedia", cascade={"remove"})
     * @ORM\JoinColumn(name="pdf_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @Groups({"Submit"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    public $pdf;
    /**
     * @var Folder
     *
     * @ORM\ManyToOne(targetEntity="Folder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="folder_id", referencedColumnName="id")
     * })
     * @Groups({"Questionnaire:Read"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $folder;
    /**
     * @var TplQuestionnaire
     *
     * @ORM\ManyToOne(targetEntity="TplQuestionnaire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplQuestionnaire_id", referencedColumnName="id")
     * })
     *
     * @Groups({"Folder:Read"})
     * @Groups({"Questionnaire:Read"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $tplQuestionnaire;
    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(targetEntity="Block", mappedBy="questionnaire", cascade={"persist"})
     *
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Score"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $blocks;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }

    public function addBlock(Block $block): self
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks[] = $block;
            $block->setQuestionnaire($this);
        }

        return $this;
    }

    /**
     * @return Collection|Block[]
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPdf(): ?QuestionnairePDFMedia
    {
        return $this->pdf;
    }

    public function setPdf(?QuestionnairePDFMedia $pdf): self
    {
        $this->pdf = $pdf;

        return $this;
    }

    public function getTplQuestionnaire(): ?TplQuestionnaire
    {
        return $this->tplQuestionnaire;
    }

    public function setTplQuestionnaire(?TplQuestionnaire $tplQuestionnaire): self
    {
        $this->tplQuestionnaire = $tplQuestionnaire;
        return $this;
    }

    public function removeBlock(Block $block): self
    {
        if ($this->block->contains($block)) {
            $this->block->removeElement($block);
            // set the owning side to null (unless already changed)
            if ($block->getQuestionnaire() === $this) {
                $block->setQuestionnaire(null);
            }
        }

        return $this;
    }


}
