<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\UploadLessonFileAction;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="lessons")
 * @ORM\Entity (repositoryClass="App\Repository\LessonRepository")
 */
#[ApiResource(operations: [new Get(), new GetCollection(), new Post(denormalizationContext: ['groups' => ['lesson:collection:post']]), new Post(uriTemplate: '/lessons/upload_file', controller: \App\Controller\UploadLessonFileAction::class, deserialize: false, security: 'is_granted(\'ROLE_CLIENT\')', validationContext: ['groups' => ['Default', 'order_upload_nf']], normalizationContext: ['groups' => ['lesson_upload_file:post']], openapiContext: ['consumes' => ['multipart/form-data']])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['lesson:read']], security: 'is_granted(\'ROLE_CLIENT\')')]
class Lesson
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"lesson:read"})
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $title;
    /**
     * @ORM\OneToMany(targetEntity=SchoolClassLessons::class, mappedBy="lesson")
     */
    private $schoolClassLessons;
    /**
     * @ORM\ManyToOne(targetEntity=LessonCategory::class, inversedBy="lessons")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $lessonCategory;
    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $notProficient;
    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $developing;
    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $proficient;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $subtitle;
    /**
     * @ORM\Column(type="text")
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $shortDescription;
    /**
     * @ORM\Column(type="text")
     * @Groups({"lesson:read", "lesson:collection:post"})
     */
    private $description;
    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"lesson:collection:post", "lesson:read"})
     */
    private $file;
    public function __construct()
    {
        $this->schoolClassLessons = new ArrayCollection();
    }
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * @return Collection|SchoolClassLessons[]
     */
    public function getSchoolClassLessons() : Collection
    {
        return $this->schoolClassLessons;
    }
    public function addSchoolClassLesson(SchoolClassLessons $schoolClassLesson) : self
    {
        if (!$this->schoolClassLessons->contains($schoolClassLesson)) {
            $this->schoolClassLessons[] = $schoolClassLesson;
            $schoolClassLesson->setLesson($this);
        }
        return $this;
    }
    public function removeSchoolClassLesson(SchoolClassLessons $schoolClassLesson) : self
    {
        if ($this->schoolClassLessons->removeElement($schoolClassLesson)) {
            // set the owning side to null (unless already changed)
            if ($schoolClassLesson->getLesson() === $this) {
                $schoolClassLesson->setLesson(null);
            }
        }
        return $this;
    }
    public function getLessonCategory() : ?LessonCategory
    {
        return $this->lessonCategory;
    }
    public function setLessonCategory(?LessonCategory $lessonCategory) : self
    {
        $this->lessonCategory = $lessonCategory;
        return $this;
    }
    public function getNotProficient() : ?float
    {
        return $this->notProficient;
    }
    public function setNotProficient(?float $notProficient) : self
    {
        $this->notProficient = $notProficient;
        return $this;
    }
    public function getDeveloping() : ?float
    {
        return $this->developing;
    }
    public function setDeveloping(?float $developing) : self
    {
        $this->developing = $developing;
        return $this;
    }
    public function getProficient() : ?float
    {
        return $this->proficient;
    }
    public function setProficient(?float $proficient) : self
    {
        $this->proficient = $proficient;
        return $this;
    }
    public function getSubtitle() : ?string
    {
        return $this->subtitle;
    }
    public function setSubtitle(string $subtitle) : self
    {
        $this->subtitle = $subtitle;
        return $this;
    }
    public function getShortDescription() : ?string
    {
        return $this->shortDescription;
    }
    public function setShortDescription(string $shortDescription) : self
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }
    public function getDescription() : ?string
    {
        return $this->description;
    }
    public function setDescription(string $description) : self
    {
        $this->description = $description;
        return $this;
    }
    public function getFile() : ?File
    {
        return $this->file;
    }
    public function setFile(?File $file) : self
    {
        $this->file = $file;
        return $this;
    }
    public function getTitle() : ?string
    {
        return $this->title;
    }
    public function setTitle(string $title) : self
    {
        $this->title = $title;
        return $this;
    }
}
