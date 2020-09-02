<?php
/*
 * Core
 * QuestionAnswer.php
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
use App\Traits\gedmoableEntity;
use App\Traits\scorableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * QuestionAnswer
 *
 * @ORM\Table(name="QuestionAnswer")
 * @ORM\Entity
 */
class QuestionAnswer
{
    /**
     * add group (Timestamp and Blame) for TimestampableEntity and BlameableEntity
     */
    use GedmoableEntity;

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

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
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"QuestionAnswer:Read"})
     */
    public $id;

    /**
     * @ORM\Column(name="question", type="json", nullable=false)
     * Assert\Json(message = "This is not valid JSON")
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"QuestionAnswer:Read"})
     */
    private $question;

    /**
     * @var int|null
     * @Assert\Range(min = 0, max = 100)
     *
     * @ORM\Column(name="weight", type="integer", nullable=true, options={"unsigned"=true})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"QuestionAnswer:Read"})
     */
    private $weight;

    /**
     * @var \Doctrine\Common\Collections\Collection|\App\Entity\AnswerValue
     * @ORM\OneToMany(targetEntity="AnswerValue", mappedBy="questionAnswer", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $answerValues;

    /**
     * One QuestionAnswer has Many Sub QuestionAnswers.
     * @ORM\OneToMany(targetEntity="QuestionAnswer", mappedBy="parent", cascade={"persist"})
     * @ApiProperty()
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"QuestionAnswer:Read"})
     */
    private $children;

    /**
     * Many QuestionAnswers have One parent QuestionAnswer.
     *
     * @var QuestionAnswer
     * @ORM\ManyToOne(targetEntity="QuestionAnswer", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", nullable=true, referencedColumnName="id")
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"QuestionAnswer:Read"})
     */
    private $parent;

    /**
     * @var Block
     *
     * @ORM\ManyToOne(targetEntity="Block")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="block_id", referencedColumnName="id")
     * })
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"Folder:Create"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     */
    private $block;

    /**
     * @var string
     * @ORM\Column(name="comment", type="text", nullable=true)
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     */
    private $comment;

    /**
     * Many answers can have many photos
     *
     * @var UserMedia|null
     *
     * @ORM\ManyToMany(targetEntity="UserMedia")
     * @ORM\JoinTable(name="QuestionAnwers_Media",
     *      joinColumns={@ORM\JoinColumn(name="questionAnswer_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="media_id", referencedColumnName="id", unique=true)}
     *      )
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     */
    private $photos;

    public function __construct()
    {
        $this->photos       = new ArrayCollection();
        $this->children     = new ArrayCollection();
        $this->answerValues = new ArrayCollection();
    }

    public function addChild(QuestionAnswer $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    /**
     * @param \App\Entity\UserMedia $photo
     *
     * @return $this
     *
     * @todo control number of photos vs maxphoto in TplQuestion with custom constraint/validator
     */
    public function addPhoto(UserMedia $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
        }

        return $this;
    }

    /**
     * @return Collection|AnswerValue[]
     *          why getValues?
     *          check that => https://api-platform.com/docs/core/serialization/#collection-relation
     */
    public function getAnswerValues()
    {
        return $this->answerValues->getValues();
    }

    /**
     * @param \AnswerValue $answerValues
     *
     * @return $this
     */
    public function addAnswerValue(AnswerValue $answerValue): self
    {
        if (!$this->answerValues->contains($answerValue)) {
            $this->answerValues[] = $answerValue;
            $answerValue->setQuestionAnswer($this);
        }

        return $this;
    }

    public function getBlock(): ?Block
    {
        return $this->block;
    }

    public function setBlock(?Block $block): self
    {
        $this->block = $block;

        return $this;
    }

    /**
     * @return Collection|QuestionAnswer[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|UserMedia[]
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function getQuestion(): ?array
    {
        return $this->question;
    }

    public function setQuestion(array $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function removeAnswerValue(AnswerValue $answerValue): self
    {
        if ($this->answerValues->contains($answerValue)) {
            $this->answerValues->removeElement($answerValue);
            // set the owning side to null (unless already changed)
            if ($answerValue->getQuestionAnswer() === $this) {
                $answerValue->setQuestionAnswer(null);
            }
        }

        return $this;
    }

    public function removeChild(QuestionAnswer $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @param \App\Entity\UserMedia $photo
     *
     * @return $this
     */
    public function removePhoto(UserMedia $photo): self
    {
        if ($this->photos->contains($photo)) {
            $this->photos->removeElement($photo);
        }

        return $this;
    }


}
