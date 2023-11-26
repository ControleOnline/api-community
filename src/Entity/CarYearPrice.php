<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
/**
 * CarYearPrice
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="car_year_price")
 * @ORM\Entity ()
 */
#[ApiResource(formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class CarYearPrice
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var integer
     *
     * @ORM\Column(name="car_type_id", type="integer", nullable=false)
     */
    private $carTypeId;
    /**
     * @var integer
     *
     * @ORM\Column(name="car_type_ref", type="integer", nullable=false)
     */
    private $carTypeRef;
    /**
     * @var integer
     *
     * @ORM\Column(name="fuel_type_code", type="integer", nullable=false)
     */
    private $fuelTypeCode;
    /**
     * @var integer
     *
     * @ORM\Column(name="car_manufacturer_id", type="integer", nullable=false)
     */
    private $carManufacturerId;
    /**
     * @var integer
     *
     * @ORM\Column(name="car_model_id", type="integer", nullable=false)
     */
    private $carModelId;
    /**
     * @var \App\Entity\CarModel
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\CarModel", inversedBy="years")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="car_model_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $carModel;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $label;
    /**
     * @var integer
     *
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
    private $value;
    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="decimal", nullable=false)
     */
    private $price;
    /**
     * @var \DateTimeInterface
     * @ORM\Column(name="created_at", type="datetime",  nullable=false, columnDefinition="DATETIME")
     */
    private $createdAt;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }
    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Get the value of label
     */
    public function getLabel()
    {
        return $this->label;
    }
    /**
     * Set the value of label
     */
    public function setLabel($label) : self
    {
        $this->label = $label;
        return $this;
    }
    /**
     * Get the value of value
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Set the value of value
     */
    public function setValue($value) : self
    {
        $this->value = $value;
        return $this;
    }
    /**
     * Get the value of createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    /**
     * Get the value of carTypeId
     */
    public function getCarTypeId()
    {
        return $this->carTypeId;
    }
    /**
     * Set the value of carTypeId
     */
    public function setCarTypeId($carTypeId) : self
    {
        $this->carTypeId = $carTypeId;
        return $this;
    }
    /**
     * Get the value of carTypeRef
     */
    public function getCarTypeRef()
    {
        return $this->carTypeRef;
    }
    /**
     * Set the value of carTypeRef
     */
    public function setCarTypeRef($carTypeRef) : self
    {
        $this->carTypeRef = $carTypeRef;
        return $this;
    }
    /**
     * Get the value of fuelTypeCode
     */
    public function getFuelTypeCode()
    {
        return $this->fuelTypeCode;
    }
    /**
     * Set the value of fuelTypeCode
     */
    public function setFuelTypeCode($fuelTypeCode) : self
    {
        $this->fuelTypeCode = $fuelTypeCode;
        return $this;
    }
    /**
     * Get the value of carManufacturerId
     */
    public function getCarManufacturerId()
    {
        return $this->carManufacturerId;
    }
    /**
     * Set the value of carManufacturerId
     */
    public function setCarManufacturerId($carManufacturerId) : self
    {
        $this->carManufacturerId = $carManufacturerId;
        return $this;
    }
    /**
     * Get the value of carModelId
     */
    public function getCarModelId()
    {
        return $this->carModelId;
    }
    /**
     * Set the value of carModelId
     */
    public function setCarModelId($carModelId) : self
    {
        $this->carModelId = $carModelId;
        return $this;
    }
    /**
     * Get the value of price
     */
    public function getPrice()
    {
        return $this->price;
    }
    /**
     * Set the value of price
     */
    public function setPrice($price) : self
    {
        $this->price = $price;
        return $this;
    }
    /**
     * Get the value of carModel
     */
    public function getCarModel()
    {
        return $this->carModel;
    }
    /**
     * Set the value of carModel
     */
    public function setCarModel($carModel) : self
    {
        $this->carModel = $carModel;
        return $this;
    }
}
// create table car_year_price (
//     id int primary key auto_increment,
//     car_type_id int not null,
//     car_type_ref int not null,
//     fuel_type_code int not null,
//     car_manufacturer_id int not null,
//     car_model_id int not null,
//     label varchar(255) not null,
//     value varchar(255) not null,
//     price double not null,
//     created_at datetime not null DEFAULT CURRENT_TIMESTAMP,
//     foreign key (car_manufacturer_id) references car_manufacturer (id),
//     foreign key (car_model_id) references car_model (id)
// )
