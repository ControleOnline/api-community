<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\PeopleTeamRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass=PeopleTeamRepository::class)
 */
#[ApiResource(operations: [new Get()], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class PeopleTeam
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity=People::class, inversedBy="peopleTeams")
     * @Groups({"school_class:item:get", "school_team_schedule_read"})
     */
    private $people;
    /**
     * @ORM\ManyToOne(targetEntity=Team::class, inversedBy="peopleTeams")
     * @ORM\JoinColumn(nullable=false)
     */
    private $team;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"school_class:item:get", "school_team_schedule_read"})
     */
    private $peopleType;
    /**
     * @ORM\Column(type="boolean")
     */
    private $enable;
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getPeople() : ?People
    {
        return $this->people;
    }
    public function setPeople(?People $people) : self
    {
        $this->people = $people;
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
    public function getPeopleType() : ?string
    {
        return $this->peopleType;
    }
    public function setPeopleType(string $peopleType) : self
    {
        $this->peopleType = $peopleType;
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
