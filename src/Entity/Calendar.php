<?php
/*
 * Core
 * Calendar.php
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

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Entity\Folder\Folder;
use App\Entity\Folder\FolderTpl;
use App\Traits\descriptionableEntity;
use App\Traits\traceableEntity;
use App\Traits\labelableEntity;
use App\Traits\resourceableEntity;
use Cron;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Calendar
 *
 * @ORM\Table(name="Calendars")
 * @ORM\Entity
 * @ApiFilter(PropertyFilter::class, arguments={"parameterName": "properties", "overrideDefaultProperties": false,
 *                                   "whitelist": {"allowed_property"}})
 *
 */
class Calendar
{
    /**
     * add group (Timestamp and Blame) for TimestampableEntity and BlameableEntity
     */
    use traceableEntity;

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
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     * @Groups({"Calendar:Read"})
     * @Groups({"Calendar:Update"})
     * @Groups({"FolderTpl:Read"})
     */
    public $id;

    /**
     * @var \DateTime|null
     * @Groups({"Calendar:Read"})
     * @Groups({"FolderTpl:Read"})
     *
     */
    public $periodStart;

    /**
     * @var \DateTime|null
     * @Groups({"Calendar:Read"})
     * @Groups({"FolderTpl:Read"})
     *
     */
    public $periodEnd;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="start", type="datetime", nullable=true)
     * @Groups({"Calendar:Read"})
     * @Groups({"Calendar:Update"})
     * @Groups({"FolderTpl:Read"})
     */
    private $start;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="end", type="datetime", nullable=true)
     * @Groups({"Calendar:Read"})
     * @Groups({"Calendar:Update"})
     * @Groups({"FolderTpl:Read"})
     */
    private $end;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cronstart", type="string", length=100, nullable=true)
     * @Groups({"Calendar:Read"})
     * @Groups({"Calendar:Update"})
     * @Groups({"FolderTpl:Read"})
     */
    private $cronStart;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cronend", type="string", length=100, nullable=true)
     * @Groups({"Calendar:Read"})
     * @Groups({"Calendar:Update"})
     * @Groups({"FolderTpl:Read"})
     */
    private $cronEnd;

    /**
     * @var bool
     *
     * @ORM\Column(name="valid", type="boolean", nullable=false, options={"default" : 1})
     *
     *          Is the calendar active or not?
     */
    private $valid = true;

    /**
     * @var array
     *
     *         period (start and end dates)
     *
     */
    private $periods = [];

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="\App\Entity\Folder\FolderTpl", mappedBy="calendars")
     * @Groups({"Calendar:Read"})
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $folderTpls;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="\App\Entity\Folder\Folder", mappedBy="calendars")
     * @Groups({"Calendar:Read"})
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $folders;

    /**
     * @param \App\Entity\Folder\Folder $folder
     *
     * @return $this
     */
    public function addFolder(Folder $folder): self
    {
        if (!$this->getFolders()->contains($folder)) {
            $this->getFolders()->add($folder);
            $folder->addCalendar($this);
        }

        return $this;
    }

    /**
     * @param \App\Entity\Folder\FolderTpl $folderTpl
     *
     * @return $this
     */
    public function addFolderTpl(FolderTpl $folderTpl): self
    {
        if (!$this->getFolderTpls()->contains($folderTpl)) {
            $this->getFolderTpls()->add($folderTpl);
            $folderTpl->addCalendar($this);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCronEnd(): ?string
    {
        return $this->cronEnd;
    }

    /**
     * @param string|null $cronEnd
     *
     * @return $this
     */
    public function setCronEnd(?string $cronEnd): self
    {
        $this->cronEnd = $cronEnd;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCronStart(): ?string
    {
        return $this->cronStart;
    }

    /**
     * @param string|null $cronStart
     *
     * @return $this
     */
    public function setCronStart(?string $cronStart): self
    {
        $this->cronStart = $cronStart;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    /**
     * @param \DateTimeInterface|null $end
     *
     * @return $this
     */
    public function setEnd(?DateTimeInterface $end): self
    {
        $this->end = $end->modify("next day midnight");

        return $this;
    }

    /**
     * @return Collection|FolderTpl[]
     */
    public function getFolderTpls(): Collection
    {
        $this->folderTpls = $this->folderTpls ?? new ArrayCollection();
        return $this->folderTpls;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPeriodCount(): int
    {
        return count($this->getPeriods());
    }

    /**
     * @return \DateTime|null
     */
    public function getPeriodEnd(): ?DateTime
    {
        $this->periodEnd = $this->getEnd();
        if ($this->getCronEnd() !== null && Cron\CronExpression::isValidExpression($this->getCronEnd())) {
            $cron = Cron\CronExpression::factory($this->getCronEnd());
            $this->setPeriodEnd($cron->getNextRunDate());
        }

        return $this->periodEnd;
    }

    /**
     * @param \DateTime|null $periodEnd
     *
     * @return $this
     */
    public function setPeriodEnd(?DateTime $periodEnd): self
    {
        $this->periodEnd = $periodEnd->modify("next day midnight");
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPeriodStart(): ?DateTime
    {
        $this->periodStart = $this->getStart();
        if ($this->getCronStart() !== null && Cron\CronExpression::isValidExpression($this->getCronStart())) {
            $cron = Cron\CronExpression::factory($this->getCronStart());
            $this->setPeriodStart($cron->getNextRunDate());
        }

        return $this->periodStart;
    }

    /**
     * @param \DateTime|null $periodStart
     *
     * @return $this
     */
    public function setPeriodStart(?DateTime $periodStart): self
    {
        $this->periodStart = $periodStart->modify("midnight");
        return $this;
    }

    /**
     * @return array
     */
    public function getPeriods(): array
    {
        $cStart = Cron\CronExpression::factory($this->getCronStart());
        $cEnd   = Cron\CronExpression::factory($this->getCronEnd());
        if (empty($this->periods)) {
            // calc all periods
            for ($pstart = $this->getStart(); $pstart < $this->getEnd(); $pstart = $cStart->getNextRunDate($pend)->modify("midnight")) {
                $pend = $cEnd->getNextRunDate($pstart);
                $pend->modify("next day midnight");
                $this->periods[] = [
                    'start' => $pstart,
                    'end'   => $pend
                ];
            }
        }

        return $this->periods;
    }

    /**
     * @param array $periods
     */
    public function setPeriods(array $periods): void
    {
        $this->periods = $periods;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    /**
     * @param \DateTimeInterface|null $start
     *
     * @return $this
     */
    public function setStart(?DateTimeInterface $start): self
    {
        $this->start = $start->modify("midnight");

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getValid(): ?bool
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     *
     * @return $this
     */
    public function setValid(bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    /**
     * @param $folder
     *
     * @return $this
     */
    public function removeFolder($folder): self
    {
        if ($this->getFolders()->contains($folder)) {
            $this->getFolders()->removeElement($folder);
            $folder->removeCalendar($this);
        }

        return $this;
    }

    /**
     * @param $folderTpl
     *
     * @return $this
     */
    public function removeFolderTpl($folderTpl): self
    {
        if ($this->getFolderTpls()->contains($folderTpl)) {
            $this->getFolderTpls()->removeElement($folderTpl);
            $folderTpl->removeCalendar($this);
        }

        return $this;
    }
}
