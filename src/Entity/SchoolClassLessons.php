<?php

namespace App\Entity;

use App\Repository\SchoolClassLessonsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SchoolClassLessonsRepository::class)
 *  @ORM\EntityListeners({App\Listener\LogListener::class})
 */
class SchoolClassLessons
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=SchoolClass::class, inversedBy="schoolClassLessons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $schoolClass;

    /**
     * @ORM\ManyToOne(targetEntity=Lesson::class, inversedBy="schoolClassLessons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $lesson;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchoolClass(): ?SchoolClass
    {
        return $this->schoolClass;
    }

    public function setSchoolClass(?SchoolClass $schoolClass): self
    {
        $this->schoolClass = $schoolClass;

        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): self
    {
        $this->lesson = $lesson;

        return $this;
    }
}
