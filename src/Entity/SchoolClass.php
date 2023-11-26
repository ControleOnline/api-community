<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\UploadSchoolClassFileAction;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\SchoolClassRepository")
 */
#[ApiResource(operations: [new Get(normalizationContext: ['groups' => ['school_class:item:get']]), new Put(denormalizationContext: ['groups' => ['school_class:item:put']]), new Put(uriTemplate: '/school_classes/{id}/date-status', denormalizationContext: ['groups' => ['school_class:item:date_status']], security: 'is_granted(\'edit_date_status\', object)'), new Post(uriTemplate: '/school_classes/{id}/upload_file', controller: \App\Controller\UploadSchoolClassFileAction::class, deserialize: false, security: 'is_granted(\'ROLE_CLIENT\')', validationContext: ['groups' => ['Default', 'order_upload_nf']], openapiContext: ['consumes' => ['multipart/form-data']]), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/school_classes/professional-classes', controller: \App\Controller\GetSchoolProfessionalClassesCollectionAction::class)], denormalizationContext: ['groups' => ['school_class:write']], security: 'is_granted(\'ROLE_CLIENT\')')]
class SchoolClass
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"school_class:item:get"})
     */
    private $id;
    /**
     * @ORM\Column(type="datetime")
     * @Groups({"school_class:item:get"})
     */
    private $originalStartPrevision;
    /**
     * @ORM\Column(type="datetime")
     * @Groups({"school_class:item:get", "school_class:item:date_status"})
     */
    private $startPrevision;
    /**
     * @ORM\Column(type="datetime")
     * @Groups({"school_class:item:get", "school_class:item:date_status"})
     */
    private $endPrevision;
    /**
     * @ORM\ManyToMany(targetEntity=Lesson::class, cascade={"persist"})
     * @ORM\JoinTable(
     *     name              ="school_class_lessons",
     *     joinColumns       ={@ORM\JoinColumn(name="school_class_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@ORM\JoinColumn(name="lesson_id", referencedColumnName="id", nullable=false)}
     * )
     * @Groups({"school_class:item:put"})
     */
    private $schoolClassLessons;
    /**
     * @ORM\Column(type="datetime")
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $lessonStart;
    /**
     * @ORM\Column(type="datetime")
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $lessonEnd;
    /**
     * @ORM\ManyToOne(targetEntity=SchoolClassStatus::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"school_class:item:get", "school_class:item:put", "school_class:item:date_status"})
     */
    private $schoolClassStatus;
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $homework = '';
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $homeworkCorrection = '';
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $board = '';
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $importantNotes = '';
    /**
     * @ORM\OneToMany(targetEntity=SchoolClassFiles::class, mappedBy="schoolClass")
     */
    private $schoolClassFiles;
    /**
     * @ORM\ManyToOne(targetEntity=Team::class, inversedBy="schoolClasses")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"school_class:item:get"})
     */
    private $team;
    /**
     * @var \ControleOnline\Entity\PurchasingOrder
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\PurchasingOrder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $order;
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"school_class:item:get", "school_class:item:date_status"})
     */
    private $observations = null;
    public function __construct()
    {
        $this->originalStartPrevision = new DateTime('now');
        $this->startPrevision = new DateTime('now');
        $this->endPrevision = new DateTime('now');
        $this->lessonStart = new DateTime('now');
        $this->lessonEnd = new DateTime('now');
        $this->schoolClassLessons = new ArrayCollection();
        $this->schoolClassFiles = new ArrayCollection();
    }
    public function getId() : int
    {
        return $this->id;
    }
    public function getOriginalStartPrevision() : ?\DateTimeInterface
    {
        return $this->originalStartPrevision;
    }
    public function setOriginalStartPrevision(\DateTimeInterface $startPrevision) : self
    {
        $this->originalStartPrevision = $startPrevision;
        return $this;
    }
    public function getStartPrevision() : ?\DateTimeInterface
    {
        return $this->startPrevision;
    }
    public function setStartPrevision(\DateTimeInterface $startPrevision) : self
    {
        $this->startPrevision = $startPrevision;
        return $this;
    }
    public function getEndPrevision() : ?\DateTimeInterface
    {
        return $this->endPrevision;
    }
    public function setEndPrevision(\DateTimeInterface $endPrevision) : self
    {
        $this->endPrevision = $endPrevision;
        return $this;
    }
    /**
     * @return Collection|Lesson[]
     */
    public function getSchoolClassLessons() : Collection
    {
        return $this->schoolClassLessons;
    }
    public function addSchoolClassLesson(Lesson $schoolClassLesson) : self
    {
        if (!$this->schoolClassLessons->contains($schoolClassLesson)) {
            $this->schoolClassLessons[] = $schoolClassLesson;
        }
        return $this;
    }
    public function removeSchoolClassLesson(Lesson $schoolClassLesson) : self
    {
        if (!$this->schoolClassLessons->contains($schoolClassLesson)) {
            $this->schoolClassLessons->removeElement($schoolClassLesson);
        }
        return $this;
    }
    public function getLessonStart() : ?\DateTimeInterface
    {
        return $this->lessonStart;
    }
    public function setLessonStart(?\DateTimeInterface $lessonStart) : self
    {
        $this->lessonStart = $lessonStart;
        return $this;
    }
    public function getLessonEnd() : ?\DateTimeInterface
    {
        return $this->lessonEnd;
    }
    public function setLessonEnd(?\DateTimeInterface $lessonEnd) : self
    {
        $this->lessonEnd = $lessonEnd;
        return $this;
    }
    public function getSchoolClassStatus() : ?SchoolClassStatus
    {
        return $this->schoolClassStatus;
    }
    public function setSchoolClassStatus(SchoolClassStatus $schoolClassStatus) : self
    {
        $this->schoolClassStatus = $schoolClassStatus;
        return $this;
    }
    public function getHomework() : ?string
    {
        return $this->homework;
    }
    public function setHomework(?string $homework) : self
    {
        $this->homework = $homework;
        return $this;
    }
    public function getHomeworkCorrection() : ?string
    {
        return $this->homeworkCorrection;
    }
    public function setHomeworkCorrection(?string $homeworkCorrection) : self
    {
        $this->homeworkCorrection = $homeworkCorrection;
        return $this;
    }
    public function getBoard() : ?string
    {
        return $this->board;
    }
    public function setBoard(?string $board) : self
    {
        $this->board = $board;
        return $this;
    }
    public function getImportantNotes() : ?string
    {
        return $this->importantNotes;
    }
    public function setImportantNotes(?string $importantNotes) : self
    {
        $this->importantNotes = $importantNotes;
        return $this;
    }
    /**
     * @return Collection|SchoolClassFiles[]
     */
    public function getSchoolClassFiles() : Collection
    {
        return $this->schoolClassFiles;
    }
    public function addSchoolClassFile(SchoolClassFiles $schoolClassFile) : self
    {
        if (!$this->schoolClassFiles->contains($schoolClassFile)) {
            $this->schoolClassFiles[] = $schoolClassFile;
            $schoolClassFile->setSchoolClass($this);
        }
        return $this;
    }
    public function removeSchoolClassFile(SchoolClassFiles $schoolClassFile) : self
    {
        if ($this->schoolClassFiles->removeElement($schoolClassFile)) {
            // set the owning side to null (unless already changed)
            if ($schoolClassFile->getSchoolClass() === $this) {
                $schoolClassFile->setSchoolClass(null);
            }
        }
        return $this;
    }
    public function getTeam() : ?Team
    {
        return $this->team;
    }
    public function setTeam(?Team $team) : self
    {
        $this->team = $team;
        return $this;
    }
    /**
     * Set order
     *
     * @param \ControleOnline\Entity\PurchasingOrder $order
     */
    public function setOrder(\ControleOnline\Entity\PurchasingOrder $order = null) : self
    {
        $this->order = $order;
        return $this;
    }
    /**
     * Get order
     *
     * @return \ControleOnline\Entity\PurchasingOrder
     */
    public function getOrder() : ?PurchasingOrder
    {
        return $this->order;
    }
    public function hasOrder() : bool
    {
        return $this->order instanceof PurchasingOrder;
    }
    public function getObservations() : ?string
    {
        return $this->observations;
    }
    public function setObservations(?string $observations) : self
    {
        $this->observations = $observations;
        return $this;
    }
}
