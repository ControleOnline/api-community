<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\PeopleStudentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass=PeopleStudentRepository::class)
 */
#[ApiResource(operations: [new Get(), new GetCollection(normalizationContext: ['groups' => ['people_student:collection:get']])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')')]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['company.id' => 'exact'])]
class PeopleStudent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\OneToOne(targetEntity=People::class, inversedBy="peopleStudent", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"people_student:collection:get"})
     */
    private $student;
    /**
     * @ORM\ManyToOne(targetEntity=People::class, inversedBy="peopleStudents")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;
    /**
     * @ORM\Column(type="boolean")
     */
    private $enable;
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getStudent() : ?People
    {
        return $this->student;
    }
    public function setStudent(People $student) : self
    {
        $this->student = $student;
        return $this;
    }
    public function getCompany() : ?People
    {
        return $this->company;
    }
    public function setCompany(?People $company) : self
    {
        $this->company = $company;
        return $this;
    }
    public function getEnable() : ?bool
    {
        return $this->enable;
    }
    public function setEnable(bool $enable) : self
    {
        $this->enable = $enable;
        return $this;
    }
}
