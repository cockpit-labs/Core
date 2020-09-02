<?php
/*
 * Core
 * Block.php
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
use App\Traits\labelableEntity;
use App\Traits\resourceableEntity;
use App\Traits\scorableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Block
 *
 * @ORM\Table(name="Blocks")
 * @ORM\Entity
 *
 */
class Block
{
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
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"Score"})
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="tplBlock_id", type="string", nullable=false)
     *
     * @Groups({"Folder:Read"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"Score"})
     */
    private $tplBlockId = 0;

    /**
     * @var int
     * @ORM\Column(name="position", type="integer", nullable=false, options={"default" : 1})
     *
     * @Groups({"Folder:Read"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     *
     */
    private $position = 1;

    /**
     * @var Questionnaire
     *
     * @ORM\ManyToOne(targetEntity="Questionnaire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="questionnaire_id", referencedColumnName="id")
     * })
     *
     * @Groups({"Block:Read"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $questionnaire;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(targetEntity="QuestionAnswer", mappedBy="block", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"Score"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $questionAnswers;

    public function __construct()
    {
        $this->questionAnswers = new ArrayCollection();
    }

    public function addQuestionAnswer(QuestionAnswer $questionAnswer): self
    {
        if (!$this->questionAnswers->contains($questionAnswer)) {
            $this->questionAnswers[] = $questionAnswer;
            $questionAnswer->setBlock($this);
        }

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return Collection|\App\Entity\QuestionAnswer[]
     */
    public function getQuestionAnswers(): Collection
    {
        return $this->questionAnswers;
    }

    public function getQuestionnaire(): ?Questionnaire
    {
        return $this->questionnaire;
    }

    public function setQuestionnaire(?Questionnaire $questionnaire): self
    {
        $this->questionnaire = $questionnaire;

        return $this;
    }

    /**
     * @return string
     */
    public function getTplBlockId(): string
    {
        return $this->tplBlockId;
    }

    public function removeQuestionAnswer(QuestionAnswer $questionAnswer): self
    {
        if ($this->questionAnswers->contains($questionAnswer)) {
            $this->questionAnswers->removeElement($questionAnswer);
            // set the owning side to null (unless already changed)
            if ($questionAnswer->getBlock() === $this) {
                $questionAnswer->setBlock(null);
            }
        }

        return $this;
    }

    /**
     * @param string $tplBlockId
     */
    public function setTplBlockId(string $tplBlockId): void
    {
        $this->tplBlockId = $tplBlockId;
    }
}
