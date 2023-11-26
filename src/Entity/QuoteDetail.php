<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * QuoteDetail
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="quote_detail", indexes={@ORM\Index (name="IDX_region_destination_id", columns={"region_destination_id"}),@ORM\Index(name="IDX_region_origin_id", columns={"region_origin_id"}),@ORM\Index(name="IDX_delivery_tax_id", columns={"delivery_tax_id"}),@ORM\Index(name="IDX_quote", columns={"quote_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\QuoteDetailRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['quotedetail_read']], denormalizationContext: ['groups' => ['quotedetail_write']])]
class QuoteDetail
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"quotation_read"})
     */
    private $id;
    /**
     * @var \App\Entity\Quotation
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Quotation", inversedBy="quote_detail")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="quote_id", referencedColumnName="id")
     * })
     * @Groups({"quotation_read"})
     */
    private $quote;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_name", type="string", length=255, nullable=false)
     * @Groups({"quotation_read"})
     */
    private $tax_name;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_description", type="string", length=255, nullable=false)
     * @Groups({"quotation_read"})
     */
    private $tax_description;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_type", type="string", length=50, nullable=false)
     * @Groups({"quotation_read"})
     */
    private $tax_type;
    /**
     * @var string
     *
     * @ORM\Column(name="tax_subtype", type="string", length=50, nullable=true)
     * @Groups({"quotation_read"})
     */
    private $tax_subtype;
    /**
     * @var \App\Entity\DeliveryTax
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\DeliveryTax")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_tax_id", referencedColumnName="id")
     * })
     */
    private $deliveryTax;
    /**
     * @var float
     *
     * @ORM\Column(name="minimum_price", type="float",  nullable=true)
     * @Groups({"quotation_read"})
     */
    private $minimum_price;
    /**
     * @var float
     *
     * @ORM\Column(name="final_weight", type="float",  nullable=true)
     * @Groups({"quotation_read"})
     */
    private $final_weight;
    /**
     * @var \App\Entity\DeliveryRegion
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\DeliveryRegion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="region_origin_id", referencedColumnName="id")
     * })
     */
    private $region_origin;
    /**
     * @var \App\Entity\DeliveryRegion
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\DeliveryRegion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="region_destination_id", referencedColumnName="id")
     * })
     */
    private $region_destination;
    /**
     * @var integer
     *
     * @ORM\Column(name="tax_order", type="integer",  nullable=false)
     * @Groups({"quotation_read"})
     */
    private $tax_order;
    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float",  nullable=true)
     * @Groups({"quotation_read"})
     */
    private $price = 0;
    /**
     * @var boolean
     *
     * @ORM\Column(name="optional", type="boolean",  nullable=false)
     * @Groups({"quotation_read"})
     */
    private $optional;
    /**
     * @var float
     *
     * @ORM\Column(name="price_calculated", type="float",  nullable=false)
     * @Groups({"quotation_read"})
     */
    private $price_calculated = 0;
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
     * Set quote
     *
     * @param \App\Entity\Quotation $quote
     * @return Order
     */
    public function setQuote(\App\Entity\Quotation $quote = null)
    {
        $this->quote = $quote;
        return $this;
    }
    /**
     * Get quote
     *
     * @return \App\Entity\Quotation
     */
    public function getQuote()
    {
        return $this->quote;
    }
    /**
     * Set tax_name
     *
     * @param string $tax_name
     * @return QuoteDetail
     */
    public function setTaxName($tax_name)
    {
        $this->tax_name = preg_replace('/\\s+/', ' ', trim($tax_name));
        return $this;
    }
    /**
     * Get tax_name
     *
     * @return string
     */
    public function getTaxName()
    {
        return preg_replace('/\\s+/', ' ', trim($this->tax_name));
    }
    /**
     * Set tax_description
     *
     * @param string $tax_description
     * @return QuoteDetail
     */
    public function setTaxDescription($tax_description)
    {
        $this->tax_description = preg_replace('/\\s+/', ' ', trim($tax_description));
        return $this;
    }
    /**
     * Get tax_name
     *
     * @return string
     */
    public function getTaxDescription()
    {
        return preg_replace('/\\s+/', ' ', trim($this->tax_description));
    }
    /**
     * Set tax_type
     *
     * @param string $tax_type
     * @return QuoteDetail
     */
    public function setTaxType($tax_type)
    {
        $this->tax_type = $tax_type;
        return $this;
    }
    /**
     * Get tax_type
     *
     * @return string
     */
    public function getTaxType()
    {
        return $this->tax_type;
    }
    /**
     * Set tax_subtype
     *
     * @param string $tax_subtype
     * @return QuoteDetail
     */
    public function setTaxSubtype($tax_subtype)
    {
        $this->tax_subtype = $tax_subtype;
        return $this;
    }
    /**
     * Get tax_subtype
     *
     * @return string
     */
    public function getTaxSubtype()
    {
        return $this->tax_subtype;
    }
    /**
     * Set delivery_tax
     *
     * @param \App\Entity\DeliveryTax $delivery_tax
     * @return QuoteDetail
     */
    public function setDeliveryTax(\App\Entity\DeliveryTax $delivery_tax = null)
    {
        $this->deliveryTax = $delivery_tax;
        return $this;
    }
    /**
     * Get delivery_tax
     *
     * @return \App\Entity\DeliveryTax
     */
    public function getDeliveryTax()
    {
        return $this->deliveryTax;
    }
    /**
     * Set minimum_price
     *
     * @param string $minimum_price
     * @return QuoteDetail
     */
    public function setMinimumPrice($minimum_price)
    {
        $this->minimum_price = $minimum_price;
        return $this;
    }
    /**
     * Get minimum_price
     *
     * @return float
     */
    public function getMinimumPrice()
    {
        return $this->minimum_price;
    }
    /**
     * Set final_weight
     *
     * @param float $final_weight
     * @return QuoteDetail
     */
    public function setFinalWeight($final_weight)
    {
        $this->final_weight = $final_weight;
        return $this;
    }
    /**
     * Get final_weight
     *
     * @return float
     */
    public function getFinalWeight()
    {
        return $this->final_weight;
    }
    /**
     * Set region_origin
     *
     * @param \App\Entity\DeliveryRegion $region_origin
     * @return QuoteDetail
     */
    public function setRegionOrigin(\App\Entity\DeliveryRegion $region_origin = null)
    {
        $this->region_origin = $region_origin;
        return $this;
    }
    /**
     * Get region_origin
     *
     * @return \App\Entity\DeliveryRegion
     */
    public function getRegionOrigin()
    {
        return $this->region_origin;
    }
    /**
     * Set region_destination
     *
     * @param \App\Entity\DeliveryRegion $region_destination
     * @return QuoteDetail
     */
    public function setRegionDestination(\App\Entity\DeliveryRegion $region_destination = null)
    {
        $this->region_destination = $region_destination;
        return $this;
    }
    /**
     * Get region_destination
     *
     * @return \App\Entity\DeliveryRegion
     */
    public function getRegionDestination()
    {
        return $this->region_destination;
    }
    /**
     * Set tax_order
     *
     * @param integer $tax_order
     * @return QuoteDetail
     */
    public function setTaxOrder($tax_order)
    {
        $this->tax_order = $tax_order;
        return $this;
    }
    /**
     * Get tax_order
     *
     * @return integer
     */
    public function getTaxOrder()
    {
        return $this->tax_order;
    }
    /**
     * Set price
     *
     * @param float $price
     * @return QuoteDetail
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
     * @return QuoteDetail
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
     * Set price_calculated
     *
     * @param float $price_calculated
     * @return QuoteDetail
     */
    public function setPriceCalculated($price_calculated)
    {
        $this->price_calculated = $price_calculated;
        return $this;
    }
    /**
     * Get price_calculated
     *
     * @return float
     */
    public function getPriceCalculated()
    {
        return $this->price_calculated;
    }
}
