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
 * DeliveryRegion
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="delivery_region", uniqueConstraints={@ORM\UniqueConstraint (name="region_id", columns={"region","people_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\DeliveryRegionRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection()], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['delivery_region_read']], denormalizationContext: ['groups' => ['delivery_region_write']])]
class DeliveryRegion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"delivery_region_read", "delivery_tax_read"})
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="region", type="string", length=255, nullable=false)
     * @Groups({"delivery_region_read", "delivery_tax_read"})
     */
    private $region;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\DeliveryRegionCity", mappedBy="region")
     * @Groups({"delivery_region_read"})
     */
    private $regionCity;
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
     * @var integer
     *
     * @ORM\Column(name="deadline", type="integer", length=3, nullable=false)
     * @Groups({"delivery_region_read", "delivery_tax_read"})
     */
    private $deadline;
    /**
     * @var float
     *
     * @ORM\Column(name="retrieve_tax", type="float", nullable=true)
     * @Groups({"delivery_region_read"})
     */
    private $retrieveTax;
    public function __construct()
    {
        $this->regionCity = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set region
     *
     * @param string $region
     * @return DeliveryRegion
     */
    public function setRegion($region)
    {
        $this->region = $region;
        return $this;
    }
    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }
    /**
     * Set people
     *
     * @param \App\Entity\People $people
     * @return Document
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
     * Add regionCity
     *
     * @param \App\Entity\DeliveryRegionCity $regionCity
     * @return DeliveryRegion
     */
    public function addRegionCity(\App\Entity\DeliveryRegionCity $regionCity)
    {
        $this->regionCity[] = $regionCity;
        return $this;
    }
    /**
     * Remove regionCity
     *
     * @param \App\Entity\DeliveryRegionCity $regionCity
     */
    public function removeRegionCity(\App\Entity\DeliveryRegionCity $regionCity)
    {
        $this->regionCity->removeElement($regionCity);
    }
    /**
     * Get regionCity
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRegionCity()
    {
        return $this->regionCity;
    }
    /**
     * Set deadline
     *
     * @param string $deadline
     * @return DeliveryRegion
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
        return $this;
    }
    /**
     * Get deadline
     *
     * @return string
     */
    public function getDeadline()
    {
        return $this->deadline;
    }
    /**
     * Set retrieveTax
     *
     * @param string $retrieveTax
     * @return DeliveryRegion
     */
    public function setRetrieveTax($retrieveTax)
    {
        $this->retrieveTax = $retrieveTax;
        return $this;
    }
    /**
     * Get retrieveTax
     *
     * @return string
     */
    public function getRetrieveTax()
    {
        return $this->retrieveTax;
    }
}
