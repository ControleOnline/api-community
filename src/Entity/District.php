<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * District
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="district", indexes={@ORM\Index (name="city_id", columns={"city_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\DistrictRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['district_read']], denormalizationContext: ['groups' => ['district_write']])]
class District
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
     * @ORM\Column(name="district", type="string", length=255, nullable=false)
     * @Groups({"people_read", "address_read"})
     */
    private $district;
    /**
     * @var \App\Entity\City
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\City", inversedBy="district")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="city_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people_read", "address_read"})
     */
    private $city;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Street", mappedBy="district")
     */
    private $street;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->street = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set district
     *
     * @param string $district
     * @return District
     */
    public function setDistrict($district)
    {
        $this->district = $district;
        return $this;
    }
    /**
     * Get district
     *
     * @return string
     */
    public function getDistrict()
    {
        return strtoupper($this->district);
    }
    /**
     * Set city
     *
     * @param \App\Entity\City $city
     * @return District
     */
    public function setCity(\App\Entity\City $city = null)
    {
        $this->city = $city;
        return $this;
    }
    /**
     * Get city
     *
     * @return \App\Entity\City
     */
    public function getCity()
    {
        return $this->city;
    }
    /**
     * Add street
     *
     * @param \App\Entity\Street $street
     * @return District
     */
    public function addStreet(\App\Entity\Street $street)
    {
        $this->street[] = $street;
        return $this;
    }
    /**
     * Remove street
     *
     * @param \App\Entity\Street $street
     */
    public function removeStreet(\App\Entity\Street $street)
    {
        $this->street->removeElement($street);
    }
    /**
     * Get street
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStreet()
    {
        return $this->street;
    }
}
