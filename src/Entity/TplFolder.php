<?php
/*
 * Core
 * TplFolder.php
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
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Traits\descriptionableEntity;
use App\Traits\gedmoableEntity;
use App\Traits\labelableEntity;
use App\Traits\resourceableEntity;
use App\Traits\stateableEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * TplFolder
 *
 * @ORM\Table(
 *     name="TplFolders",
 *     indexes={
 *      @ORM\Index(name="TplFolderdeleted_idx", columns={"deleted_at"})
 *     }
 * )
 * @ORM\Entity()
 * @ApiFilter(SearchFilter::class, properties={"permissions.right": "exact", "all": "exact"})
 *
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
class TplFolder
{
    /**
     * add group (Timestamp and Blame) for TimestampableEntity and BlameableEntity
     */
    use GedmoableEntity;

    /**
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

    /**
     * Hook timestampable behavior
     * updates writedAt, updatedAt fields
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
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"TplQuestionnaire:Read"})
     * @Groups({"TplFolder:Expectation"})
     * @Groups({"TplFolder:Periods"})
     * @Groups({"Folder:Create"})
     */
    public $id;

    /**
     * @var \DateTime
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"Folder:Create"})
     */
    public $periodStart;

    /**
     * @var \DateTime
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"Folder:Create"})
     */
    public $periodEnd;

    /**
     * @var \DateTime
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"Folder:Create"})
     */
    public $startDate;

    /**
     * @var \DateTime
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"Folder:Create"})
     */
    public $endDate;

    /**
     * @var array<\App\Entity\Target>
     *
     * @Groups({"TplFolder:Read"})
     *
     */
    public $targets;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="TplFolderTarget", mappedBy="tplFolder", cascade={"persist"})
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"Folder:Create"})
     *
     */
    private $folderTargets;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="TplFolderPermission", mappedBy="tplFolder", cascade={"persist"})
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"Folder:Create"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $permissions;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="TplQuestionnaire", inversedBy="tplFolders", cascade={"persist"})
     * @ORM\JoinTable(
     *  name="TplFolders_Questionnaires",
     *  joinColumns={
     *      @ORM\JoinColumn(name="tplFolder_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="tplQuestionnaire_id", referencedColumnName="id")
     *  }
     * )
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"Folder:Create"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $tplQuestionnaires;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Calendar", inversedBy="tplFolders", cascade={"persist"})
     * @ORM\JoinTable(
     *  name="TplFolders_Calendars",
     *  joinColumns={
     *      @ORM\JoinColumn(name="tplFolder_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="calendar_id", referencedColumnName="id")
     *  }
     * )
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"Folder:Create"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $calendars;
    /**
     * @var int
     * @ORM\Column(name="minfolders", type="integer", nullable=true, options={"unsigned"=true})
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"TplFolder:Expectation"})
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
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"TplFolder:Expectation"})
     * @Groups({"TplFolder:Periods"})
     * @Groups({"Folder:Create"})
     *
     *         maximum folders count in a period. 0 mean no limit
     */
    private $maxFolders = 0;
    /**
     * @var int
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Update"})
     * @Groups({"TplFolder:Expectation"})
     * @Groups({"TplFolder:Periods"})
     * @Groups({"Folder:Create"})
     *
     *         expected folders count in a period. Only calculated
     */
    private $expectedFolders = 0;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @Groups({"TplFolder:Periods"})
     *
     *         periods. Only calculated
     */
    private $periods = null;

    public function __construct()
    {
        $this->folderTargets     = new ArrayCollection();
        $this->targets           = new ArrayCollection();
        $this->permissions       = new ArrayCollection();
        $this->tplQuestionnaires = new ArrayCollection();
        $this->calendars         = new ArrayCollection();
        $this->periods           = new ArrayCollection();
    }

    public function addCalendar(Calendar $calendar): self
    {
        if (!$this->calendars->contains($calendar)) {
            $this->calendars->add($calendar);
            $calendar->addTplFolder($this);
        }

        return $this;
    }

    public function addFolderTarget(TplFolderTarget $folderTarget): self
    {
        if (!$this->folderTargets->contains($folderTarget)) {
            $this->folderTargets[] = $folderTarget;
            $folderTarget->setTplFolder($this);
        }

        return $this;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $periods
     */
    public function addPeriod(array $period): self
    {
        $this->periods = $this->getPeriods();
        if (!$this->periods->contains($period)) {
            $this->periods[] = $period;
        }

        return $this;
    }

    public function addPermission(TplFolderPermission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions[] = $permission;
            $permission->setTplFolder($this);
        }

        return $this;
    }

    public function addTarget(Target $target): self
    {
        $this->targets = $this->targets ?? new ArrayCollection();
        if (!$this->targets->contains($target)) {
            $this->targets->add($target);
        }

        return $this;
    }

    public function addTplQuestionnaire(TplQuestionnaire $tplQuestionnaire): self
    {
        if (!$this->tplQuestionnaires->contains($tplQuestionnaire)) {
            $this->tplQuestionnaires->add($tplQuestionnaire);
            $tplQuestionnaire->addTplFolder($this);
        }

        return $this;
    }

    /**
     * @return Collection|Calendar[]
     */
    public function getCalendars(): Collection
    {
        return $this->calendars;
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
     * @return Collection|TplFolderTarget[]
     */
    public function getFolderTargets(): Collection
    {
        return $this->folderTargets;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMaxFolders(): ?int
    {
        return $this->maxFolders;
    }

    public function setMaxFolders(int $maxFolders): self
    {
        $this->maxFolders = $maxFolders;

        return $this;
    }

    public function getMinFolders(): ?int
    {
        return $this->minFolders;
    }

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
    public function getPeriods(): \Doctrine\Common\Collections\Collection
    {
        $this->periods = $this->periods ?? new ArrayCollection();
        return $this->periods;
    }

    /**
     * @return Collection|TplFolderPermission[]
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
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
        return $this->targets->getValues();
    }

    /**
     * @param array $targets
     */
    public function setTargets(array $targets): void
    {
        $this->targets = $targets;
    }

    /**
     * @return Collection|TplFolderQuestionnaire[]
     */
    public function getTplQuestionnaires(): Collection
    {
        return $this->tplQuestionnaires;
    }

    public function removeCalendar(Calendar $calendar): self
    {
        if ($this->calendars->contains($calendar)) {
            $this->calendars->removeElement($calendar);
            $calendar->removeTplFolder($this);
        }

        return $this;
    }

    public function removeFolderTarget(TplFolderTarget $folderTarget): self
    {
        if ($this->folderTargets->contains($folderTarget)) {
            $this->folderTargets->removeElement($folderTarget);
            // set the owning side to null (unless already changed)
            if ($folderTarget->getTplFolder() === $this) {
                $folderTarget->setTplFolder(null);
            }
        }

        return $this;
    }

    public function removePermission(TplFolderPermission $permission): self
    {
        if ($this->permissions->contains($permission)) {
            $this->permissions->removeElement($permission);
            // set the owning side to null (unless already changed)
            if ($permission->getTplFolder() === $this) {
                $permission->setTplFolder(null);
            }
        }

        return $this;
    }

    public function removeTplQuestionnaire(TplQuestionnaire $tplQuestionnaire): self
    {
        if ($this->tplQuestionnaires->contains($tplQuestionnaire)) {
            $this->tplQuestionnaires->removeElement($tplQuestionnaire);
            // set the owning side to null (unless already changed)
            $tplQuestionnaire->removeTplFolder($this);
        }

        return $this;
    }

}
