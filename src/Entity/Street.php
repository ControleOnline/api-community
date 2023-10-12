<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * Street
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="street", uniqueConstraints={@ORM\UniqueConstraint (name="street_2", columns={"street", "district_id"})}, indexes={@ORM\Index (name="district_id", columns={"district_id"}),@ORM\Index(name="cep", columns={"cep_id"}), @ORM\Index(name="street", columns={"street"})})
 * @ORM\Entity (repositoryClass="App\Repository\StreetRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['street_read']], denormalizationContext: ['groups' => ['street_write']])]
class Street
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
     * @ORM\Column(name="street", type="string", length=255, nullable=false)
     * @Groups({"people_read", "address_read"})
     */
    private $street;
    /**
     * @var \App\Entity\District
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\District", inversedBy="street")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="district_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people_read", "address_read"})
     */
    private $district;
    /**
     * @var \App\Entity\Cep
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Cep", inversedBy="street")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cep_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people_read", "address_read"})
     */
    private $cep;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Address", mappedBy="street")
     */
    private $address;
    /**
     * @var boolean
     *
     * @ORM\Column(name="confirmed", type="boolean", nullable=true)
     */
    private $confirmed;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->address = new ArrayCollection();
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
     * Set street
     *
     * @param string $street
     * @return Street
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }
    /**
     * Get street
     *
     * @return string
     */
    public function getStreet()
    {
        return strtoupper($this->street);
    }
    /**
     * Set district
     *
     * @param \App\Entity\District $district
     * @return District
     */
    public function setDistrict(\App\Entity\District $district = null)
    {
        $this->district = $district;
        return $this;
    }
    /**
     * Get district
     *
     * @return \App\Entity\District
     */
    public function getDistrict()
    {
        return $this->district;
    }
    /**
     * Set cep
     *
     * @param \App\Entity\Cep $cep
     * @return Cep
     */
    public function setCep(\App\Entity\Cep $cep = null)
    {
        $this->cep = $cep;
        return $this;
    }
    /**
     * Get cep
     *
     * @return \App\Entity\Cep
     */
    public function getCep()
    {
        return $this->cep;
    }
    /**
     * Add address
     *
     * @param \App\Entity\Address $address
     * @return Street
     */
    public function addAddress(\App\Entity\Address $address)
    {
        $this->address[] = $address;
        return $this;
    }
    /**
     * Remove address
     *
     * @param \App\Entity\Address $address
     */
    public function removeAddress(\App\Entity\Address $address)
    {
        $this->address->removeElement($address);
    }
    /**
     * Get address
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * Set confirmed
     *
     * @param boolean $confirmed
     * @return Street
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }
    /**
     * Get confirmed
     *
     * @return boolean
     */
    public function getConfirmed()
    {
        return $this->confirmed;
    }
}
