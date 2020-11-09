<?php
/*
 * Core
 * FolderTpl.php
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
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\Calendar;
use App\Entity\Questionnaire\QuestionnaireTpl;
use App\Entity\Target;
use App\Traits\resourceableEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Folder
 *
 * @ORM\Entity()
 * @ApiFilter(SearchFilter::class, properties={"permissions.right": "exact", "all": "exact"})
 *
 */
class FolderTpl extends FolderBase
{

    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;


    /**
     * @var \DateTime
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"Folder:Create"})
     */
    public $startDate;

    /**
     * @var \DateTime
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"Folder:Create"})
     */
    public $endDate;

    /**
     * @var array<\App\Entity\Target>
     *
     * @Groups({"FolderTpl:Read"})
     *
     */
    public $targets;
    /**
     * @var array<\App\Entity\Questionnaire\QuestionnaireTpl>
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @ApiProperty(readableLink=true, readable=true)
     */
    public $questionnaireTpls;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Folder\FolderTplTarget", mappedBy="folderTpl", cascade={"persist"})
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"Folder:Create"})
     *
     */
    private $folderTargets;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Folder\FolderTplPermission", mappedBy="folderTpl", cascade={"persist"})
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"Folder:Create"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $permissions;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Folder\FolderTplQuestionnaireTpl",
     *     mappedBy="folderTpl",cascade={"persist"},
     *     fetch="EAGER")
     * @ORM\OrderBy({"position" = "ASC"})
     *
     */
    private $folderTplsQuestionnaireTpls;

    /**
     * @var int
     * @ORM\Column(name="minfolders", type="integer", nullable=true, options={"unsigned"=true})
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"FolderTpl:Expectation"})
     * @Groups({"Folder:Create"})
     *
     *         minimum folders count in a period
     *
     */
    private $minFolders = 0;
    /**
     * @var int
     * @ORM\Column(name="maxfolders", type="integer", nullable=true, options={"unsigned"=true})
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"FolderTpl:Expectation"})
     * @Groups({"FolderTpl:Periods"})
     * @Groups({"Folder:Create"})
     *
     *         maximum folders count in a period. 0 mean no limit
     */
    private $maxFolders = 0;
    /**
     * @var int
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"FolderTpl:Expectation"})
     * @Groups({"FolderTpl:Periods"})
     * @Groups({"Folder:Create"})
     *
     *         expected folders count in a period. Only calculated
     */
    private $expectedFolders = 0;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @Groups({"FolderTpl:Periods"})
     *
     *         periods. Only calculated
     */
    private $periods = null;

    /**
     * @param \App\Entity\Calendar $calendar
     *
     * @return $this
     */
    public function addCalendar(Calendar $calendar): self
    {
        if (!$this->getCalendars()->contains($calendar)) {
            $this->getCalendars()->add($calendar);
            $calendar->addFolderTpl($this);
        }

        return $this;
    }

    /**
     * @param \App\Entity\Folder\FolderTplTarget $folderTarget
     *
     * @return $this
     */
    public function addFolderTarget(FolderTplTarget $folderTarget): self
    {
        if (!$this->getFolderTargets()->contains($folderTarget)) {
            $this->getFolderTargets()->add($folderTarget);
            $folderTarget->setFolderTpl($this);
        }

        return $this;
    }

    /**
     * @param \App\Entity\Folder\FolderTplQuestionnaireTpl $folderTplQuestionnaireTpl
     *
     * @return $this
     */
    public function addFolderTplsQuestionnaireTpls(FolderTplQuestionnaireTpl $folderTplQuestionnaireTpl): self
    {
        if (!$this->folderTplsQuestionnaireTpls->contains($folderTplQuestionnaireTpl)) {
            $folderTplQuestionnaireTpl->setFolderTpl($this);
            $this->folderTplsQuestionnaireTpls->add($folderTplQuestionnaireTpl);
        }

        return $this;
    }

    /**
     * @param array $period
     *
     * @return $this
     */
    public function addPeriod(array $period): self
    {
        if (!$this->getPeriods()->contains($period)) {
            $this->getPeriods()->add($period);
        }

        return $this;
    }

    /**
     * @param \App\Entity\Folder\FolderTplPermission $permission
     *
     * @return $this
     */
    public function addPermission(FolderTplPermission $permission): self
    {
        if (!$this->getPermissions()->contains($permission)) {
            $this->getPermissions()->add($permission);
            $permission->setFolderTpl($this);
        }

        return $this;
    }

    /**
     * @param \App\Entity\Questionnaire\QuestionnaireTpl $questionnaireTpl
     *
     * @return $this
     */
    public function addQuestionnaireTpl(QuestionnaireTpl $questionnaireTpl): self
    {
        if (empty($this->questionnaireTpls)) {
            $this->questionnaireTpls = new ArrayCollection();
        }
        if (!$this->questionnaireTpls->contains($questionnaireTpl)) {
            $this->questionnaireTpls->add($questionnaireTpl);
        }
        return $this;
    }

    /**
     * @param \App\Entity\Target $target
     *
     * @return $this
     */
    public function addTarget(Target $target): self
    {
        if (!$this->getTargets()->contains($target)) {
            $this->getTargets()->add($target);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate(): DateTime
    {
        $this->endDate = $this->endDate ?? new DateTime();
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate(DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getExpectedFolders(): ?int
    {
        return $this->expectedFolders;
    }

    /**
     * @param int $expectedFolders
     */
    public function setExpectedFolders(int $expectedFolders): void
    {
        $this->expectedFolders = $expectedFolders;
    }

    /**
     * @return Collection|FolderTplTarget[]
     */
    public function getFolderTargets(): Collection
    {
        $this->folderTargets = $this->folderTargets ?? new ArrayCollection();
        return $this->folderTargets;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFolderTplsQuestionnaireTpls(): Collection
    {
        $this->folderTplsQuestionnaireTpls = $this->folderTplsQuestionnaireTpls ?? new ArrayCollection();
        return $this->folderTplsQuestionnaireTpls;
    }

    /**
     * @return int|null
     */
    public function getMaxFolders(): ?int
    {
        return $this->maxFolders;
    }

    /**
     * @param int $maxFolders
     *
     * @return $this
     */
    public function setMaxFolders(int $maxFolders): self
    {
        $this->maxFolders = $maxFolders;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMinFolders(): ?int
    {
        return $this->minFolders;
    }

    /**
     * @param int $minFolders
     *
     * @return $this
     */
    public function setMinFolders(int $minFolders): self
    {
        $this->minFolders = $minFolders;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPeriodEnd(): DateTime
    {
        $this->periodEnd = $this->periodEnd ?? new DateTime();
        return $this->periodEnd;
    }

    /**
     * @param \DateTime $periodEnd
     */
    public function setPeriodEnd(DateTime $periodEnd): void
    {
        $this->periodEnd = $periodEnd;
    }

    /**
     * @return \DateTime
     */
    public function getPeriodStart(): DateTime
    {
        $this->periodStart = $this->periodStart ?? new DateTime();
        return $this->periodStart;
    }

    /**
     * @param \DateTime $periodStart
     */
    public function setPeriodStart(DateTime $periodStart): void
    {
        $this->periodStart = $periodStart;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeriods(): Collection
    {
        $this->periods = $this->periods ?? new ArrayCollection();
        return $this->periods;
    }

    /**
     * @return Collection|FolderTplPermission[]
     */
    public function getPermissions(): Collection
    {
        $this->permissions = $this->permissions ?? new ArrayCollection();
        return $this->permissions;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @throws \Exception
     */
    public function getQuestionnaireTpls(): ArrayCollection
    {
        $this->questionnaireTpls = new ArrayCollection();
        $this->getFolderTplsQuestionnaireTpls()->map(function ($questionnaireTplBlockTpl) {
            $questionnaireTpl = $questionnaireTplBlockTpl->getQuestionnaireTpl();
            $questionnaireTpl->setPosition($questionnaireTplBlockTpl->getPosition());
            $this->addQuestionnaireTpl($questionnaireTpl);
        });

        // sort questionnaireTpls by position
        $iterator = $this->questionnaireTpls->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getPosition() < $b->getPosition()) ? -1 : 1;
        });
        $this->questionnaireTpls = new ArrayCollection(array_values(iterator_to_array($iterator)));
        return $this->questionnaireTpls;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate(): DateTime
    {
        $this->startDate = $this->startDate ?? new DateTime();
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @return array
     */
    public function getTargets()
    {
        $this->targets = $this->targets ?? new ArrayCollection();
        return $this->targets;
    }

    /**
     * @param array $targets
     */
    public function setTargets(array $targets): void
    {
        $this->targets = $targets;
    }

    /**
     * @return \App\Entity\Folder\Folder
     * @throws \Exception
     */
    public function instantiate(): Folder
    {
        $folder = new Folder();

        $folder->setFolderTpl($this)
               ->setLabel($this->getLabel())
               ->setDescription($this->getDescription());

        foreach ($this->getCalendars() as $calendar) {
            $this->addCalendar($calendar);
        }

        foreach ($this->getQuestionnaireTpls() as $questionnaireTpl) {
            $folder->addQuestionnaire($questionnaireTpl->instantiate());
        }
        return $folder;
    }

    /**
     * @param \App\Entity\Folder\FolderTplTarget $folderTarget
     *
     * @return $this
     */
    public function removeFolderTarget(FolderTplTarget $folderTarget): self
    {
        if ($this->getFolderTargets()->contains($folderTarget)) {
            $this->getFolderTargets()->removeElement($folderTarget);
            // set the owning side to null (unless already changed)
            if ($folderTarget->getFolderTpl() === $this) {
                $folderTarget->setFolderTpl(null);
            }
        }

        return $this;
    }

    /**
     * @param \App\Entity\Folder\FolderTplPermission $permission
     *
     * @return $this
     */
    public function removePermission(FolderTplPermission $permission): self
    {
        if ($this->getPermissions()->contains($permission)) {
            $this->getPermissions()->removeElement($permission);
            // set the owning side to null (unless already changed)
            if ($permission->getFolderTpl() === $this) {
                $permission->setFolderTpl(null);
            }
        }

        return $this;
    }

    /**
     * @param \App\Entity\Questionnaire\QuestionnaireTpl $questionnaireTpl
     *
     * @return $this
     */
    public function removeQuestionnaireTpl(?QuestionnaireTpl $questionnaireTpl = null): self
    {
        if (empty($this->questionnaireTpls)) {
            $this->questionnaireTpls = new ArrayCollection();
        }
        if ($this->questionnaireTpls->contains($questionnaireTpl)) {
            $this->questionnaireTpls->removeElement($questionnaireTpl);
        }

        return $this;
    }

    public function sortQuestionnnaireTpls(): self
    {
        $position = 0;
        foreach ($this->getFolderTplsQuestionnaireTpls() as &$folderTplsQuestionnaireTpl) {
            $folderTplsQuestionnaireTpl->setPosition($position++);
        }
        return $this;
    }

}
