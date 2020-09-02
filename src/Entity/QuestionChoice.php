<?php
/*
 * Core
 * QuestionChoice.php
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
use App\Traits\labelableEntity;
use App\Traits\resourceableEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * QuestionChoice
 *
 * @ORM\Table(name="QuestionChoices")
 * @ORM\Entity
 *
 */
class QuestionChoice
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
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"Folder:Create"})
     * @Groups({"Folder:Read"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"AnswerValue:Read"})
     */
    public $id;
    /**
     * @var TplMedia|null
     *
     * @ORM\OneToOne(targetEntity="TplMedia", cascade={"remove"})
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id", nullable=true)
     * ApiProperty(iri="http://schema.org/image")
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"Folder:Create"})
     */
    public $media;
    /**
     * @var int
     * @ORM\Column(name="position", type="integer", nullable=false, options={"default" : 1})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"Folder:Create"})
     *
     */
    private $position = 1;
    /**
     * this field is filled with a formula to calculate the value of the choice, ie
     *  =10
     *
     * @ORM\Column(name="value_formula", type="json", length=255, nullable=false)
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplQuestion:Read"})
     * @Groups({"TplQuestion:Update"})
     * @Groups({"TplBlock:Read"})
     * @Groups({"TplBlock:Update"})
     * @Groups({"Folder:Create"})
     *
     */
    private $valueFormula;

    /**
     * @var TplQuestion
     *
     * @ORM\ManyToOne(targetEntity="TplQuestion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplquestion_id", referencedColumnName="id")
     * })
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplQuestion:Update"})
     * @Groups({"TplBlock:Update"})
     */
    private $tplQuestion;

    public function getMedia(): ?TplMedia
    {
        return $this->media;
    }

    public function setMedia(?TplMedia $media): self
    {
        $this->media = $media;

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
    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getTplQuestion(): ?TplQuestion
    {
        return $this->tplQuestion;
    }

    public function setTplQuestion(?TplQuestion $tplQuestion): self
    {
        $this->tplQuestion = $tplQuestion;

        return $this;
    }

    public function getValueFormula(): ?array
    {
        return $this->valueFormula;
    }

    public function setValueFormula(array $valueFormula): self
    {
        $this->valueFormula = $valueFormula;

        return $this;
    }
}
