<?php


namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="student_proficiency")
 * @ORM\Entity (repositoryClass="App\Repository\StudentProficiencyRepository")
 */
#[ApiResource(normalizationContext: ['groups' => ['read'], 'datetime_format' => 'Y-m-d H:i:s'], denormalizationContext: ['groups' => ['write']])]
class StudentProficiency
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"read"})
     */
    private $id;
    /**
     * Many Student Proficiency has One Student
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="proficiencies")
     * @ORM\JoinColumn(name="student_id", referencedColumnName="id", nullable=false)
     */
    private $student;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumn(name="professional_id", referencedColumnName="id", nullable=false)
     * @Groups({"read"})
     */
    private $professional;
    /**
     * Many Student Proficiency has One Lesson
     * @ORM\ManyToOne(targetEntity="App\Entity\Lesson")
     * @ORM\JoinColumn(name="lesson_id", referencedColumnName="id", nullable=false)
     */
    private $lesson;
    /**
     * @ORM\Column(name="proficiency", type="string", columnDefinition="enum('Not Proficiency', 'Developing', 'Proficiency')")
     */
    private $proficiency;
    /**
     * @ORM\Column(name="proficiency_date", type="datetime",  nullable=false)
     * @Groups({"read"})
     */
    private $proficiency_date;
    public function __construct()
    {
        $this->proficiency_date = new DateTime('now');
    }
    /**
     * @return integer
     */
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * @return People
     */
    public function getStudent() : People
    {
        return $this->student;
    }
    /**
     * @param People $student
     * @return StudentProficiency
     */
    public function setStudent(Team $student) : People
    {
        $this->student = $student;
        return $this;
    }
    /**
     * @return People
     */
    public function getProfessional() : People
    {
        return $this->professional;
    }
    /**
     * @param People $professional
     * @return StudentProficiency
     */
    public function setProfessional(People $professional) : StudentProficiency
    {
        $this->professional = $professional;
        return $this;
    }
    /**
     * @return Lesson
     */
    public function getLesson() : Lesson
    {
        return $this->lesson;
    }
    /**
     * @param Lesson $lesson
     * @return StudentProficiency
     */
    public function setLesson(Lesson $lesson) : StudentProficiency
    {
        $this->lesson = $lesson;
        return $this;
    }
    /**
     * @return String
     */
    public function getProficiency() : string
    {
        return $this->proficiency;
    }
    /**
     * @param string $proficiency
     * @return StudentProficiency
     */
    public function setStartPrevision(string $proficiency) : StudentProficiency
    {
        $this->proficiency = $proficiency;
        return $this;
    }
    /**
     * @return DateTime
     */
    public function getProficiencyDate() : DateTime
    {
        return $this->proficiency_date;
    }
    /**
     * @param DateTime $proficiency_date
     * @return StudentProficiency
     */
    public function setProficiencyDate(DateTime $proficiency_date) : StudentProficiency
    {
        $this->proficiency_date = $proficiency_date;
        return $this;
    }
}
