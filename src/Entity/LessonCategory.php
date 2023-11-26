<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\LessonCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass=LessonCategoryRepository::class)
 */
#[ApiResource(operations: [new Get(), new GetCollection(normalizationContext: ['groups' => ['lesson_category:collection:get']])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')')]
class LessonCategory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"lesson_category:collection:get"})
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"lesson:read", "lesson_category:collection:get"})
     */
    private $category;
    /**
     * @ORM\ManyToOne(targetEntity=LessonCategory::class, inversedBy="lessonCategories")
     */
    private $lessonCategoryParent;
    /**
     * @ORM\OneToMany(targetEntity=LessonCategory::class, mappedBy="lessonCategoryParent")
     */
    private $lessonCategories;
    /**
     * @ORM\OneToMany(targetEntity=Lesson::class, mappedBy="lessonCategory")
     */
    private $lessons;
    public function __construct()
    {
        $this->lessonCategories = new ArrayCollection();
        $this->lessons = new ArrayCollection();
    }
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getCategory() : ?string
    {
        return $this->category;
    }
    public function setCategory(string $category) : self
    {
        $this->category = $category;
        return $this;
    }
    public function getLessonCategoryParent() : ?self
    {
        return $this->lessonCategoryParent;
    }
    public function setLessonCategoryParent(?self $lessonCategoryParent) : self
    {
        $this->lessonCategoryParent = $lessonCategoryParent;
        return $this;
    }
    /**
     * @return Collection|self[]
     */
    public function getLessonCategories() : Collection
    {
        return $this->lessonCategories;
    }
    public function addLessonCategory(self $lessonCategory) : self
    {
        if (!$this->lessonCategories->contains($lessonCategory)) {
            $this->lessonCategories[] = $lessonCategory;
            $lessonCategory->setLessonCategoryParent($this);
        }
        return $this;
    }
    public function removeLessonCategory(self $lessonCategory) : self
    {
        if ($this->lessonCategories->removeElement($lessonCategory)) {
            // set the owning side to null (unless already changed)
            if ($lessonCategory->getLessonCategoryParent() === $this) {
                $lessonCategory->setLessonCategoryParent(null);
            }
        }
        return $this;
    }
    /**
     * @return Collection|Lesson[]
     */
    public function getLessons() : Collection
    {
        return $this->lessons;
    }
    public function addLesson(Lesson $lesson) : self
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons[] = $lesson;
            $lesson->setLessonCategory($this);
        }
        return $this;
    }
    public function removeLesson(Lesson $lesson) : self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getLessonCategory() === $this) {
                $lesson->setLessonCategory(null);
            }
        }
        return $this;
    }
}
