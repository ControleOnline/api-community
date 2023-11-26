<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * City
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="city", uniqueConstraints={@ORM\UniqueConstraint (name="city", columns={"city", "state_id"}), @ORM\UniqueConstraint(name="cod_ibge", columns={"cod_ibge"})}, indexes={@ORM\Index (name="state_id", columns={"state_id"}), @ORM\Index(name="seo", columns={"seo"})})
 * @ORM\Entity (repositoryClass="App\Repository\CityRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['city_read']], denormalizationContext: ['groups' => ['city_write']])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['city' => 'ASC'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['city' => 'partial', 'state.uf' => 'exact'])]
class City
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
     * @ORM\Column(name="city", type="string", length=80, nullable=false)
     * @Groups({"city_read", "order_read", "people_read", "address_read", "delivery_region_read"})
     */
    private $city;
    /**
     * @var string
     *
     * @ORM\Column(name="cod_ibge", type="integer", nullable=true)
     */
    private $cod_ibge;
    /**
     * @var boolean
     *
     * @ORM\Column(name="seo", type="boolean", nullable=false)
     */
    private $seo;
    /**
     * @var \App\Entity\State
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\State", inversedBy="city")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"order_read", "people_read", "address_read", "delivery_region_read"})
     */
    private $state;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\District", mappedBy="city")
     */
    private $district;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->district = new \Doctrine\Common\Collections\ArrayCollection();
        $this->seo = false;
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
     * Set city
     *
     * @param string $city
     * @return City
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }
    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return strtoupper($this->city);
    }
    /**
     * Set state
     *
     * @param \App\Entity\State $state
     * @return City
     */
    public function setState(\App\Entity\State $state = null)
    {
        $this->state = $state;
        return $this;
    }
    /**
     * Get state
     *
     * @return \App\Entity\State
     */
    public function getState()
    {
        return $this->state;
    }
    /**
     * Add district
     *
     * @param \App\Entity\District $district
     * @return City
     */
    public function addDistrict(\App\Entity\District $district)
    {
        $this->district[] = $district;
        return $this;
    }
    /**
     * Remove district
     *
     * @param \App\Entity\District $district
     */
    public function removeDistrict(\App\Entity\District $district)
    {
        $this->district->removeElement($district);
    }
    /**
     * Get district
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDistrict()
    {
        return $this->district;
    }
    /**
     * Set seo
     *
     * @param boolean $seo
     * @return City
     */
    public function setSeo($seo)
    {
        $this->seo = $seo;
        return $this;
    }
    /**
     * Get seo
     *
     * @return boolean
     */
    public function getSeo()
    {
        return $this->seo;
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
