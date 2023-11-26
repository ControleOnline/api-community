<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\PeopleProfessionalRepository")
 */
#[ApiResource(operations: [new Get(), new GetCollection(normalizationContext: ['groups' => ['people_professional:collection:get']])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')')]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['company.id' => 'exact', 'professional.id' => 'exact'])]
class PeopleProfessional
{
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var People
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $company;
    /**
     * @var bool
     * @ORM\Column(name="enable", type="boolean", nullable=false)
     */
    private $enable;
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\People", inversedBy="peopleProfessional", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"people_professional:collection:get", "school_professional_weekly_read", "school_team_schedule_read"})
     */
    private $professional;
    public function getId() : int
    {
        return $this->id;
    }
    public function setProfessional(People $professional) : PeopleProfessional
    {
        $this->professional = $professional;
        return $this;
    }
    public function getCompany() : People
    {
        return $this->company;
    }
    public function setCompany(People $company) : PeopleProfessional
    {
        $this->company = $company;
        return $this;
    }
    public function isEnable() : bool
    {
        return $this->enable;
    }
    public function setEnable(bool $enable) : PeopleProfessional
    {
        $this->enable = $enable;
        return $this;
    }
    public function getProfessional() : ?People
    {
        return $this->professional;
    }
}
