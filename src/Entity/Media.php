<?php
/*
 * Core
 * Media.php
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
use App\Traits\gedmoableEntity;
use App\Traits\resourceableEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Media
 *
 * @ORM\Table(
 *     name="Medias",
 *     indexes={
 *          @ORM\Index(name="Mediadeleted_idx", columns={"deleted_at"}),
 *          @ORM\Index(name="type_idx", columns={"type"})
 *     }
 * )
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=4)
 * @ORM\DiscriminatorMap({
 *     "User"="UserMedia",
 *     "Tpl"="TplMedia"
 * })
 *
 * @Vich\Uploadable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 *
 * @ApiFilter(DateFilter::class, properties={"updatedAt"})
 * @ApiFilter(SearchFilter::class, properties={"owners.owner": "exact", "createdBy": "exact", "createdAt": "exact"})
 */
abstract class Media
{
    /**
     * add group (Timestamp and Blame) for TimestampableEntity and BlameableEntity
     */
    use GedmoableEntity;


    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    /**
     * Hook SoftDeleteable behavior
     * updates deletedAt field
     */
    use SoftDeleteableEntity;

    /**
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

    /**
     * @var string|null
     *
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({"Media"})
     */
    public $mediaUrl;
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     * @Groups({"Media"})
     */
    public $id;
    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     * @Groups({"Media"})
     */
    private $fileName;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     * @Groups({"Media"})
     */
    private $size;

    /**
     *
     * @ORM\Column(nullable=true)
     * @Groups({"Media"})
     */
    private $dimensions;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     * @Groups({"Media"})
     */
    private $mimeType;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     * @Groups({"Media"})
     */
    private $originalName;

    /**
     * @var File|null
     *
     * @Assert\NotNull(groups={"media_object_create"})
     * @Vich\UploadableField(mapping="media_object", fileNameProperty="fileName", size="size", mimeType="mimeType",
     *                                               originalName="originalName", dimensions="dimensions")
     */
    private $file;


    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?array $dimensions): self
    {
        $this->dimensions = json_encode($dimensions);

        return $this;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\File\File|null $file
     */
    public function setFile(?File $file): void
    {
        $this->file = $file;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): self
    {
        $this->size = $size;

        return $this;
    }
}
