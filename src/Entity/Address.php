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
 * Address
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="address", uniqueConstraints={@ORM\UniqueConstraint (name="user_id_3", columns={"people_id", "number", "street_id", "complement"})}, indexes={@ORM\Index (name="user_id_2", columns={"people_id","nickname"}), @ORM\Index(name="user_id", columns={"people_id"}), @ORM\Index(name="cep_id", columns={"street_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\AddressRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['address_read']], denormalizationContext: ['groups' => ['address_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
class Address
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
     * @var integer
     *
     * @ORM\Column(name="number", type="integer", nullable=true)
     * @Groups({"people_read", "address_read"})
     */
    private $number;
    /**
     * @var string
     *
     * @ORM\Column(name="nickname", type="string", length=50, nullable=false)
     * @Groups({"people_read", "address_read"})
     */
    private $nickname;
    /**
     * @var string
     *
     * @ORM\Column(name="complement", type="string", length=50, nullable=false)
     * @Groups({"people_read", "address_read"})
     */
    private $complement;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $people;
    /**
     * @var \App\Entity\Street
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Street", inversedBy="address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="street_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people_read", "address_read"})
     */
    private $street;
    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", nullable=false)
     * @Groups({"people_read"})
     */
    private $latitude;
    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", nullable=false)
     * @Groups({"people_read"})
     */
    private $longitude;
    /**
     * @var string
     *
     * @ORM\Column(name="locator", type="string", nullable=false)
     * @Groups({"people_read"})
     */
    private $locator;
    /**
     * @var Datetime
     *
     * @ORM\Column(name="opening_time", type="time", nullable=false)
     * @Groups({"people_read"})
     */
    private $opening_time;
    /**
     * @var Datetime
     *
     * @ORM\Column(name="closing_time", type="time", nullable=false)
     * @Groups({"people_read"})
     */
    private $closing_time;
    /**
     * @var string
     *
     * @ORM\Column(name="search_for", type="string", nullable=false)
     * @Groups({"people_read"})
     */
    private $search_for;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->latitude = 0;
        $this->longitude = 0;
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
     * Set number
     *
     * @param integer $number
     * @return Address
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }
    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }
    /**
     * Set nickname
     *
     * @param string $nickname
     * @return Address
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
        return $this;
    }
    /**
     * Get nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return strtoupper($this->nickname);
    }
    /**
     * Set complement
     *
     * @param string $complement
     * @return Address
     */
    public function setComplement($complement)
    {
        $this->complement = $complement;
        return $this;
    }
    /**
     * Get complement
     *
     * @return string
     */
    public function getComplement()
    {
        return strtoupper($this->complement);
    }
    /**
     * Set people
     *
     * @param \App\Entity\People $people
     * @return Address
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
    public function getPeople() : People
    {
        return $this->people;
    }
    /**
     * Set street
     *
     * @param \App\Entity\Street $street
     * @return Address
     */
    public function setStreet(\App\Entity\Street $street = null)
    {
        $this->street = $street;
        return $this;
    }
    /**
     * Get street
     *
     * @return \App\Entity\Street
     */
    public function getStreet()
    {
        return $this->street;
    }
    /**
     * Set latitude
     *
     * @param string $latitude
     * @return Address
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude ?: 0;
        return $this;
    }
    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }
    /**
     * Set longitude
     *
     * @param string $longitude
     * @return Address
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude ?: 0;
        return $this;
    }
    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Get the value of locator     
     * @return string
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     * Set the value of locator
     */
    public function setLocator($locator)
    {
        $this->locator = $locator;

        return $this;
    }

    /**
     * Get the value of opening_time
     */
    public function getOpeningTime()
    {
        return $this->opening_time;
    }

    /**
     * Set the value of opening_time
     */
    public function setOpeningTime($opening_time): self
    {
        $this->opening_time = $opening_time;

        return $this;
    }

    /**
     * Get the value of closing_time
     */
    public function getClosingTime()
    {
        return $this->closing_time;
    }

    /**
     * Set the value of closing_time
     */
    public function setClosingTime($closing_time): self
    {
        $this->closing_time = $closing_time;

        return $this;
    }
    
    /**
     * Get the value of search_for
     */
    public function getSearchFor()
    {
        return $this->search_for;
    }

    /**
     * Set the value of search_for
     */
    public function setSearchFor(string $search_for = null): self
    {
        $this->search_for = $search_for;

        return $this;
    }
}
