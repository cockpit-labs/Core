<?php
/*
 * Core
 * AnswerValue.php
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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * AnswerValue
 *
 * @ORM\Table(name="AnswerValue")
 * @ORM\Entity
 *
 */
class AnswerValue
{
    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @Groups({"AnswerValue:Read"})
     * @Groups({"AnswerValue:Update"})
     * @Groups({"Folder:Update"})
     */
    public $id;

    /**
     * @var \App\Entity\QuestionAnswer
     *
     * @ORM\ManyToOne(targetEntity="QuestionAnswer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="questionanswer_id", referencedColumnName="id")
     * })
     *
     * @Groups({"AnswerValue:Read"})
     */
    private $questionAnswer;

    /**
     * @var \App\Entity\QuestionChoice
     *
     * @ORM\ManyToOne(targetEntity="QuestionChoice")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="questionchoice_id", referencedColumnName="id")
     * })
     *
     * @Groups({"AnswerValue:Read"})
     * @Groups({"AnswerValue:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $choice;

    /**
     * Many answers can have many photos
     *
     * @var \App\Entity\UserMedia|null
     *
     * @ORM\ManyToOne(targetEntity="UserMedia")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="media_id", referencedColumnName="id")
     * })
     *
     * @Groups({"AnswerValue:Read"})
     * @Groups({"AnswerValue:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $media;

    /**
     * @var string
     * @ORM\Column(name="value", type="string", nullable=true)
     *
     * @Groups({"AnswerValue:Read"})
     * @Groups({"AnswerValue:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     */
    private $value;

    /**
     * @var string
     * @ORM\Column(name="rawValue", type="string", nullable=true)
     *
     * @Groups({"AnswerValue:Read"})
     * @Groups({"AnswerValue:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     */
    private $rawValue;

    /**
     * @var string
     * @ORM\Column(name="formula", type="json", length=255, nullable=true)
     *
     * @Groups({"AnswerValue:Read"})
     * @Groups({"AnswerValue:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Block:Read"})
     * @Groups({"Block:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"QuestionAnswer:Read"})
     * @Groups({"QuestionAnswer:Update"})
     */
    private $valueFormula;

    /**
     * @return \App\Entity\QuestionChoice
     */
    public function getChoice(): ?QuestionChoice
    {
        return $this->choice;
    }

    /**
     * @param QuestionChoice $choice
     */
    public function setChoice(?QuestionChoice $choice): self
    {
        $this->choice = $choice;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return UserMedia|null
     */
    public function getMedia(): ?UserMedia
    {
        return $this->media;
    }

    /**
     * @param UserMedia|null $media
     */
    public function setMedia(?UserMedia $media): self
    {
        $this->media = $media;
        return $this;
    }

    /**
     * @return QuestionAnswer
     */
    public function getQuestionAnswer(): ?QuestionAnswer
    {
        return $this->questionAnswer;
    }

    /**
     * @param \App\Entity\QuestionAnswer $questionAnswer
     */
    public function setQuestionAnswer(?QuestionAnswer $questionAnswer): self
    {
        $this->questionAnswer = $questionAnswer;

        return $this;
    }

    /**
     * @return string
     */
    public function getRawValue(): ?string
    {
        return $this->rawValue;
    }

    /**
     * @param string $rawValue
     */
    public function setRawValue(?string $rawValue): self
    {
        $this->rawValue = $rawValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;
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
