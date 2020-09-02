<?php
/*
 * Core
 * TplFolderCalendar.php
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

use App\Traits\resourceableEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * TplFolderCalendar
 *
 * @ORM\Table(name="TplFolders_Calendars")
 * @ORM\Entity
 *
 */
class TplFolderCalendar
{
    /**
     * add a resource field filled with entity name
     */
    use resourceableEntity;

    /**
     * @var tplFolder
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="TplFolder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tplFolder_id", referencedColumnName="id")
     * })
     *
     * @Groups({"Calendar:Read"})
     */
    private $tplFolder;

    /**
     * @var calendar
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Calendar")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="calendar_id", referencedColumnName="id")
     * })
     *
     * @Groups({"TplFolder:Read"})
     * @Groups({"TplFolder:Create"})
     */
    private $calendar;

    public function getCalendar(): ?Calendar
    {
        return $this->calendar;
    }

    public function setCalendar(?Calendar $calendar): self
    {
        $this->calendar = $calendar;

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
}