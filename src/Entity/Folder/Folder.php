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
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace App\Entity\Folder;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\Calendar;
use App\Entity\Questionnaire\Questionnaire;
use App\Traits\traceableEntity;
use App\Traits\resourceableEntity;
use App\Traits\scorableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Folder
 *
 * @ORM\Entity
 *
 * @ApiFilter(DateFilter::class, properties={"updatedAt"})
 * @ApiFilter(SearchFilter::class, properties={"folderTpl.id": "exact", "target": "exact", "parentTargets": "partial"})
 *
 */
class Folder extends FolderBase
{

    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * Add scoring field
     * a method named 'getChildEntities' must exists to process score on child Entities if there is child entities
     * a method named 'processScore' must exists to process score on current entity, if there is no child entities (end
     * tree entity)
     */
    use scorableEntity;

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
     * @var \App\Entity\Folder\FolderTpl
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\Folder\FolderTpl", fetch="LAZY")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="folderTpl_id", referencedColumnName="id")
     * })
     * @ApiProperty(readableLink=false, readable=true)
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     */
    private $folderTpl;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Questionnaire\Questionnaire", mappedBy="folder", cascade={"persist"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Create"})
     * @Groups({"Folder:Update"})
     * @Groups({"Score"})
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $questionnaires;

    /**
     * @param \App\Entity\Calendar $calendar
     *
     * @return $this
     */
    public function addCalendar(Calendar $calendar): self
    {
        if (!$this->getCalendars()->contains($calendar)) {
            $this->getCalendars()->add($calendar);

            $calendar->addFolder($this);
        }

        return $this;
    }

    /**
     * @param \App\Entity\Questionnaire\Questionnaire $questionnaire
     *
     * @return $this
     */
    public function addQuestionnaire(Questionnaire $questionnaire): self
    {
        if (!$this->getQuestionnaires()->contains($questionnaire)) {
            $this->getQuestionnaires()->add($questionnaire);
            $questionnaire->setFolder($this);
        }

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildEntities(): Collection
    {
        return $this->getQuestionnaires();
    }

    /**
     * @return \App\Entity\Folder\FolderTpl|null
     */
    public function getFolderTpl(): ?FolderTpl
    {
        return $this->folderTpl;
    }

    /**
     * @param \App\Entity\Folder\FolderTpl|null $folderTpl
     *
     * @return $this
     */
    public function setFolderTpl(?FolderTpl $folderTpl): self
    {
        $this->folderTpl = $folderTpl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getParentTargets(): ?string
    {
        return $this->parentTargets;
    }

    /**
     * @param string $parentTargets
     *
     * @return $this
     */
    public function setParentTargets(string $parentTargets): self
    {
        $this->parentTargets = $parentTargets;

        return $this;
    }

    /**
     * @return Collection|Questionnaire[]
     */
    public function getQuestionnaires(): Collection
    {
        $this->questionnaires = $this->questionnaires ?? new ArrayCollection();
        return $this->questionnaires;
    }

    /**
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @param string $target
     *
     * @return $this
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @param \App\Entity\Questionnaire\Questionnaire $questionnaire
     *
     * @return $this
     */
    public function removeQuestionnaire(Questionnaire $questionnaire): self
    {
        if ($this->getQuestionnaires()->contains($questionnaire)) {
            $this->getQuestionnaires()->removeElement($questionnaire);
            // set the owning side to null (unless already changed)
            if ($questionnaire->getFolder() === $this) {
                $questionnaire->setFolder(null);
            }
        }

        return $this;
    }

}
