<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\OrderLogisticRepository;
use App\Controller\CreateLogisticAction;
use App\Controller\UpdateLogisticAction;
use ControleOnline\Entity\Status;
use App\Entity\SalesOrder;
use ControleOnline\Entity\PurchasingOrder;
use App\Entity\People;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;
use phpDocumentor\Reflection\Types\This;
use App\Filter\OrderLogisticEntityFilter;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="order_logistic", indexes={@ORM\Index (name="provider_id", columns={"provider_id"}), @ORM\Index(name="order_id", columns={"order_id"}), @ORM\Index(name="status_id", columns={"status_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\OrderLogisticRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), 
    new Put(security: 'is_granted(\'ROLE_CLIENT\')', denormalizationContext: ['groups' => ['logistic_write']]), 
    new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), 
    new Delete(name: 'order_logistics_delete', security: 'is_granted(\'ROLE_CLIENT\')', denormalizationContext: ['groups' => ['logistic_write']]),
    new Post(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/order_logistics', denormalizationContext: ['groups' => ['logistic_write']])], 
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')', 
    filters: [\App\Filter\OrderLogisticEntityFilter::class],
    normalizationContext: ['groups' => ['logistic_read']],
    denormalizationContext: ['groups' => ['logistic_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['shippingDate' => 'exact', 'arrivalDate' => 'exact', 'order.id' => 'partial', 'order.contract.id' => 'partial', 'order.client.name' => 'partial', 'order.productType' => 'partial', 'order.otherInformations' => 'partial', 'provider' => 'exact', 'destinationProvider' => 'exact', 'status' => 'exact', 'originAddress' => 'partial'])]
class OrderLogistic
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"logistic_read"})
     */
    private $id;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="estimated_shipping_date", type="date", nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $estimatedShippingDate = NULL;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="shipping_date", type="date", nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $shippingDate = NULL;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="estimated_arrival_date", type="date", nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $estimatedArrivalDate = NULL;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="arrival_date", type="date", nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $arrivalDate = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="origin_type", type="string", length=1, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $originType;
    /**
     * @var string|null
     *
     * @ORM\Column(name="origin_region", type="string", length=50, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $originRegion = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="origin_state", type="string", length=50, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $originState = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="origin_city", type="string", length=100, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $originCity = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="origin_adress", type="string", length=150, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $originAddress = NULL;
    /** 
     * @var string|null
     *
     * @ORM\Column(name="origin_locator", type="string", length=150, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $originLocator = NULL;
    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", nullable=false)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $price;
    /**
     * @var float
     *
     * @ORM\Column(name="amount_paid", type="float", nullable=false)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $amountPaid = 0;
    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="float", nullable=false)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $balance = 0;
    /**
     * @var \SalesOrder
     *
     * @ORM\ManyToOne(targetEntity="SalesOrder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     * @Groups({"logistic_read","logistic_write"})
     */
    private $order;
    /**
     * @var \ControleOnline\Entity\PurchasingOrder
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\PurchasingOrder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="purchasing_order_id", referencedColumnName="id")
     * })
     * @Groups({"logistic_read","logistic_write"})
     */
    private $purchasingOrder;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="provider_id", referencedColumnName="id")
     * })
     * @Groups({"logistic_read","logistic_write"})
     */
    private $provider;
    /**
     * @var \ControleOnline\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     * @Groups({"logistic_read","logistic_write"})
     */
    private $status;
    /**
     * @var string|null
     *
     * @ORM\Column(name="destination_type", type="string", length=1, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $destinationType;
    /**
     * @var string|null
     *
     * @ORM\Column(name="destination_region", type="string", length=50, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $destinationRegion = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="destination_state", type="string", length=50, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $destinationState = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="destination_city", type="string", length=100, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $destinationCity = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="destination_address", type="string", length=150, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $destinationAdress = NULL;
    /**
     * @var string|null
     *
     * @ORM\Column(name="destination_locator", type="string", length=150, nullable=true)
     * @Groups({"logistic_read","logistic_write"})
     */
    private $destinationLocator = NULL;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="destination_provider_id", referencedColumnName="id")
     * })
     * @Groups({"logistic_read","logistic_write"})
     */
    private $destinationProvider;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="in_charge", referencedColumnName="id")
     * })
     * @Groups({"logistic_read","logistic_write"})
     */
    private $inCharge;
    /**
     * @var \DateTimeInterface
     * @ORM\Column(name="last_modified", type="datetime",  nullable=false, columnDefinition="DATETIME")
     * @Groups({"logistic_read","logistic_write"})
     */
    private $lastModified;
    /**
     * @var \OrderLogisticSurveys
     *
     * @ORM\OneToOne(targetEntity="OrderLogisticSurveys", mappedBy="order_logistic_id")
     * @Groups({"logistic_read"})
     */
    private $orderLogisticSurvey;
    /**
     * Get the value of id
     *
     * @return  int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Get the value of estimatedShippingDate
     */
    public function getEstimatedShippingDate()
    {
        return $this->estimatedShippingDate;
    }

    /**
     * Set the value of estimatedShippingDate
     */
    public function setEstimatedShippingDate($estimatedShippingDate)
    {
        $this->estimatedShippingDate = $estimatedShippingDate;

        return $this;
    }
    /**
     * Get the value of shippingDate
     *
     * @return  \DateTime|null
     */
    public function getShippingDate()
    {
        return $this->shippingDate;
    }
    /**
     * Set the value of shippingDate
     *
     * @param  \DateTime|null  $shippingDate
     *
     * @return  self
     */
    public function setShippingDate($shippingDate)
    {
        $this->shippingDate = $shippingDate;
        return $this;
    }
    /**
     * Get the value of estimatedArrivalDate
     */
    public function getEstimatedArrivalDate()
    {
        return $this->estimatedArrivalDate;
    }

    /**
     * Set the value of estimatedArrivalDate
     */
    public function setEstimatedArrivalDate($estimatedArrivalDate)
    {
        $this->estimatedArrivalDate = $estimatedArrivalDate;

        return $this;
    }
    /**
     * Get the value of arrivalDate
     *
     * @return  \DateTime|null
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }
    /**
     * Set the value of arrivalDate
     *
     * @param  \DateTime|null  $arrivalDate
     *
     * @return  self
     */
    public function setArrivalDate($arrivalDate)
    {
        $this->arrivalDate = $arrivalDate;
        return $this;
    }
    /**
     * Get the value of originType
     *
     * @return  string|null
     */
    public function getOriginType()
    {
        return $this->originType;
    }
    /**
     * Set the value of originType
     *
     * @param  string|null  $originType
     *
     * @return  self
     */
    public function setOriginType($originType)
    {
        $this->originType = $originType;
        return $this;
    }
    /**
     * Get the value of originRegion
     *
     * @return  string|null
     */
    public function getOriginRegion()
    {
        return $this->originRegion;
    }
    /**
     * Set the value of originRegion
     *
     * @param  string|null  $originRegion
     *
     * @return  self
     */
    public function setOriginRegion($originRegion)
    {
        $this->originRegion = $originRegion;
        return $this;
    }
    /**
     * Get the value of originState
     *
     * @return  string|null
     */
    public function getOriginState()
    {
        return $this->originState;
    }
    /**
     * Set the value of originState
     *
     * @param  string|null  $originState
     *
     * @return  self
     */
    public function setOriginState($originState)
    {
        $this->originState = $originState;
        return $this;
    }
    /**
     * Get the value of originCity
     *
     * @return  string|null
     */
    public function getOriginCity()
    {
        return $this->originCity;
    }
    /**
     * Set the value of originCity
     *
     * @param  string|null  $originCity
     *
     * @return  self
     */
    public function setOriginCity($originCity)
    {
        $this->originCity = $originCity;
        return $this;
    }
    /**
     * Get the value of originAddress
     *
     * @return  string|null
     */
    public function getOriginAddress()
    {
        return $this->originAddress;
    }
    /**
     * Set the value of originAddress
     *
     * @param  string|null  $originAddress
     *
     * @return  self
     */
    public function setOriginAddress($originAddress)
    {
        $this->originAddress = $originAddress;
        return $this;
    }
    /**
     * Get the value of price
     *
     * @return  float
     */
    public function getPrice()
    {
        return $this->price;
    }
    /**
     * Set the value of price
     *
     * @param  float  $price
     *
     * @return  self
     */
    public function setPrice($price)
    {
        $this->price = $price ?: 0;
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
     * Set order
     *
     * @param \App\Entity\SalesOrder $order
     */
    public function setOrder(\App\Entity\SalesOrder $order)
    {
        $this->order = $order;
        return $this;
    }
    /**
     * Get the value of provider
     *
     * @return  \People
     */
    public function getProvider()
    {
        return $this->provider;
    }
    /**
     * Set the value of provider
     *
     * @param  \App\Entity\People  $provider
     *
     * @return  self
     */
    public function setProvider(?\App\Entity\People $provider)
    {
        $this->provider = $provider;
        return $this;
    }
    /**
     * Get the value of status
     *
     * @return  \ControleOnline\Entity\Status
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Set the value of status
     *
     * @param  \ControleOnline\Entity\Status  $status
     *
     * @return  self
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
        return $this;
    }
    /**
     * Get the value of destinationType
     *
     * @return  string|null
     */
    public function getDestinationType()
    {
        return $this->destinationType;
    }
    /**
     * Set the value of destinationType
     *
     * @param  string|null  $destinationType
     *
     * @return  self
     */
    public function setDestinationType($destinationType)
    {
        $this->destinationType = $destinationType;
        return $this;
    }
    /**
     * Get the value of destinationRegion
     *
     * @return  string|null
     */
    public function getDestinationRegion()
    {
        return $this->destinationRegion;
    }
    /**
     * Set the value of destinationRegion
     *
     * @param  string|null  $destinationRegion
     *
     * @return  self
     */
    public function setDestinationRegion($destinationRegion)
    {
        $this->destinationRegion = $destinationRegion;
        return $this;
    }
    /**
     * Get the value of destinationState
     *
     * @return  string|null
     */
    public function getDestinationState()
    {
        return $this->destinationState;
    }
    /**
     * Set the value of destinationState
     *
     * @param  string|null  $destinationState
     *
     * @return  self
     */
    public function setDestinationState($destinationState)
    {
        $this->destinationState = $destinationState;
        return $this;
    }
    /**
     * Get the value of destinationCity
     *
     * @return  string|null
     */
    public function getDestinationCity()
    {
        return $this->destinationCity;
    }
    /**
     * Set the value of destinationCity
     *
     * @param  string|null  $destinationCity
     *
     * @return  self
     */
    public function setDestinationCity($destinationCity)
    {
        $this->destinationCity = $destinationCity;
        return $this;
    }
    /**
     * Get the value of destinationAdress
     *
     * @return  string|null
     */
    public function getDestinationAdress()
    {
        return $this->destinationAdress;
    }
    /**
     * Set the value of destinationAdress
     *
     * @param  string|null  $destinationAdress
     *
     * @return  self
     */
    public function setDestinationAdress($destinationAdress)
    {
        $this->destinationAdress = $destinationAdress;
        return $this;
    }
    /**
     * Get the value of inCharge
     *
     * @return  \People
     */
    public function getInCharge()
    {
        return $this->inCharge;
    }
    /**
     * Set the value of inCharge
     *
     * @param  App\Entity\People\People  $inCharge
     *
     * @return  self
     */
    public function setInCharge(\App\Entity\People $inCharge)
    {
        $this->inCharge = $inCharge;
        return $this;
    }
    /**
     * Get the value of destinationProvider
     *
     * @return  \People
     */
    public function getDestinationProvider()
    {
        return $this->destinationProvider;
    }
    /**
     * Set the value of destinationProvider
     *
     * @param  \People  $destinationProvider
     *
     * @return  self
     */
    public function setDestinationProvider(?\App\Entity\People $destinationProvider)
    {
        $this->destinationProvider = $destinationProvider;
        return $this;
    }
    /**
     * Get the value of lastModified
     *
     * @return  \DateTimeInterface
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }
    /**
     * Set the value of lastModified
     *
     * @param  \DateTimeInterface  $lastModified
     *
     * @return  self
     */
    public function setLastModified(\DateTimeInterface $lastModified)
    {
        $this->lastModified = $lastModified;
        return $this;
    }
    /**
     * Get the value of amountPaid
     *
     * @return  int
     */
    public function getAmountPaid()
    {
        return $this->amountPaid;
    }
    /**
     * Set the value of amountPaid
     *
     * @param  int  $amountPaid
     *
     * @return  self
     */
    public function setAmountPaid($amountPaid)
    {
        $this->amountPaid = $amountPaid;
        return $this;
    }
        /**
     * Get the value of balance
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set the value of balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }
    /**
     * Get the value of purchasingOrder
     */
    public function getPurchasingOrder()
    {
        return $this->purchasingOrder;
    }
    /**
     * Set the value of purchasingOrder
     */
    public function setPurchasingOrder(\ControleOnline\Entity\PurchasingOrder $purchasingOrder)
    {
        $this->purchasingOrder = $purchasingOrder;
        return $this;
    }

    /**
     * Get the value of orderLogisticSurvey
     */
    public function getOrderLogisticSurvey()
    {
        return $this->orderLogisticSurvey;
    }

    /**
     * Set the value of orderLogisticSurvey
     */
    public function setOrderLogisticSurvey(OrderLogisticSurveys $orderLogisticSurvey)
    {
        $this->orderLogisticSurvey = $orderLogisticSurvey;

        return $this;
    }

    /**
     * Get the value of originLocator
     */
    public function getOriginLocator(): ?string
    {
        return $this->originLocator;
    }

    /**
     * Set the value of originLocator
     */
    public function setOriginLocator(?string $originLocator): self
    {
        $this->originLocator = $originLocator;

        return $this;
    }

    /**
     * Get the value of destinationLocator
     */
    public function getDestinationLocator(): ?string
    {
        return $this->destinationLocator;
    }

    /**
     * Set the value of destinationLocator
     */
    public function setDestinationLocator(?string $destinationLocator): self
    {
        $this->destinationLocator = $destinationLocator;

        return $this;
    }
}
