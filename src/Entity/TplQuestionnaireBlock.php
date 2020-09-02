<?php
/*
 * Core
 * TplQuestionnaireBlock.php
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
 * TplQuestionnaireBlock
 *
 * @ORM\Table(name="TplQuestionnaires_Blocks")
 * @ORM\Entity
 *
 *
 */
class TplQuestionnaireBlock
{
    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * @var int
     * @ORM\Column(name="position", type="integer", length=255, nullable=false, options={"default" : 1})
     *
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaire:Update"})
     * @Groups({"TplQuestionnaireBlock:Read"})
     * @Groups({"TplQuestionnaireBlock:Update"})
     */
    private $position = 1;

    /**
     * @var tplQuestionnaire
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity="TplQuestionnaire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplQuestionnaire_id", referencedColumnName="id")
     * })
     *
     * @Groups({"TplQuestionnaireBlock:Read"})
     * @Groups({"TplQuestionnaireBlock:Update"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $tplQuestionnaire;

    /**
     * @var tplBlock
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity="TplBlock")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplBlock_id", referencedColumnName="id")
     * })
     *
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplQuestionnaireBlock:Update"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $tplBlock;

    /**
     * @return \phpDocumentor\Reflection\Types\String_
     */
    public function __toString(): string
    {
        return $this->getTplBlock()->getLabel();
    }

    public function getId(): string
    {
        return 'tplQuestionnaire=' . $this->getTplQuestionnaire()->getId() . ';' .
            'tplBlock=' . $this->getTplBlock()->getId();
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

    public function getTplBlock(): ?TplBlock
    {
        return $this->tplBlock;
    }

    public function setTplBlock(?TplBlock $tplBlock): self
    {
        $this->tplBlock = $tplBlock;

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
}
