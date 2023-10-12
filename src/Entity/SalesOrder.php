<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Filter\SalesOrderEntityFilter;
use App\Entity\Order;
use \DateTime;
use stdClass;

/**
 * SalesOrder
 *
 * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ApiResource(
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "jsonhal", "csv"={"text/csv"}},
 *     },  
 *     normalizationContext  ={"groups"={"order_read"}},
 *     denormalizationContext={"groups"={"order_write"}},
 *     collectionOperations  ={
 *         "get" ={ 
 *             "attributes"    ={"filters"={App\Filter\SalesOrderEntityFilter::class}}, 
 *             "access_control"="is_granted('ROLE_CLIENT')",
 *             "path"="/sales/orders",
 *          },
 *     },
 *     itemOperations        ={
 *         "get"         ={
 *           "path"          ="/sales/orders/{id}",
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *         },
 *         "choose_quote"={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="PUT",
 *           "path"          ="/sales/orders/{id}/choose-quote",
 *           "controller"    =App\Controller\ChooseQuoteAction::class,
 *         }, 
 *         "get_competitor"  ={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="POST",
 *           "path"          ="/sales/orders/{id}/competitor",
 *           "controller"    =App\Controller\GetCompetitorAction::class,
 *         }, 
 *         "route_time"={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="PUT",
 *           "path"          ="/sales/orders/{id}/route/time",
 *           "controller"    =App\Controller\ChooseRouteTimeAction::class,
 *         },  
 *         "alter_quote"={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="PUT",
 *           "path"          ="/sales/orders/{id}/alter/quote",
 *           "controller"    =App\Controller\AlterQuoteAction::class,
 *         }, 
 *         "get_tag"  ={
 *           "security"    ="is_granted('IS_AUTHENTICATED_ANONYMOUSLY')", 
 *           "method"        ="GET",
 *           "path"          ="/carrier_tags/{id}/download-tag",
 *           "controller"    =App\Controller\GetCarrierTagAction::class,
 *         }, 
 *         "add_address"={
 *           "access_control"="is_granted('ROLE_CLIENT')",
 *           "method"        ="PUT",
 *           "path"          ="/sales/orders/{id}/add-address",
 *           "controller"    =App\Controller\ChangeAddressAction::class,
 *         },
 *         "get_status"  ={
 *           "access_control"="is_granted('ROLE_CLIENT')",
 *           "method"        ="GET",
 *           "path"          ="/sales/orders/{id}/detail/status",
 *           "controller"    =App\Controller\GetSalesStatusAction::class,
 *         },
 *         "create_dacte"  ={
 *           "access_control"="is_granted('ROLE_CLIENT')",
 *           "method"        ="POST",
 *           "path"          ="/sales/orders/{id}/detail/create-dacte",
 *           "controller"    =App\Controller\CreateDacteAction::class,
 *         }, 
 *         "get_summary" ={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="GET",
 *           "path"          ="/sales/orders/{id}/detail/summary",
 *           "controller"    =App\Controller\GetSalesOrderSummaryAction::class,
 *         },
 *         "get_quotation" ={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="GET",
 *           "path"          ="/sales/orders/{id}/detail/quotation",
 *           "controller"    =App\Controller\GetSalesOrderQuotationAction::class,
 *         },
 *         "add_other_informations" ={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="POST",
 *           "path"          ="/sales/orders/{id}/other-informations",
 *           "controller"    =App\Controller\AddOtherInformationsAction::class,
 *         }, 
 *         "get_invoice"   ={
 *           "access_control"="is_granted('ROLE_ADMIN') or (is_granted('ROLE_CLIENT') and previous_object.canAccess(user))",
 *           "method"        ="GET",
 *           "path"          ="/sales/orders/{id}/detail/invoice",
 *           "controller"    =App\Controller\GetSalesOrderInvoiceAction::class,
 *         },
 *         "update_status"={
 *            "access_control"="is_granted('ROLE_CLIENT')",
 *            "method"        ="PUT",
 *            "path"          ="/sales/orders/{id}/update-status",
 *            "controller"    =App\Controller\UpdateSalesStatusAction::class,
 *         },
 *         "remove_invoice"={
 *            "access_control"="is_granted('ROLE_CLIENT')",
 *            "method"        ="PUT",
 *            "path"          ="/sales/orders/{id}/remove-invoice-tax",
 *            "controller"    =App\Controller\RemoveInvoiceTaxAction::class,
 *         }, 
 *         "update_dacte"={
 *            "access_control"="is_granted('ROLE_CLIENT')",
 *            "method"        ="PUT",
 *            "path"          ="/sales/orders/{id}/detail/update-dacte",
 *            "controller"    =App\Controller\UpdateSalesOrderDacteAction::class,
 *         },
 *         "update_deadline"={
 *            "access_control"="is_granted('ROLE_CLIENT')",
 *            "method"        ="PUT",
 *            "path"          ="/sales/orders/{id}/detail/update-deadline",
 *            "controller"    =App\Controller\UpdateSalesOrderDeadlineAction::class,
 *         },
 *         "update_remote"={
 *            "method"        ="PUT",
 *            "path"          ="/sales/orders/{id}/detail/update-remote",
 *            "controller"    =App\Controller\UpdateSalesOrderRemoteAction::class,
 *         },
 *         "update_fields"         ={
 *           "access_control"="is_granted('ROLE_CLIENT')",
 *           "denormalization_context"={"groups"={"order_write"}},
 *           "method"        ="PUT",
 *           "path"          ="/sales/orders/fields/{id}",
 *         },
 *     }
 * )
 * @ORM\Table(name="orders", uniqueConstraints={@ORM\UniqueConstraint(name="discount_id", columns={"discount_coupon_id"})}, indexes={@ORM\Index(name="adress_destination_id", columns={"address_destination_id"}), @ORM\Index(name="notified", columns={"notified"}), @ORM\Index(name="delivery_contact_id", columns={"delivery_contact_id"}), @ORM\Index(name="contract_id", columns={"contract_id"}), @ORM\Index(name="delivery_people_id", columns={"delivery_people_id"}), @ORM\Index(name="status_id", columns={"status_id"}), @ORM\Index(name="order_date", columns={"order_date"}), @ORM\Index(name="provider_id", columns={"provider_id"}), @ORM\Index(name="quote_id", columns={"quote_id", "provider_id"}), @ORM\Index(name="adress_origin_id", columns={"address_origin_id"}), @ORM\Index(name="retrieve_contact_id", columns={"retrieve_contact_id"}), @ORM\Index(name="main_order_id", columns={"main_order_id"}), @ORM\Index(name="retrieve_people_id", columns={"retrieve_people_id"}), @ORM\Index(name="payer_people_id", columns={"payer_people_id"}), @ORM\Index(name="client_id", columns={"client_id"}), @ORM\Index(name="alter_date", columns={"alter_date"}), @ORM\Index(name="IDX_E52FFDEEDB805178", columns={"quote_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\SalesOrderRepository")
 * @ApiFilter(
 *   OrderFilter::class , properties={"alterDate": "DESC"},
 * )
 * @ApiFilter(
 *   SearchFilter::class, properties={
 *     "status"                                 : "exact",
 *     "status.realStatus"                      : "exact",
 *     "orderQueue.status.realStatus"           : "exact", 
 *     "orderQueue.queue.hardwareQueue.hardware"  : "exact",  
 *     "status.status"                          : "exact",
 *     "invoice.invoice"                        : "exact",
 *     "client"                                 : "exact",
 *     "provider"                               : "exact",
 *     "quote.carrier"                          : "exact", 
 *   }
 * )
 * @ApiFilter(
 *   App\Filter\SalesOrderEntityFilter::class
 * )
 */

class SalesOrder extends Order
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"order_read","company_expense_read","task_read","coupon_read","logistic_read","order_invoice_read"})
     */
    private $id;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * })
     * @Groups({"order_read", "invoice_read", "task_read"})
     */
    private $client;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(name="order_date", type="datetime",  nullable=false, columnDefinition="DATETIME")
     * @Groups({"order_read"})
     */
    private $orderDate;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\SalesOrderInvoice", mappedBy="order")
     * @Groups({"order_read"}) 
     */
    private $invoice;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Task", mappedBy="order")
     * @Groups({"order_read"})
     */
    private $task;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\SalesOrderInvoiceTax", mappedBy="order")
     * @Groups({"order_read"})
     */
    private $invoiceTax;

    /**
     * @ORM\Column(name="alter_date", type="datetime",  nullable=false)
     * @Groups({"hardware_read","order_read"})
     */
    private $alterDate;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="estimated_parking_date", type="datetime",  nullable=false, columnDefinition="DATETIME")
     * @Groups({"order_read","order_write"})
     */
    private $estimatedParkingDate;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="parking_date", type="datetime",  nullable=false, columnDefinition="DATETIME")
     * @Groups({"order_read","order_write"})
     */
    private $parkingDate;

    /**
     * @var \App\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     * @Groups({"hardware_read","order_read"})
     */
    private $status;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_people_id", referencedColumnName="id")
     * })
     * @Groups({"hardware_read","order_read"})
     */
    private $deliveryPeople;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="retrieve_people_id", referencedColumnName="id")
     * })
     * @Groups({"hardware_read","order_read"})
     */
    private $retrievePeople;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string",  nullable=true)
     */
    private $orderType;


    /**
     * @var string
     *
     * @ORM\Column(name="app", type="string",  nullable=true)
     * @Groups({"hardware_read","order_read"}) 
     */
    private $app;

    /**
     * @var string
     *
     * @ORM\Column(name="other_informations", type="json",  nullable=true)
     * @Groups({"order_read"}) 
     */
    private $otherInformations;

    /**
     * @var \App\Entity\SalesOrder
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="main_order_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $mainOrder;

    /**
     * @var \App\Entity\DiscountCoupon
     *
     * @ORM\ManyToOne(targetEntity="DiscountCoupon")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="discount_coupon_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $discountCoupon;


    /**
     * @var integer
     *
     * @ORM\Column(name="main_order_id", type="integer",  nullable=true)
     * @Groups({"order_read"})
     */
    private $mainOrderId;

    /**
     * @var \App\Entity\Contract
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Contract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","task_read","logistic_read"}) 
     */
    private $contract;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payer_people_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","task_read","invoice_read"})
     */
    private $payer;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="provider_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","invoice_read", "task_read"})
     */
    private $provider;

    /**
     * @var \App\Entity\Quotation
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Quotation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="quote_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $quote;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Quotation", mappedBy="order")
     */

    private $quotes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Retrieve", mappedBy="order")
     */

    private $retrieves;

    /**
     * @var \App\Entity\Address
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="address_origin_id", referencedColumnName="id")
     * })
     */
    private $addressOrigin;

    /**
     * @var \App\Entity\Address
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Address")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="address_destination_id", referencedColumnName="id")
     * })
     */
    private $addressDestination;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="retrieve_contact_id", referencedColumnName="id")
     * })
     */
    private $retrieveContact;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_contact_id", referencedColumnName="id")
     * })
     */
    private $deliveryContact;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\OrderPackage", mappedBy="order")
     */
    private $orderPackage;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float",  nullable=false)
     * @Groups({"order_read"})
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="invoice_total", type="float",  nullable=false)
     * @Groups({"order_read","order_write"})
     */
    private $invoiceTotal = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="cubage", type="float",  nullable=false)
     * @Groups({"order_read","order_write"})
     */
    private $cubage = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="product_type", type="string",  nullable=false)
     * @Groups({"order_read","order_write"})
     */
    private $productType = '';

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="string",  nullable=true)
     * @Groups({"order_read","order_write"})
     */
    private $comments;

    /**
     * @var boolean
     *
     * @ORM\Column(name="notified", type="boolean")
     */
    private $notified = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(targetEntity="App\Entity\OrderTracking", mappedBy="order")
     * @ApiSubresource()
     */
    private $tracking;



    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\OrderQueue", mappedBy="order")
     * @Groups({"order_read"}) 
     */
    private $orderQueue;



    public function __construct()
    {
        $this->orderDate    = new \DateTime('now');
        $this->alterDate    = new \DateTime('now');
        $this->orderPackage = new ArrayCollection();
        $this->invoiceTax   = new ArrayCollection();
        $this->invoice      = new ArrayCollection();
        $this->quotes       = new ArrayCollection();
        $this->retrieves    = new ArrayCollection();
        $this->tracking     = new ArrayCollection();
        $this->task         = new ArrayCollection();
        $this->orderQueue   = new ArrayCollection();
        // $this->parkingDate  = new \DateTime('now');
        $this->orderType    = 'sale';
        $this->otherInformations = json_encode(new stdClass());
    }

    public function resetId()
    {
        $this->id          = null;
        $this->orderDate   = new \DateTime('now');
        $this->alterDate   = new \DateTime('now');
        // $this->parkingDate = new \DateTime('now');
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
     * Set status
     *
     * @param \App\Entity\Status $status
     * @return Order
     */
    public function setStatus(\App\Entity\Status $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \App\Entity\Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set client
     *
     * @param \App\Entity\People $client
     * @return Order
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
     * Set provider
     *
     * @param \App\Entity\People $provider
     * @return Order
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
     * Set price
     *
     * @param float $price
     * @return Order
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
     * Set addressOrigin
     *
     * @param \App\Entity\Address $address_origin
     * @return Order
     */
    public function setAddressOrigin(\App\Entity\Address $address_origin = null)
    {
        $this->addressOrigin = $address_origin;

        return $this;
    }

    /**
     * Get addressOrigin
     *
     * @return \App\Entity\Address
     */
    public function getAddressOrigin()
    {
        return $this->addressOrigin;
    }

    /**
     * Set addressDestination
     *
     * @param \App\Entity\Address $address_destination
     * @return Order
     */
    public function setAddressDestination(\App\Entity\Address $address_destination = null)
    {
        $this->addressDestination = $address_destination;

        return $this;
    }

    /**
     * Get quote
     *
     * @return \App\Entity\Address
     */
    public function getAddressDestination()
    {
        return $this->addressDestination;
    }

    /**
     * Get retrieveContact
     *
     * @return \App\Entity\People
     */
    public function getRetrieveContact()
    {
        return $this->retrieveContact;
    }

    /**
     * Set retrieveContact
     *
     * @param \App\Entity\People $retrieve_contact
     * @return Order
     */
    public function setRetrieveContact(\App\Entity\People $retrieve_contact = null)
    {
        $this->retrieveContact = $retrieve_contact;

        return $this;
    }

    /**
     * Get deliveryContact
     *
     * @return \App\Entity\People
     */
    public function getDeliveryContact()
    {
        return $this->deliveryContact;
    }

    /**
     * Set deliveryContact
     *
     * @param \App\Entity\People $delivery_contact
     * @return Order
     */
    public function setDeliveryContact(\App\Entity\People $delivery_contact = null)
    {
        $this->deliveryContact = $delivery_contact;

        return $this;
    }

    /**
     * Set payer
     *
     * @param \App\Entity\People $payer
     * @return Order
     */
    public function setPayer(\App\Entity\People $payer = null)
    {
        $this->payer = $payer;

        return $this;
    }

    /**
     * Get payer
     *
     * @return \App\Entity\People
     */
    public function getPayer()
    {
        return $this->payer;
    }

    /**
     * Set discountCoupon
     *
     * @param \App\Entity\DiscountCoupon $discount_coupon
     * @return Order
     */
    public function setDiscountCoupon(\App\Entity\DiscountCoupon $discount_coupon = null)
    {
        $this->discountCoupon = $discount_coupon;

        return $this;
    }

    /**
     * Get discountCoupon
     *
     * @return \App\Entity\DiscountCoupon
     */
    public function getDiscountCoupon()
    {
        return $this->discountCoupon;
    }

    /**
     * Set deliveryPeople
     *
     * @param \App\Entity\People $delivery_people
     * @return Order
     */
    public function setDeliveryPeople(\App\Entity\People $delivery_people = null)
    {
        $this->deliveryPeople = $delivery_people;

        return $this;
    }

    /**
     * Get deliveryPeople
     *
     * @return \App\Entity\People
     */
    public function getDeliveryPeople()
    {
        return $this->deliveryPeople;
    }

    /**
     * Set retrievePeople
     *
     * @param \App\Entity\People $retrieve_people
     * @return Order
     */
    public function setRetrievePeople(\App\Entity\People $retrieve_people = null): self
    {
        $this->retrievePeople = $retrieve_people;

        return $this;
    }

    /**
     * Get retrievePeople
     *
     * @return \App\Entity\People
     */
    public function getRetrievePeople(): ?People
    {
        return $this->retrievePeople;
    }

    /**
     * Set comments
     *
     * @param string $comments
     * @return Order
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Get otherInformations
     *
     * @return stdClass
     */
    public function getOtherInformations($decode = false)
    {
        return $decode ? (object) json_decode((is_array($this->otherInformations) ? json_encode($this->otherInformations) : $this->otherInformations)) : $this->otherInformations;
    }

    /**
     * Set comments
     *
     * @param string $otherInformations
     * @return Order
     */
    public function addOtherInformations($key, $value)
    {
        $otherInformations = $this->getOtherInformations(true);
        $otherInformations->$key = $value;
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }

    /**
     * Set comments
     *
     * @param string $otherInformations
     * @return Order
     */
    public function setOtherInformations(stdClass $otherInformations)
    {
        $this->otherInformations = json_encode($otherInformations);
        return $this;
    }


    /**
     * Get orderDate
     *
     * @return \DateTimeInterface
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * Set alter_date
     *
     * @param \DateTimeInterface $alter_date
     */
    public function setAlterDate(\DateTimeInterface $alter_date): self
    {
        $this->alterDate = $alter_date;

        return $this;
    }

    /**
     * Get alter_date
     *
     */
    public function getAlterDate(): ?\DateTimeInterface
    {
        return $this->alterDate;
    }

    /**
     * Add orderPackage
     *
     * @param \App\Entity\OrderPackage $order_package
     * @return Order
     */
    public function addOrderPackage(\App\Entity\OrderPackage $order_package)
    {
        $this->orderPackage[] = $order_package;

        return $this;
    }

    /**
     * Remove orderPackage
     *
     * @param \App\Entity\OrderPackage $order_package
     */
    public function removeOrderPackage(\App\Entity\OrderPackage $order_package)
    {
        $this->orderPackage->removeElement($order_package);
    }

    /**
     * Get orderPackage
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderPackage()
    {
        return $this->orderPackage;
    }

    /**
     * Add invoiceTax
     *
     * @param \App\Entity\SalesOrderInvoiceTax $invoice_tax
     * @return Order
     */
    public function addAInvoiceTax(\App\Entity\SalesOrderInvoiceTax $invoice_tax)
    {
        $this->invoiceTax[] = $invoice_tax;

        return $this;
    }

    /**
     * Remove invoiceTax
     *
     * @param \App\Entity\SalesOrderInvoiceTax $invoice_tax
     */
    public function removeInvoiceTax(\App\Entity\SalesOrderInvoiceTax $invoice_tax)
    {
        $this->invoiceTax->removeElement($invoice_tax);
    }

    /**
     * Get invoiceTax
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInvoiceTax()
    {
        return $this->invoiceTax;
    }



    /**
     * Get invoiceTax
     *
     * @return \App\Entity\SalesInvoiceTax
     */
    public function getClientSalesInvoiceTax()
    {
        foreach ($this->getInvoiceTax() as $invoice) {
            if ($invoice->getInvoiceType() == 55) {
                return $invoice;
            }
        }
    }

    /**
     * Get invoiceTax
     *
     * @return \App\Entity\SalesInvoiceTax
     */
    public function getClientInvoiceTax()
    {
        foreach ($this->getInvoiceTax() as $invoice) {
            if ($invoice->getInvoiceType() == 55) {
                return $invoice->getInvoiceTax();
            }
        }
    }
    /**
     * Get invoiceTax
     *
     * @return \App\Entity\SalesInvoiceTax
     */
    public function getCarrierInvoiceTax()
    {
        foreach ($this->getInvoiceTax() as $invoice) {
            if ($invoice->getInvoiceType() == 57) {
                return $invoice->getInvoiceTax();
            }
        }
    }

    /**
     * Add SalesOrderInvoice
     *
     * @param \App\Entity\SalesOrderInvoice $invoice
     * @return People
     */
    public function addInvoice(\App\Entity\SalesOrderInvoice $invoice)
    {
        $this->invoice[] = $invoice;

        return $this;
    }

    /**
     * Remove SalesOrderInvoice
     *
     * @param \App\Entity\SalesOrderInvoice $invoice
     */
    public function removeInvoice(\App\Entity\SalesOrderInvoice $invoice)
    {
        $this->invoice->removeElement($invoice);
    }

    /**
     * Get SalesOrderInvoice
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * Set invoiceTotal
     *
     * @param float $invoice_total
     * @return Order
     */
    public function setInvoiceTotal($invoice_total)
    {
        $this->invoiceTotal = $invoice_total;

        return $this;
    }

    /**
     * Get invoiceTotal
     *
     * @return float
     */
    public function getInvoiceTotal()
    {
        return $this->invoiceTotal;
    }

    /**
     * Set cubage
     *
     * @param float $cubage
     * @return Order
     */
    public function setCubage($cubage)
    {
        $this->cubage = $cubage;

        return $this;
    }

    /**
     * Get cubage
     *
     * @return float
     */
    public function getCubage()
    {
        return $this->cubage;
    }

    /**
     * Set product_type
     *
     * @param string $product_type
     * @return Order
     */
    public function setProductType($product_type)
    {
        $this->productType = $product_type;

        return $this;
    }

    /**
     * Get product_type
     *
     * @return string
     */
    public function getProductType()
    {
        return $this->productType;
    }

    /**
     * Add quotes
     *
     * @param \App\Entity\Quotation $quotes
     * @return Order
     */
    public function addAQuotes(\App\Entity\Quotation $quotes)
    {
        $this->quotes[] = $quotes;

        return $this;
    }

    /**
     * Remove quotes
     *
     * @param \App\Entity\Quotation $quotes
     */
    public function removeQuotes(\App\Entity\Quotation $quotes)
    {
        $this->quotes->removeElement($quotes);
    }

    /**
     * Get quotes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuotes()
    {
        return $this->quotes;
    }

    /**
     * Add retrieves
     *
     * @param \App\Entity\Retrieve $retrieves
     * @return Order
     */
    public function addARetrieves(\App\Entity\Retrieve $retrieves)
    {
        $this->retrieves[] = $retrieves;

        return $this;
    }

    /**
     * Remove retrieves
     *
     * @param \App\Entity\Retrieve $retrieves
     */
    public function removeRetrieves(\App\Entity\Retrieve $retrieves)
    {
        $this->retrieves->removeElement($retrieves);
    }

    /**
     * Get retrieves
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRetrieves()
    {
        return $this->retrieves;
    }

    /**
     * Get Notified
     *
     * @return boolean
     */
    public function getNotified()
    {
        return $this->notified;
    }

    /**
     * Set Notified
     *
     * @param boolean $notified
     * @return People
     */
    public function setNotified($notified)
    {
        $this->notified = $notified ? 1 : 0;

        return $this;
    }

    /**
     * Set orderType
     *
     * @param string $orderType
     * @return Order
     */
    public function setOrderType($order_type)
    {
        $this->orderType = $order_type;

        return $this;
    }

    /**
     * Get orderType
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * Set app
     *
     * @param string $app
     * @return Order
     */
    public function setApp($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get app
     *
     * @return string
     */
    public function getApp()
    {
        return $this->app;
    }



    /**
     * Set mainOrder
     *
     * @param \App\Entity\SalesOrder $mainOrder
     * @return Order
     */
    public function setMainOrder(\App\Entity\SalesOrder $main_order = null)
    {
        $this->mainOrder = $main_order;

        return $this;
    }

    /**
     * Get mainOrder
     *
     * @return \App\Entity\SalesOrder
     */
    public function getMainOrder()
    {
        return $this->mainOrder;
    }

    /**
     * Set mainOrderId
     *
     * @param integer $mainOrderId
     * @return Order
     */
    public function setMainOrderId($mainOrderId)
    {
        $this->mainOrderId = $mainOrderId;

        return $this;
    }

    /**
     * Get mainOrderId
     *
     * @return integer
     */
    public function getMainOrderId()
    {
        return $this->mainOrderId;
    }

    /**
     * Set contract
     *
     * @param \App\Entity\Contract $contract
     * @return SalesOrder
     */
    public function setContract($contract)
    {
        $this->contract = $contract;

        return $this;
    }

    public function getInvoiceByStatus(array $status)
    {
        foreach ($this->getInvoice() as $purchasingOrderInvoice) {
            $invoice = $purchasingOrderInvoice->getInvoice();
            if (in_array($invoice->getStatus()->getStatus(), $status)) {
                return $invoice;
            }
        }
    }
    /**
     * Get contract
     *
     * @return \App\Entity\Contract
     */
    public function getContract()
    {
        return $this->contract;
    }

    public function canAccess($currentUser): bool
    {
        if (($provider = $this->getProvider()) === null)
            return false;

        return $currentUser->getPeople()->getPeopleCompany()->exists(
            function ($key, $element) use ($provider) {
                return $element->getCompany() === $provider;
            }
        );
    }

    public function justOpened(): bool
    {
        return $this->getStatus()->getStatus() == 'quote';
    }

    /**
     * Get tracking
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTracking()
    {
        return $this->tracking;
    }

    public function getOneInvoice(): ?Invoice
    {
        return (($invoiceOrders = $this->getInvoice()->first()) === false) ?
            null : $invoiceOrders->getInvoice();
    }

    /**
     * Add Task
     *
     * @param \App\Entity\Task $task
     * @return SalesOrder
     */
    public function addTask(\App\Entity\Task $task)
    {
        $this->task[] = $task;

        return $this;
    }

    /**
     * Remove Task
     *
     * @param \App\Entity\Task $task
     */
    public function removeTask(\App\Entity\Task $task)
    {
        $this->task->removeElement($task);
    }

    /**
     * Get Task
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Get the value of estimatedParkingDate
     */
    public function getEstimatedParkingDate(): ?\DateTime
    {
        return $this->estimatedParkingDate;
    }

    /**
     * Set the value of estimatedParkingDate
     */
    public function setEstimatedParkingDate(?\DateTime $estimatedParkingDate): self
    {
        $this->estimatedParkingDate = $estimatedParkingDate;

        return $this;
    }

    /**
     * Get the value of parkingDate
     *
     * @return  \DateTime|null
     */
    public function getParkingDate()
    {
        return $this->parkingDate;
    }

    /**
     * Set the value of parkingDate
     *
     * @param  \DateTime|null  $parkingDate
     *
     * @return  self
     */
    public function setParkingDate($parkingDate)
    {
        $this->parkingDate = $parkingDate;

        return $this;
    }


    /**
     * Add OrderQueue
     *
     * @param \App\Entity\OrderQueue $invoice_tax
     * @return Order
     */
    public function addAOrderQueue(\App\Entity\OrderQueue $orderQueue)
    {
        $this->orderQueue[] = $orderQueue;

        return $this;
    }

    /**
     * Remove OrderQueue
     *
     * @param \App\Entity\OrderQueue $invoice_tax
     */
    public function removeOrderQueue(\App\Entity\OrderQueue $orderQueue)
    {
        $this->orderQueue->removeElement($orderQueue);
    }

    /**
     * Get OrderQueue
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderQueue()
    {
        return $this->orderQueue;
    }
}
