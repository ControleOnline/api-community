<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Quotation
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="quote", indexes={
 *  @ORM\Index(name="IDX_city_destination_id", columns={"city_destination_id"}),
 *  @ORM\Index(name="IDX_city_origin_id", columns={"city_origin_id"}),
 *  @ORM\Index(name="IDX_provider_id", columns={"provider_id"}),
 *  @ORM\Index(name="IDX_carrier_id", columns={"carrier_id"}),
 *  @ORM\Index(name="IDX_client_id", columns={"client_id"}),
 *  @ORM\Index(name="IDX_order_id", columns={"order_id"})}
 * )
 * @ORM\Entity (repositoryClass="App\Repository\QuotationRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/quotations/{id}/optional-taxes',
            controller: \App\Controller\GetQuotationOptionalTaxesAction::class
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/quotations/{id}/add-deliverytax',
            controller: \App\Controller\UpdateQuotationAddTaxAction::class
        ),
        new Get(
            uriTemplate: '/quotations/{id}/get-pdf',
            controller: \App\Controller\GetQuotationPdfAction::class
        ),
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/quote_detail/{id}',
            controller: \App\Controller\GetQuoteDetailTaxesAction::class
        ), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/skyhub/shipping-quote',
            controller: \App\Controller\SkyhubShippingQuoteAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['quotation_read']],
    denormalizationContext: ['groups' => ['quotation_write']]
)]
class Quotation
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
     * @ORM\Column(name="ip", type="string", nullable=true)
     */
    private $ip;
    /**
     * @var string
     *
     * @ORM\Column(name="internal_ip", type="string", nullable=true)
     */
    private $internal_ip;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $client;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteDetail", mappedBy="quote")
     * @ORM\OrderBy({"tax_order" = "ASC"})
     * @Groups({"quotation_read"})
     */
    private $quote_detail;
    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="quote_date", type="datetime",  nullable=false, columnDefinition="DATETIME")
     */
    private $quote_date;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="carrier_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"quotation_read", "order_read"})
     */
    private $carrier;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="provider_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $provider;
    /**
     * @var \App\Entity\City
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\City")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="city_origin_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"order_read"})
     */
    private $cityOrigin;
    /**
     * @var \App\Entity\City
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\City")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="city_destination_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"order_read"})
     */
    private $cityDestination;
    /**
     * @var \App\Entity\SalesOrder
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder", inversedBy="quotes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $order;
    /**
     * @var float
     *
     * @ORM\Column(name="total", type="float",  nullable=false)
     * @Groups({"quotation_read"})
     */
    private $total;
    /**
     * @var boolean
     *
     * @ORM\Column(name="denied", type="boolean",  nullable=false)
     */
    private $denied;
    /**
     * @var integer
     *
     * @ORM\Column(name="deadline", type="integer",  nullable=false)
     * @Groups({"order_read"})
     */
    private $deadline;
    public function __construct()
    {
        $this->quote_detail = new \Doctrine\Common\Collections\ArrayCollection();
        $this->quote_date = new \DateTime('now');
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
     * Add quote_detail
     *
     * @param \App\Entity\QuoteDetail $quote_detail
     * @return Quotation
     */
    public function addQuoteDetail(\App\Entity\QuoteDetail $quote_detail)
    {
        $this->quote_detail[] = $quote_detail;
        return $this;
    }
    /**
     * Remove quote_detail
     *
     * @param \App\Entity\Address quote_detail
     */
    public function removeQuoteDetail(\App\Entity\QuoteDetail $quote_detail)
    {
        $this->quote_detail->removeElement($quote_detail);
    }
    /**
     * Get quote_detail
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuoteDetail()
    {
        return $this->quote_detail;
    }
    /**
     * Set ip
     *
     * @param string $ip
     * @return Quotation
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }
    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
    /**
     * Set client
     *
     * @param \App\Entity\People $client
     * @return Quotation
     */
    public function setClient(\App\Entity\People $client = null)
    {
        $this->client = $client;
        return $this;
    }
    /**
     * Get client
     *
     * @return \App\Entity\People
     */
    public function getClient()
    {
        return $this->client;
    }
    /**
     * Set carrier
     *
     * @param \App\Entity\People $carrier
     * @return Quotation
     */
    public function setCarrier(\App\Entity\People $carrier = null)
    {
        $this->carrier = $carrier;
        return $this;
    }
    /**
     * Get carrier
     *
     * @return \App\Entity\People
     */
    public function getCarrier()
    {
        return $this->carrier;
    }
    /**
     * Set provider
     *
     * @param \App\Entity\People $provider
     * @return Quotation
     */
    public function setProvider(\App\Entity\People $provider = null)
    {
        $this->provider = $provider;
        return $this;
    }
    /**
     * Get provider
     *
     * @return \App\Entity\People
     */
    public function getProvider()
    {
        return $this->provider;
    }
    /**
     * Set cityOrigin
     *
     * @param \App\Entity\City $city_origin
     * @return Quotation
     */
    public function setCityOrigin(\App\Entity\City $city_origin = null)
    {
        $this->cityOrigin = $city_origin;
        return $this;
    }
    /**
     * Get cityOrigin
     *
     * @return \App\Entity\City
     */
    public function getCityOrigin()
    {
        return $this->cityOrigin;
    }
    /**
     * Set cityDestination
     *
     * @param \App\Entity\City $city_destination
     * @return Quotation
     */
    public function setCityDestination(\App\Entity\City $city_destination = null)
    {
        $this->cityDestination = $city_destination;
        return $this;
    }
    /**
     * Get cityDestination
     *
     * @return \App\Entity\City
     */
    public function getCityDestination()
    {
        return $this->cityDestination;
    }
    /**
     * Set total
     *
     * @param string $total
     * @return Quotation
     */
    public function setTotal($total)
    {
        $this->total = $total;
        return $this;
    }
    /**
     * Get total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }
    /**
     * Set order
     *
     * @param \App\Entity\SalesOrder $order
     * @return Quotation
     */
    public function setOrder(\App\Entity\SalesOrder $order = null)
    {
        $this->order = $order;
        return $this;
    }
    /**
     * Get order
     *
     * @return \App\Entity\SalesOrder
     */
    public function getOrder()
    {
        return $this->order;
    }
    /**
     * Set deadline
     *
     * @param integer $deadline
     * @return Quotation
     */
    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
        return $this;
    }
    /**
     * Get deadline
     *
     * @return integer
     */
    public function getDeadline()
    {
        return $this->deadline;
    }
    /**
     * Get denied
     *
     * @return boolean
     */
    public function getDenied()
    {
        return $this->denied;
    }
    /**
     * Set denied
     *
     * @param boolean $denied
     * @return Quotation
     */
    public function setDenied($denied)
    {
        $this->denied = $denied;
        return $this;
    }
    /**
     * Set internal_ip
     *
     * @param string $internal_ip
     * @return Quotation
     */
    public function setInternalIp($internal_ip)
    {
        $this->internal_ip = $internal_ip;
        return $this;
    }
    /**
     * Get internal_ip
     *
     * @return string
     */
    public function getInternalIp()
    {
        return $this->internal_ip;
    }
    /**
     * Set quote_date
     *
     * @param \DateTimeInterface $quote_date
     * @return Quotation
     */
    public function setQuoteDate($quote_date)
    {
        $this->quote_date = $quote_date;
        return $this;
    }
    /**
     * Get quote_date
     *
     * @return \DateTimeInterface
     */
    public function getQuoteDate()
    {
        return $this->quote_date;
    }
}
