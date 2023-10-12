<?php

namespace App\Entity;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DeliveryTax
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="delivery_tax", indexes={@ORM\Index (name="IDX_region_destination_id", columns={"region_destination_id"}),@ORM\Index(name="IDX_region_origin_id", columns={"region_origin_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\DeliveryTaxRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'read\', object)'), new Put(security: 'is_granted(\'edit\', object)', denormalizationContext: ['groups' => ['delivery_tax_edit']]), new Delete(security: 'is_granted(\'delete\', object)'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(securityPostDenormalize: 'is_granted(\'create\', object)')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['delivery_tax_read']], denormalizationContext: ['groups' => ['delivery_tax_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['taxType' => 'exact', 'taxSubtype' => 'exact', 'regionOrigin' => 'exact', 'regionDestination' => 'exact'])]
#[ApiFilter(filterClass: ExistsFilter::class, properties: ['taxSubtype', 'regionOrigin', 'regionDestination'])]
#[ApiResource(uriTemplate: '/delivery_tax_groups/{id}/delivery_taxes.{_format}', uriVariables: ['id' => new Link(fromClass: \App\Entity\DeliveryTaxGroup::class, identifiers: ['id'], toProperty: 'groupTax')], status: 200, filters: ['annotated_app_entity_delivery_tax_api_platform_core_bridge_doctrine_orm_filter_search_filter', 'annotated_app_entity_delivery_tax_api_platform_core_bridge_doctrine_orm_filter_exists_filter'], normalizationContext: ['groups' => ['delivery_tax_read']], operations: [new GetCollection()])]
class DeliveryTax
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"delivery_tax_read"})
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_name", type="string", length=255, nullable=false)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     * @Assert\NotBlank(groups={"delivery_tax_write"})
     * @Assert\Type("string")
     */
    private $taxName;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_description", type="string", length=255, nullable=false)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     * @Assert\NotBlank(groups={"delivery_tax_write"})
     * @Assert\Type("string")
     */
    private $taxDescription;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_type", type="string", length=50, nullable=false)
     * @Groups({"delivery_tax_read", "delivery_tax_write"})
     * @Assert\NotBlank
     * @Assert\Choice({"fixed", "percentage"})
     */
    private $taxType;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_subtype", type="string", length=50, nullable=true)
     * @Groups({"delivery_tax_read", "delivery_tax_write"})
     * @Assert\Choice({"invoice", "kg", "order", "km"})
     */
    private $taxSubtype;
    /**
     * @var float
     *
     * @ORM\Column(name="minimum_price", type="float",  nullable=true)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     * @Assert\NotBlank(groups={"delivery_tax_write"})
     * @Assert\Type(type={"float", "integer"})
     * @Assert\PositiveOrZero
     */
    private $minimumPrice;
    /**
     * @var float
     *
     * @ORM\Column(name="final_weight", type="float",  nullable=true)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     * @Assert\Type(type={"float", "integer"})
     * @Assert\Positive
     */
    private $finalWeight;
    /**
     * @var \App\Entity\DeliveryRegion
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\DeliveryRegion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="region_origin_id", referencedColumnName="id")
     * })
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     */
    private $regionOrigin;
    /**
     * @var \App\Entity\DeliveryRegion
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\DeliveryRegion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="region_destination_id", referencedColumnName="id")
     * })
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     */
    private $regionDestination;
    /**
     * @var \App\Entity\DeliveryTaxGroup
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\DeliveryTaxGroup", inversedBy="deliveryTaxes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_tax_group_id", referencedColumnName="id")
     * })
     * @Groups({"delivery_tax_write"})
     * @Assert\NotBlank
     */
    private $groupTax;
    /**
     * @var integer
     *
     * @ORM\Column(name="tax_order", type="integer",  nullable=false)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     * @Assert\NotBlank(groups={"delivery_tax_write"})
     * @Assert\Type(type={"integer"})
     * @Assert\PositiveOrZero
     */
    private $taxOrder;
    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float",  nullable=true)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     * @Assert\NotBlank(groups={"delivery_tax_write"})
     * @Assert\Type(type={"float", "integer"})
     * @Assert\Positive
     */
    private $price;
    /**
     * @var boolean
     *
     * @ORM\Column(name="optional", type="boolean",  nullable=false)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     * @Assert\NotBlank(groups={"delivery_tax_write"})
     * @Assert\Type("bool")
     */
    private $optional;
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
     * @var boolean
     *
     * @ORM\Column(name="deadline", type="integer",  nullable=false)
     * @Groups({"delivery_tax_read", "delivery_tax_write", "delivery_tax_edit"})
     */
    private $deadline = 0;

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
     * Set people
     *
     * @param \App\Entity\People $people
     * @return DeliveryTax
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
     * Set tax_name
     *
     * @param string $tax_name
     * @return DeliveryTax
     */
    public function setTaxName($tax_name)
    {
        $this->taxName = preg_replace('/\\s+/', ' ', trim($tax_name));
        return $this;
    }
    /**
     * Get tax_name
     *
     * @return string
     */
    public function getTaxName()
    {
        return preg_replace('/\\s+/', ' ', trim($this->taxName));
    }
    /**
     * Set tax_description
     *
     * @param string $tax_name
     * @return DeliveryTax
     */
    public function setTaxDescription($tax_description)
    {
        $this->taxDescription = preg_replace('/\\s+/', ' ', trim($tax_description));
        return $this;
    }
    /**
     * Get tax_name
     *
     * @return string
     */
    public function getTaxDescription()
    {
        return preg_replace('/\\s+/', ' ', trim($this->taxDescription));
    }
    /**
     * Set tax_type
     *
     * @param string $tax_type
     * @return DeliveryTax
     */
    public function setTaxType($tax_type)
    {
        $this->taxType = $tax_type;
        return $this;
    }
    /**
     * Get tax_type
     *
     * @return string
     */
    public function getTaxType()
    {
        return $this->taxType;
    }
    /**
     * Set tax_subtype
     *
     * @param string $tax_subtype
     * @return DeliveryTax
     */
    public function setTaxSubtype($tax_subtype)
    {
        $this->taxSubtype = $tax_subtype;
        return $this;
    }
    /**
     * Get tax_subtype
     *
     * @return string
     */
    public function getTaxSubtype()
    {
        return $this->taxSubtype;
    }
    /**
     * Set minimum_price
     *
     * @param string $minimum_price
     * @return DeliveryTax
     */
    public function setMinimumPrice($minimum_price)
    {
        $this->minimumPrice = $minimum_price;
        return $this;
    }
    /**
     * Get minimum_price
     *
     * @return float
     */
    public function getMinimumPrice()
    {
        return $this->minimumPrice;
    }
    /**
     * Set final_weight
     *
     * @param string $final_weight
     * @return DeliveryTax
     */
    public function setFinalWeight($final_weight)
    {
        $this->finalWeight = $final_weight;
        return $this;
    }
    /**
     * Get final_weight
     *
     * @return float
     */
    public function getFinalWeight()
    {
        return $this->finalWeight;
    }
    /**
     * Set region_origin
     *
     * @param \App\Entity\DeliveryRegion $region_origin
     * @return DeliveryTax
     */
    public function setRegionOrigin(\App\Entity\DeliveryRegion $region_origin = null)
    {
        $this->regionOrigin = $region_origin;
        return $this;
    }
    /**
     * Get region_origin
     *
     * @return \App\Entity\DeliveryRegion
     */
    public function getRegionOrigin()
    {
        return $this->regionOrigin;
    }
    /**
     * Set region_destination
     *
     * @param \App\Entity\DeliveryRegion $region_destination
     * @return DeliveryTax
     */
    public function setRegionDestination(\App\Entity\DeliveryRegion $region_destination = null)
    {
        $this->regionDestination = $region_destination;
        return $this;
    }
    /**
     * Get region_destination
     *
     * @return \App\Entity\DeliveryRegion
     */
    public function getRegionDestination()
    {
        return $this->regionDestination;
    }
    /**
     * Get group_tax
     *
     * @return \App\Entity\DeliveryTaxGroup
     */
    public function getGroupTax()
    {
        return $this->groupTax;
    }
    /**
     * Set group_tax
     *
     * @param \App\Entity\DeliveryTaxGroup $group_tax
     * @return DeliveryTax
     */
    public function setGroupTax(\App\Entity\DeliveryTaxGroup $group_tax = null)
    {
        $this->groupTax = $group_tax;
        return $this;
    }
    /**
     * Set tax_order
     *
     * @param string $tax_order
     * @return DeliveryTax
     */
    public function setTaxOrder($tax_order)
    {
        $this->taxOrder = $tax_order;
        return $this;
    }
    /**
     * Get tax_order
     *
     * @return integer
     */
    public function getTaxOrder()
    {
        return $this->taxOrder;
    }
    /**
     * Set price
     *
     * @param string $price
     * @return DeliveryTax
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }
    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }
    /**
     * Set optional
     *
     * @param boolean $optional
     * @return DeliveryTax
     */
    public function setOptional($optional)
    {
        $this->optional = $optional;
        return $this;
    }
    /**
     * Get optional
     *
     * @return boolean
     */
    public function getOptional()
    {
        return $this->optional;
    }

    /**
     * Set deadline
     *
     * @param integer $deadline
     * @return DeliveryTax
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
        return $this;
    }
    /**
     * Get tax_type
     *
     * @return integer
     */
    public function getDeadline()
    {
        return $this->deadline;
    }
}
