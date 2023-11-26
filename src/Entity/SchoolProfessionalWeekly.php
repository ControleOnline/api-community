<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\SchoolProfessionalWeeklyRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new Delete(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/school_professional_weeklies/available-professionals', controller: \App\Controller\GetAvailableProfessionalsAction::class), new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')')], normalizationContext: ['groups' => ['school_professional_weekly_read']], denormalizationContext: ['groups' => ['school_professional_weekly_write']], security: 'is_granted(\'ROLE_CLIENT\')')]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleProfessional' => 'exact'])]
class SchoolProfessionalWeekly
{
    const WEEK_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var PeopleProfessional
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\PeopleProfessional")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="professional_id", referencedColumnName="id")
     * })
     * @Groups({"school_professional_weekly_read", "school_professional_weekly_write"})
     * @Assert\NotBlank
     */
    private $peopleProfessional;
    /**
     * @ORM\Column(type="text", nullable=false)
     * @Groups({"school_professional_weekly_read", "school_professional_weekly_write"})
     * @Assert\NotBlank
     * @Assert\Choice(choices=SchoolProfessionalWeekly::WEEK_DAYS)
     */
    private $weekDay;
    /**
     * @ORM\Column(type="time", nullable=false)
     * @Groups({"school_professional_weekly_read", "school_professional_weekly_write"})
     * @Assert\NotBlank
     * @Assert\Time
     * @Assert\Expression(
     *     "this.getStartTime() < this.getEndTime()",
     *     message="Start time must be less than end time"
     * )
     */
    private $startTime;
    /**
     * @ORM\Column(type="time", nullable=false)
     * @Groups({"school_professional_weekly_read", "school_professional_weekly_write"})
     * @Assert\NotBlank
     * @Assert\Time
     * @Assert\Expression(
     *     "this.getEndTime() > this.getStartTime()",
     *     message="End time must be greater than start time"
     * )
     */
    private $endTime;
    public function getId() : int
    {
        return $this->id;
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
