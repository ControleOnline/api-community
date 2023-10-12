<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleDomain
 *
 * @ORM\Table(name="people_domain")
 * @ORM\Entity(repositoryClass="App\Repository\PeopleDomainRepository")
 * @ORM\EntityListeners({App\Listener\LogListener::class}) 
 */
class PeopleDomain
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=255, nullable=false)
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="domain_type", type="string", length=255, nullable=false)
     */
    private $domain_type;

    public function __construct()
    {
        $this->domain_type = 'cfp';
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set people
     *
     * @param \App\Entity\People $people
     * @return PeopleDomain
     */
    public function setPeople(\App\Entity\People $people = null)
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get people
     *
     * @return \App\Entity\People
     */
    public function getPeople()
    {
        return $this->people;
    }

    /**
     * Set domain
     *
     * @param string domain
     * @return PeopleDomain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set domain
     *
     * @param string domain_type
     * @return PeopleDomain
     */
    public function setDomainType($domain_type)
    {
        $this->domain_type = $domain_type;

        return $this;
    }

    /**
     * Get domain_type
     *
     * @return string
     */
    public function getDomainType()
    {
        return $this->domain_type;
    }
}
