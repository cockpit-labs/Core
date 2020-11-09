<?php
/*
 * Core
 * FolderBase.php
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

use ApiPlatform\Core\Annotation\ApiProperty;
use App\Entity\Calendar;
use App\Traits\descriptionableEntity;
use App\Traits\traceableEntity;
use App\Traits\labelableEntity;
use App\Traits\stateableEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * BaseFolder
 *
 * @ORM\Table(
 *     name="Folders",
 *     indexes={
 *      @ORM\Index(name="Folderdeleted_idx", columns={"deleted_at"})
 *     }
 * )
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=20)
 * @ORM\DiscriminatorMap({
 *     "Template"="FolderTpl",
 *     "Instance"="Folder"
 * })
 *
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
abstract class FolderBase implements Translatable
{
    /**
     * add group (Timestamp and Blame) for TimestampableEntity and BlameableEntity
     */
    use traceableEntity;

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
     * @Groups({"Submit"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Score"})
     * @Groups({"QuestionnaireTpl:Read"})
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"QuestionnaireTpl:Read"})
     * @Groups({"FolderTpl:Expectation"})
     * @Groups({"FolderTpl:Periods"})
     */
    public $id;

    /**
     * @var \DateTime
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"Folder:Read"})
     */
    public $periodStart;

    /**
     * @var \DateTime
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"Folder:Read"})
     */
    public $periodEnd;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="\App\Entity\Calendar", inversedBy="folders", cascade={"persist"})
     * @ORM\JoinTable(
     *  name="Folders_Calendars",
     *  joinColumns={
     *      @ORM\JoinColumn(name="folder_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="calendar_id", referencedColumnName="id")
     *  }
     * )
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"Folder:Create"})
     * @Groups({"Folder:Read"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    public $calendars;

    /**
     * @return Collection|\App\Entity\Calendar[]
     */
    public function getCalendars(): Collection
    {
        $this->calendars = $this->calendars ?? new ArrayCollection();
        return $this->calendars;
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function removeCalendar(Calendar $calendar): self
    {
        if ($this->calendars->contains($calendar)) {
            $this->calendars->removeElement($calendar);
            $calendar->removeFolder($this);
        }

        return $this;
    }

}
