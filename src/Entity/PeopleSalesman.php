<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleSalesman
 * @ORM\Table(name="people_salesman", uniqueConstraints={@ORM\UniqueConstraint(name="salesman_id", columns={"salesman_id", "company_id"})}, indexes={@ORM\Index(name="company_id", columns={"company_id"}), @ORM\Index(name="IDX_2C6E59348C03F15C", columns={"salesman_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\PeopleSalesmanRepository")
 *  @ORM\EntityListeners({App\Listener\LogListener::class})
 */
class PeopleSalesman
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
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="peopleSalesman")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     */
    private $company;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="peopleCompany")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="salesman_id", referencedColumnName="id")
     * })
     * @ORM\OrderBy({"alias" = "ASC"})
     */
    private $salesman;


    /**
     * @var float
     *
     * @ORM\Column(name="salesman_type", type="string", nullable=false)
     */
    private $salesman_type;


    /**
     * @var float
     *
     * @ORM\Column(name="commission", type="float", nullable=false)
     */
    private $commission;

    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;

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
     * Set company
     *
     * @param \App\Entity\People $company
     * @return PeopleSalesman
     */
    public function setCompany(People $company = null): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return \App\Entity\People 
     */
    public function getCompany(): People
    {
        return $this->company;
    }

    /**
     * Set salesman
     *
     * @param \App\Entity\People $salesman
     * @return PeopleSalesman
     */
    public function setSalesman(People $salesman = null): self
    {
        $this->salesman = $salesman;

        return $this;
    }

    /**
     * Get salesman
     *
     * @return \App\Entity\People
     */
    public function getSalesman(): People
    {
        return $this->salesman;
    }





    /**
     * Set salesman_type
     *
     * @param float $salesman_type
     * @return PeopleSalesman
     */
    public function setSalesmanType($salesman_type): self
    {
        $this->salesman_type = $salesman_type;

        return $this;
    }

    /**
     * Get salesman_type
     *
     * @return float
     */
    public function getSalesmanType(): float
    {
        return $this->salesman_type;
    }

    /**
     * Set commission
     *
     * @param float $commission
     * @return PeopleSalesman
     */
    public function setCommission($commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * Get commission
     *
     * @return float
     */
    public function getCommission(): float
    {
        return $this->commission;
    }

    public function getEnabled(): bool
    {
        return $this->enable;
    }

    public function setEnabled($enable): self
    {
        $this->enable = $enable ?: 0;

        return $this;
    }
}
