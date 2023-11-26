<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\SchoolTeamScheduleRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new Delete(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], normalizationContext: ['groups' => ['school_team_schedule_read']], denormalizationContext: ['groups' => ['school_team_schedule_write']], security: 'is_granted(\'ROLE_CLIENT\')')]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['team.contract' => 'exact'])]
class SchoolTeamSchedule
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Team")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="team_id", referencedColumnName="id")
     * })
     * @Groups({"school_team_schedule_read"})
     */
    private $team;
    /**
     * @var PeopleProfessional
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\PeopleProfessional")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="professional_id", referencedColumnName="id")
     * })
     * @Groups({"school_team_schedule_read"})
     */
    private $peopleProfessional;
    /**
     * @ORM\Column(type="text", nullable=false)
     * @Groups({"school_team_schedule_read"})
     */
    private $weekDay;
    /**
     * @ORM\Column(type="time", nullable=false)
     * @Groups({"school_team_schedule_read"})
     */
    private $startTime;
    /**
     * @ORM\Column(type="time", nullable=false)
     * @Groups({"school_team_schedule_read"})
     */
    private $endTime;
    public function getId() : int
    {
        return $this->id;
    }
    public function getTeam() : Team
    {
        return $this->team;
    }
    public function setTeam(Team $team) : self
    {
        $this->team = $team;
        return $this;
    }
    public function getPeopleProfessional() : PeopleProfessional
    {
        return $this->peopleProfessional;
    }
    public function setPeopleProfessional(PeopleProfessional $peopleProfessional) : self
    {
        $this->peopleProfessional = $peopleProfessional;
        return $this;
    }
    public function getWeekDay() : string
    {
        return $this->weekDay;
    }
    public function setWeekDay(string $day) : self
    {
        $this->weekDay = $day;
        return $this;
    }
    public function getStartTime() : \DateTime
    {
        return $this->startTime;
    }
    public function setStartTime(\Datetime $time) : self
    {
        $this->startTime = $time;
        return $this;
    }
    public function getEndTime() : \DateTime
    {
        return $this->endTime;
    }
    public function setEndTime(\Datetime $time) : self
    {
        $this->endTime = $time;
        return $this;
    }
}
