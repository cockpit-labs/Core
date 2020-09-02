<?php
/*
 * Core
 * TplQuestion.php
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evence\Bundle\SoftDeleteableExtensionBundle\Mapping\Annotation as Evence;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TplQuestion
 *
 * @ORM\Table(
 *     name="TplQuestions",
 *     indexes={
 *          @ORM\Index(name="TplQuestiondeleted_idx", columns={"deleted_at"}),
 *          @ORM\Index(name="fk_question_tplBlock_idx", columns={"tplBlock_id"}),
 *          @ORM\Index(name="fk_Question_category_idx", columns={"category_id"})
 *     }
 * )
 * @ORM\Entity()
 *
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
class TplQuestion
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
     * @var TplMedia|null
     *
     * @ORM\OneToOne(targetEntity="TplMedia", cascade={"remove"})
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @Evence\onSoftDelete(type="CASCADE")
     *
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    public $document;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    public $id;

    /**
     * @var string|null
     * @ORM\Column(name="alias", type="string", length=255, nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     *
     */
    private $alias;

    /**
     * @var int|null
     * @Assert\Range(min = 0, max = 100)
     *
     * @ORM\Column(name="weight", type="integer", nullable=true, options={"unsigned"=true}, options={"default" : 0})
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $weight;

    /**
     * @ORM\Column(name="read_renderer", type="json", length=500, nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $readRenderer;

    /**
     * @ORM\Column(name="write_renderer", type="json", length=500, nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $writeRenderer;

    /**
     * @ORM\Column(name="validator", type="json", length=255, nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $validator;

    /**
     * @ORM\Column(name="open_trigger", type="json", length=500, nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     *
     */
    private $trigger;

    /**
     * @var int
     * @Assert\PositiveOrZero
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $position = 0;

    /**
     * @var bool|null
     * @Assert\Type("bool")
     *
     * @ORM\Column(name="hiddenlabel", type="boolean", nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $hiddenLabel = false;

    /**
     * @var bool|null
     * @Assert\Type("bool")
     *
     * @ORM\Column(name="mandatory", type="boolean", nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $mandatory = false;

    /**
     * @var bool|null
     * @Assert\Type("bool")
     *
     * @ORM\Column(name="comment", type="boolean", nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $hasComment = false;

    /**
     * @var int
     * @Assert\Range(min = 0, max = 10)
     *
     * @ORM\Column(name="max_photos", type="integer", nullable=false, options={"default" : 0})
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $maxPhotos = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="min_choices", type="integer", nullable=false, options={"default" : 0})
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $minChoices = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="max_choices", type="integer", nullable=false, options={"default" : 0})
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $maxChoices = 0;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $category;

    /**
     * @var TplBlock
     *
     * @ORM\ManyToOne(targetEntity="TplBlock")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplBlock_id", referencedColumnName="id")
     * })
     *
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $tplBlock;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="QuestionChoice", mappedBy="tplQuestion", cascade={"persist"})
     * @Assert\NotNull()
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $choices;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToOne(targetEntity="QuestionChoice")
     * @ORM\JoinColumn(name="defaultchoice_id", referencedColumnName="id", nullable=true)
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $defaultChoice;

    /**
     * One TplQuestion has Many Sub Questions.
     * @ORM\OneToMany(targetEntity="TplQuestion", mappedBy="parent", cascade={"persist"})
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     */
    private $children;

    /**
     * Many Questions have One parent TplQuestion.
     *
     * @var TplQuestion
     * @ORM\ManyToOne(targetEntity="TplQuestion", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", nullable=true, referencedColumnName="id")
     *
     * @Groups({"Folder:Create"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $parent;

    public function __construct()
    {
        $this->choices  = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function addChild(TplQuestion $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function addChoice(QuestionChoice $choice): self
    {
        if (!$this->choices->contains($choice)) {
            $this->choices[] = $choice;
            $choice->setTplQuestion($this);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection|TplQuestion[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return int
     */
    public function getMaxChoices(): int
    {
        return $this->maxChoices;
    }

    /**
     * @return int
     */
    public function getMinChoices(): int
    {
        return $this->minChoices;
    }

    public function getTrigger(): ?array
    {
        return $this->trigger;
    }

    public function setTrigger(?array $trigger): self
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * @return Collection|QuestionChoice[]
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function getDefaultChoice(): ?QuestionChoice
    {
        return $this->defaultChoice;
    }

    public function setDefaultChoice(?QuestionChoice $defaultChoice): self
    {
        $this->defaultChoice = $defaultChoice;

        return $this;
    }

    public function getDocument(): ?TplMedia
    {
        return $this->document;
    }

    public function setDocument(?TplMedia $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getHasComment(): ?bool
    {
        return $this->hasComment;
    }

    public function setHasComment(?bool $hasComment): self
    {
        $this->hasComment = $hasComment;

        return $this;
    }

    public function getHiddenLabel(): ?bool
    {
        return $this->hiddenLabel;
    }

    public function setHiddenLabel(?bool $hiddenLabel): self
    {
        $this->hiddenLabel = $hiddenLabel;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMandatory(): ?bool
    {
        return $this->mandatory;
    }

    public function setMandatory(?bool $mandatory): self
    {
        $this->mandatory = $mandatory;

        return $this;
    }

    public function getMaxPhotos(): ?int
    {
        return $this->maxPhotos;
    }

    public function setMaxPhotos(int $maxPhotos): self
    {
        $this->maxPhotos = $maxPhotos;

        return $this;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getReadRenderer(): ?array
    {
        return $this->readRenderer;
    }

    public function setReadRenderer(?array $readRenderer): self
    {
        $this->readRenderer = $readRenderer;

        return $this;
    }

    public function getTplBlock(): ?TplBlock
    {
        return $this->tplBlock;
    }

    public function setTplBlock(?TplBlock $tplBlock): self
    {
        $this->tplBlock = $tplBlock;

        return $this;
    }

    public function getValidator(): ?array
    {
        return $this->validator;
    }

    public function setValidator(?array $validator): self
    {
        $this->validator = $validator;

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

    public function getWriteRenderer(): ?array
    {
        return $this->writeRenderer;
    }

    public function setWriteRenderer(?array $writeRenderer): self
    {
        $this->writeRenderer = $writeRenderer;

        return $this;
    }

    public function removeChild(TplQuestion $child): self
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

    public function removeChoice(QuestionChoice $choice): self
    {
        if ($this->choices->contains($choice)) {
            $this->choices->removeElement($choice);
            // set the owning side to null (unless already changed)
            if ($choice->getTplQuestion() === $this) {
                $choice->setTplQuestion(null);
            }
        }

        return $this;
    }

    /**
     * @param int $minChoices
     */
    public function setMinChoices(int $minChoices): self
    {
        $this->minChoices = $minChoices;
        return $this;
    }

    /**
     * @param string|null $alias
     */
    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @param int $maxChoices
     */
    public function setMaxChoices(int $maxChoices): self
    {
        $this->maxChoices = $maxChoices;
        return $this;
    }
}
