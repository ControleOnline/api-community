<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Table(name="people_franchisee", uniqueConstraints={@ORM\UniqueConstraint(name="franchisee_id", columns={"franchisee_id", "franchisor"})}, indexes={@ORM\Index(name="franchisor_id", columns={"franchisor"})})
 * @ORM\Entity(repositoryClass="App\Repository\PeopleFranchiseeRepository")
 * @ORM\EntityListeners({App\Listener\LogListener::class}) 
 */
class PeopleFranchisee
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="peopleFranchisor")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="franchisor_id", referencedColumnName="id")
     * })
     */
    private $franchisor;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="peopleFranchisee")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="franchisee_id", referencedColumnName="id")
     * })
     */
    private $franchisee;

    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;


    /**
     * @var float
     *
     * @ORM\Column(name="royalties", type="float", nullable=false)
     */
    private $royalties;


    /**
     * @var float
     *
     * @ORM\Column(name="minimum_royalties", type="float", nullable=false)
     */
    private $minimum_royalties;

    public function getId()
    {
        return $this->id;
    }

    public function setFranchisor(People $franchisor = null)
    {
        $this->franchisor = $franchisor;

        return $this;
    }

    public function getFranchisor()
    {
        return $this->franchisor;
    }

    public function setFranchisee(People $franchisee = null)
    {
        $this->franchisee = $franchisee;

        return $this;
    }

    public function getFranchisee()
    {
        return $this->franchisee;
    }

    public function getEnabled()
    {
        return $this->enable;
    }

    public function setEnabled($enable)
    {
        $this->enable = $enable ?: 0;

        return $this;
    }


    /**
     * Set minimum_royalties
     *
     * @param float $minimum_royalties
     * @return PeopleSalesman
     */
    public function setMinimumRoyalties($minimum_royalties): self
    {
        $this->minimum_royalties = $minimum_royalties;

        return $this;
    }

    /**
     * Get minimum_royalties
     *
     * @return float
     */
    public function getMinimumRoyalties(): float
    {
        return $this->minimum_royalties;
    }


    /**
     * Set royalties
     *
     * @param float $royalties
     * @return PeopleSalesman
     */
    public function setRoyalties($royalties): self
    {
        $this->royalties = $royalties;

        return $this;
    }

    /**
     * Get royalties
     *
     * @return float
     */
    public function getRoyalties(): float
    {
        return $this->royalties;
    }
}
