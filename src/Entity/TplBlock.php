<?php
/*
 * Core
 * TplBlock.php
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

use App\Traits\descriptionableEntity;
use App\Traits\labelableEntity;
use App\Traits\resourceableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * TplBlock
 *
 * @ORM\Table(
 *     name="TplBlocks",
 *     indexes={
 *      @ORM\Index(name="TplBlockdeleted_idx", columns={"deleted_at"})
 *     }
 * )
 * @ORM\Entity
 *
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
class TplBlock
{
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
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

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
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     */
    public $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(targetEntity="TplQuestion", mappedBy="tplBlock", cascade={"persist"}, fetch="EAGER")
     *
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     */
    private $tplQuestions;

    /**
     * @var int
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     */
    private $position = 0;

    public function __construct()
    {
        $this->tplQuestions = new ArrayCollection();
        $this->tplQuestionnaires = new ArrayCollection();
        $this->tplQuestionnaireBlock = new ArrayCollection();
    }

    public function addTplQuestion(TplQuestion $tplQuestion): self
    {
        if (!$this->tplQuestions->contains($tplQuestion)) {
            $this->tplQuestions->add($tplQuestion);
            $tplQuestion->setTplBlock($this);
        }

        return $this;
    }

    public function addTplQuestionnaire(TplQuestionnaire $tplQuestionnaire): self
    {
        if (!$this->tplQuestionnaires->contains($tplQuestionnaire)) {
            $this->tplQuestionnaires->add($tplQuestionnaire);
            $tplQuestionnaire->addTplBlock($this);
        }

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getTplQuestionnaireBlock(): ?TplQuestionnaireBlock
    {
        return $this->tplQuestionnaireBlock;
    }

    /**
     * @param string $tplQuestionnaireBlock
     */
    public function setTplQuestionnaireBlock(string $tplQuestionnaireBlock): void
    {
        $this->tplQuestionnaireBlock = $tplQuestionnaireBlock;
    }

    /**
     * @return Collection|\App\Entity\TplQuestionnaire[]
     */
    public function getTplQuestionnaires(): Collection
    {
        return $this->tplQuestionnaires;
    }

    /**
     * @return Collection|TplQuestion[]
     */
    public function getTplQuestions(): Collection
    {
        // keep only parent questions in list
        $newlist = [];
        foreach ($this->tplQuestions as $tplQuestion) {
            if (empty($tplQuestion->getParent())) {
                $newlist[] = $tplQuestion;
            }
        }
        return new ArrayCollection(array_values($newlist));
    }

    public function removeTplQuestion(TplQuestion $tplQuestion): self
    {
        if ($this->tplQuestions->contains($tplQuestion)) {
            $this->tplQuestions->removeElement($tplQuestion);
            // set the owning side to null (unless already changed)
            if ($tplQuestion->getTplBlock() === $this) {
                $tplQuestion->setTplBlock(null);
            }
        }

        return $this;
    }

    public function removeTplQuestionnaire(TplQuestionnaire $tplQuestionnaire): self
    {
        if ($this->tplQuestionnaires->contains($tplQuestionnaire)) {
            $this->tplQuestionnaires->removeElement($tplQuestionnaire);
            // set the owning side to null (unless already changed)
            if ($tplQuestionnaire->getTplBlocks()->contains($this)) {
                $tplQuestionnaire->removeTplBlock($this);
            }
        }

        return $this;
    }
}
