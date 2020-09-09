<?php
/*
 * Core
 * Folder.php
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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Traits\descriptionableEntity;
use App\Traits\gedmoableEntity;
use App\Traits\labelableEntity;
use App\Traits\resourceableEntity;
use App\Traits\scorableEntity;
use App\Traits\stateableEntity;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Folder
 *
 * @ORM\Table(name="Folders", indexes={@ORM\Index(name="fk_Folder_TplFolder_idx", columns={"TplFolder_id"})})
 * @ORM\Entity
 *
 * @ApiFilter(DateFilter::class, properties={"updatedAt"})
 * @ApiFilter(SearchFilter::class, properties={"tplFolder.id": "exact", "target": "exact", "parentTargets": "partial"})
 *
 */
class Folder
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
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Score"})
     */
    public $id;


    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

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
     * Add scoring field
     */
    use scorableEntity;

    /**
     * @var \DateTime
     * @ORM\Column(name="period_start", type="datetime", nullable=true)
     * @Groups({"Folder:Read"})
     */
    public $periodStart;

    /**
     * @var \DateTime
     * @ORM\Column(name="period_end", type="datetime", nullable=true)
     * @Groups({"Folder:Read"})
     */
    public $periodEnd;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=36,
     *   nullable=false, options={"comment"="user or group id in keycloak"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Score"})
     */
    private $target;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_targets", type="string", length=1000, nullable=false)
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Score"})
     */
    private $parentTargets;

    /**
     * @var tplFolder
     *
     * @ORM\ManyToOne(targetEntity="TplFolder", fetch="LAZY")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplFolder_id", referencedColumnName="id")
     * })
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     */
    private $tplFolder;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Questionnaire", mappedBy="folder", cascade={"persist"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Folder:Update"})
     * @Groups({"Score"})
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $questionnaires;

    public function __construct()
    {
        $this->questionnaires = new ArrayCollection();
    }

    public function addQuestionnaire(Questionnaire $questionnaire): self
    {
        if (!$this->questionnaires->contains($questionnaire)) {
            $this->questionnaires[] = $questionnaire;
            $questionnaire->setFolder($this);
        }

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPeriodEnd(): ?DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(?DateTimeInterface $periodEnd): self
    {
        $this->periodEnd = $periodEnd;

        return $this;
    }

    public function getPeriodStart(): ?DateTimeInterface
    {
        return $this->periodStart;
    }

    public function setPeriodStart(?DateTimeInterface $periodStart): self
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    /**
     * @return Collection|Questionnaire[]
     */
    public function getQuestionnaires(): Collection
    {
        return $this->questionnaires;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getParentTargets(): ?string
    {
        return $this->parentTargets;
    }

    public function setParentTargets(string $parentTargets): self
    {
        $this->parentTargets = $parentTargets;

        return $this;
    }

    public function getTplFolder(): ?TplFolder
    {
        return $this->tplFolder;
    }

    public function setTplFolder(?TplFolder $tplFolder): self
    {
        $this->tplFolder = $tplFolder;

        return $this;
    }

    public function removeQuestionnaire(Questionnaire $questionnaire): self
    {
        if ($this->questionnaires->contains($questionnaire)) {
            $this->questionnaires->removeElement($questionnaire);
            // set the owning side to null (unless already changed)
            if ($questionnaire->getFolder() === $this) {
                $questionnaire->setFolder(null);
            }
        }

        return $this;
    }
}
