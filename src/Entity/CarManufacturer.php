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
 * CarManufacturer
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="car_manufacturer")
 * @ORM\Entity ()
 */
#[ApiResource(formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class CarManufacturer
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
}
// create table car_manufacturer (
//     id int primary key auto_increment,
//     car_type_id int not null,
//     car_type_ref int not null,
//     label varchar(255) not null,
//     value int not null,
//     created_at datetime not null DEFAULT CURRENT_TIMESTAMP
// )