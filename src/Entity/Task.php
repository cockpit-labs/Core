<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Questionnaire\Questionnaire;
use App\Traits\resourceableEntity;
use App\Traits\stateableEntity;
use App\Traits\traceableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 * @ORM\Table(name="Tasks")
 * @ApiResource()
 * @ORM\Entity
 * @ApiFilter(SearchFilter::class, properties={"questionnaire.id": "exact",
 *     "responsibleId": "exact", "informedIds": "partial",
 *     "state": "exact",
 *     "createdBy": "exact"})
 * @ApiFilter(DateFilter::class, properties={"createdAt"})
 */
class Task
{

    /**
     * add group (Timestamp and Blame) for TimestampableEntity and BlameableEntity
     */
    use TraceableEntity;

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
     * add a state field
     */
    use stateableEntity;


    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true)
     * @ORM\GeneratedValue(strategy="UUID")
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     */
    private $action;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="duedate", type="datetime", nullable=true)
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     *
     */
    private $dueDate;

    /**
     * @var \App\Entity\User
     * ApiProperty(readableLink=true, readable=false, writableLink=true)
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     */
    private $responsible;

    /**
     * @var string
     * @ORM\Column(name="responsible", type="string", length=36,
     *   nullable=false, options={"comment"="user id in keycloak"})
     * @Groups({"Task:Update"})
     */
    private $responsibleId;

    /**
     * @var array<\App\Entity\User>
     * @ApiProperty(readableLink=true, readable=true)
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     *
     */
    private $informed;

    /**
     * @var string
     * @ORM\Column(name="informed", type="string", length=1000, nullable=true)
     * @Groups({"Task:Update"})
     *
     */
    private $informedIds;

    /**
     * @var \App\Entity\Questionnaire\Questionnaire|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\Questionnaire\Questionnaire", inversedBy="tasks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="questionnaire_id", referencedColumnName="id")
     * })
     *
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     *
     * @ApiProperty(readableLink=false, readable=true)
     */
    private $questionnaire;

    /**
     * @var \App\Entity\Target
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Questionnaire:Update"})
     *
     * @ApiProperty(readableLink=true, readable=true)
     */
    private $target;

    /**
     * @param \App\Entity\User $informed
     *
     * @return $this
     */
    public function addInformed(User $informed): self
    {
        $alreadyExists = $this->getInformed()->exists(function ($key, $element) use ($informed) {
            return ($element->getId() == $informed->getId());
        });
        if (!$alreadyExists) {
            $this->getInformed()->add($informed);
            $informedIds = [];
            if (!empty($this->getInformedIds())) {
                $informedIds = explode('|', $this->getInformedIds());
            }
            $informedIds[] = $informed->getUsername();
            $informedIds   = array_unique($informedIds);
            $this->setInformedIds(implode('|', $informedIds));
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    /**
     * @param \DateTime $dueDate
     *
     * @return Task
     */
    public function setDueDate(?\DateTime $dueDate): Task
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getInformed(): ArrayCollection
    {
        $this->informed = $this->informed ?? new ArrayCollection();
        return $this->informed;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection|null $informed
     *
     * @return $this
     */
    public function setInformed(?ArrayCollection $informed = null): Task
    {
        $this->informed = $informed ?? new ArrayCollection();
        return $this;
    }

    /**
     * @return string
     */
    public function getInformedIds(): ?string
    {
        return $this->informedIds;
    }

    /**
     * @param string $informedIds
     *
     * @return Task
     */
    public function setInformedIds(string $informedIds): Task
    {
        $this->informedIds = $informedIds;
        return $this;
    }

    /**
     * @return \App\Entity\Questionnaire\Questionnaire
     */
    public function getQuestionnaire(): Questionnaire
    {
        return $this->questionnaire;
    }

    /**
     * @param \App\Entity\Questionnaire\Questionnaire $questionnaire
     *
     * @return Task
     */
    public function setQuestionnaire(?Questionnaire $questionnaire): Task
    {
        $this->questionnaire = $questionnaire;
        return $this;
    }

    /**
     * @return \App\Entity\User|null
     */
    public function getResponsible(): ?\App\Entity\User
    {
        return $this->responsible;
    }

    /**
     * @param string $responsible
     *
     * @return $this
     */
    public function setResponsible(User $responsible): self
    {
        $this->responsible = $responsible;
        $this->setResponsibleId($responsible->getusername());
        return $this;
    }

    /**
     * @return string
     */
    public function getResponsibleId(): string
    {
        return $this->responsibleId;
    }

    /**
     * @param string $responsibleId
     *
     * @return Task
     */
    public function setResponsibleId(string $responsibleId): Task
    {
        $this->responsibleId = $responsibleId;
        return $this;
    }

    /**
     * @return \App\Entity\Target
     */
    public function getTarget(): ?\App\Entity\Target
    {
        return $this->target;
    }

    /**
     * @param \App\Entity\Target $target
     *
     * @return Task
     */
    public function setTarget(\App\Entity\Target $target): Task
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @param \App\Entity\User $informed
     *
     * @return $this
     */
    public function removeInformed(?User $informed = null): self
    {
        if (empty($informed)) {
            $this->setInformed();
        }

        if ($this->getInformed()->contains($informed)) {
            $this->getInformed()->removeElement($informed);
        }

        return $this;
    }
}
