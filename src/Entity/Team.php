<?php

namespace App\Entity;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\TeamRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new Delete(security: 'is_granted(\'delete\', object)')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['contract' => 'exact'])]
class Team
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"school_class:item:get"})
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_team_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $company_team;
    /**
     * @ORM\Column(name="type", type="string", columnDefinition="enum('school', 'ead', 'company')")
     * @Groups({"school_class:item:get", "school_team_schedule_read"})
     */
    private $type;
    /**
     * @ORM\OneToMany(targetEntity=PeopleTeam::class, mappedBy="team")
     * @Groups({"school_class:item:get", "school_team_schedule_read"})
     */
    private $peopleTeams;
    /**
     * @ORM\OneToOne(targetEntity=Contract::class, inversedBy="team")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"school_class:item:get"})
     */
    private $contract;
    /**
     * @ORM\OneToMany(targetEntity=SchoolClass::class, mappedBy="team")
     */
    private $schoolClasses;
    public function __construct()
    {
        $this->peopleTeams = new ArrayCollection();
    }
    public function getId() : int
    {
        return $this->id;
    }
    public function getCompanyTeam() : People
    {
        return $this->company_team;
    }
    public function setCompanyTeam(People $company_team) : Team
    {
        $this->company_team = $company_team;
        return $this;
    }
    public function getType() : string
    {
        return $this->type;
    }
    public function setType(string $type) : Team
    {
        $this->type = $type;
        return $this;
    }
    /**
     * @return Collection|PeopleTeam[]
     */
    public function getPeopleTeams() : Collection
    {
        return $this->peopleTeams;
    }
    public function addPeopleTeam(PeopleTeam $peopleTeam) : self
    {
        if (!$this->peopleTeams->contains($peopleTeam)) {
            $this->peopleTeams[] = $peopleTeam;
            $peopleTeam->setTeam($this);
        }
        return $this;
    }
    public function removePeopleTeam(PeopleTeam $peopleTeam) : self
    {
        if ($this->peopleTeams->removeElement($peopleTeam)) {
            // set the owning side to null (unless already changed)
            if ($peopleTeam->getTeam() === $this) {
                $peopleTeam->setTeam(null);
            }
        }
        return $this;
    }
    public function getContract() : ?Contract
    {
        return $this->contract;
    }
    public function setContract(Contract $contract) : self
    {
        $this->contract = $contract;
        return $this;
    }
    /**
     * @return Collection|SchoolClass[]
     */
    public function getSchoolClasses() : Collection
    {
        return $this->schoolClasses;
    }
    public function addSchoolClass(SchoolClass $schoolClass) : self
    {
        if (!$this->schoolClasses->contains($schoolClass)) {
            $this->schoolClasses[] = $schoolClass;
            $schoolClass->setTeam($this);
        }
        return $this;
    }
    public function removeSchoolClass(SchoolClass $schoolClass) : self
    {
        if ($this->schoolClasses->removeElement($schoolClass)) {
            // set the owning side to null (unless already changed)
            if ($schoolClass->getTeam() === $this) {
                $schoolClass->setTeam(null);
            }
        }
        return $this;
    }
}
