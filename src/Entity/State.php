<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * State
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="state", uniqueConstraints={@ORM\UniqueConstraint (name="UF", columns={"UF"}), @ORM\UniqueConstraint(name="cod_ibge", columns={"cod_ibge"})}, indexes={@ORM\Index (name="country_id", columns={"country_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\StateRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['state_read']], denormalizationContext: ['groups' => ['state_write']])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['state' => 'ASC'])]
class State
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
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=50, nullable=false)
     * @Groups({"state_read", "order_read", "people_read", "address_read", "delivery_region_read"})
     */
    private $state;
    /**
     * @var string
     *
     * @ORM\Column(name="cod_ibge", type="integer", nullable=true)
     */
    private $cod_ibge;
    /**
     * @var string
     *
     * @ORM\Column(name="UF", type="string", length=2, nullable=false)
     * @Groups({"state_read", "order_read", "people_read", "address_read", "delivery_region_read"})
     */
    private $uf;
    /**
     * @var \App\Entity\Country
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Country", inversedBy="state")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people_read", "address_read"})
     */
    private $country;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\City", mappedBy="state")
     */
    private $city;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->city = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set state
     *
     * @param string $state
     * @return State
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }
    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return strtoupper($this->state);
    }
    /**
     * Set uf
     *
     * @param string $uf
     * @return State
     */
    public function setUf($uf)
    {
        $this->uf = $uf;
        return $this;
    }
    /**
     * Get uf
     *
     * @return string
     */
    public function getUf()
    {
        return strtoupper($this->uf);
    }
    /**
     * Set country
     *
     * @param \App\Entity\Country $country
     * @return State
     */
    public function setCountry(\App\Entity\Country $country = null)
    {
        $this->country = $country;
        return $this;
    }
    /**
     * Get country
     *
     * @return \App\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }
    /**
     * Add city
     *
     * @param \App\Entity\City $city
     * @return State
     */
    public function addCity(\App\Entity\City $city)
    {
        $this->city[] = $city;
        return $this;
    }
    /**
     * Remove city
     *
     * @param \App\Entity\City $city
     */
    public function removeCity(\App\Entity\City $city)
    {
        $this->city->removeElement($city);
    }
    /**
     * Get city
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCity()
    {
        return $this->city;
    }
    /**
     * Set cod_ibge
     *
     * @param integer $cod_ibge
     * @return City
     */
    public function setIbge($cod_ibge)
    {
        $this->cod_ibge = $cod_ibge;
        return $this;
    }
    /**
     * Get cod_ibge
     *
     * @return integer
     */
    public function getIbge()
    {
        return $this->cod_ibge;
    }
}
