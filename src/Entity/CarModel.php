<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
/**
 * CarManufacturer
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="car_model")
 * @ORM\Entity ()
 */
#[ApiResource(operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(uriTemplate: 'car_models_search', controller: \App\Controller\SearchCarModelAction::class, openapiContext: [])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class CarModel
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
     * @ORM\Column(name="car_manufacturer_id", type="integer", nullable=false)
     */
    private $carManufacturerId;
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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\CarYearPrice", mappedBy="carModel")
     */
    private $years;
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
        $this->years = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Get the value of years
     */
    public function getYears()
    {
        return $this->years;
    }
    /**
     * Set the value of years
     */
    public function setYears($years) : self
    {
        $this->years = $years;
        return $this;
    }
}
// create table car_model (
//     id int primary key auto_increment,
//     car_manufacturer_id int not null,
//     label varchar(255) not null,
//     value int not null,
//     created_at datetime not null DEFAULT CURRENT_TIMESTAMP,
//     foreign key (car_manufacturer_id) references car_manufacturer (id)
// )
