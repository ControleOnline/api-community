<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Filter\PurchasingOrderEntityFilter;
use App\Entity\Order;
use stdClass;

/**
 * PurchasingOrder
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="orders")
 * @ORM\Entity (repositoryClass="App\Repository\PurchasingOrderRepository")
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/purchasing/orders/{id}',
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and object.canAccess(user))'
        ),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/{id}/choose-quote',
            controller: \App\Controller\ChooseQuoteAction::class
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/choose/{id}/quote',
            controller: \App\Controller\ChooseQuoteAction::class
        ),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/choose/{id}/payment',
            controller: \App\Controller\ChooseQuotePaymentAction::class
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/choose/{id}/retrieve',
            controller: \App\Controller\ChooseQuoteRetrieveAction::class
        ),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/choose/{id}/delivery',
            controller: \App\Controller\ChooseQuoteDeliveryAction::class
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/choose/{id}/payer',
            controller: \App\Controller\ChooseQuotePayerAction::class
        ),
        new Post(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and previous_object.canAccess(user))',
            uriTemplate: '/purchasing/orders/{id}/other-informations',
            controller: \App\Controller\AddOtherInformationsAction::class
        ),
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/{id}/detail/status',
            controller: \App\Controller\GetPurchasingStatusAction::class
        ),
        new Get(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and previous_object.canAccess(user))',
            uriTemplate: '/purchasing/orders/{id}/detail/summary',
            controller: \App\Controller\GetPurchasingOrderSummaryAction::class
        ),
        new Get(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and previous_object.canAccess(user))',
            uriTemplate: '/purchasing/orders/{id}/detail/quotation',
            controller: \App\Controller\GetPurchasingOrderQuotationAction::class
        ),
        new Get(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and previous_object.canAccess(user))',
            uriTemplate: '/purchasing/orders/{id}/detail/invoice',
            controller: \App\Controller\GetPurchasingOrderInvoiceAction::class
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders/{id}/update-status',
            controller: \App\Controller\UpdatePurchasingStatusAction::class
        ), new GetCollection(
            extraProperties: ['filters' => [PurchasingOrderEntityFilter::class]],
            security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/purchasing/orders'
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    filters: [\App\Filter\PurchasingOrderEntityFilter::class],
    normalizationContext: ['groups' => ['order_read']],
    denormalizationContext: ['groups' => ['order_write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['alterDate' => 'DESC'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['status' => 'exact', 'status.realStatus' => 'exact', 'invoice.invoice' => 'exact', 'client' => 'exact', 'provider' => 'exact'])]
class PurchasingOrder extends Order
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
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $client;
    /**
     * @var \DateTimeInterface
     * @ORM\Column(name="order_date", type="datetime",  nullable=false, columnDefinition="DATETIME")
     * @Groups({"order_read"})
     */
    private $orderDate;
    /**
     * @var string
     *
     * @ORM\Column(name="other_informations", type="json",  nullable=true)
     */
    private $otherInformations;
    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string",  nullable=true)
     */
    private $orderType;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="main_order_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $mainOrder;
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
     */
    private $contract;
    /**
     * @var \Doctrine\Common\Collections\Collection   
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\PurchasingOrderInvoice", mappedBy="order")   
     * @Groups({"logistic_read"})    
     */
    private $invoice;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PurchasingOrderInvoiceTax", mappedBy="order")
     * @Groups({"order_read"})
     */
    private $invoiceTax;
    /**
     * @ORM\Column(name="alter_date", type="datetime",  nullable=false)
     * @Groups({"order_read"})
     */
    private $alterDate;
    /**
     * @var \App\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $status;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_people_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $deliveryPeople;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="retrieve_people_id", referencedColumnName="id")
     * })
     * @Groups({"order_read"})
     */
    private $retrievePeople;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payer_people_id", referencedColumnName="id")
     * })
     */
    private $payer;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="provider_id", referencedColumnName="id")
     * })
     * @Groups({"order_read", "invoice_read"})
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
     * @Groups({"order_read"})
     */
    private $invoiceTotal = 0;
    /**
     * @var float
     *
     * @ORM\Column(name="cubage", type="float",  nullable=false)
     * @Groups({"order_read"})
     */
    private $cubage = 0;
    /**
     * @var string
     *
     * @ORM\Column(name="product_type", type="string",  nullable=false)
     * @Groups({"order_read"})
     */
    private $productType = '';
    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="string",  nullable=true)
     * @Groups({"order_read"})
     */
    private $comments;
    /**
     * @var boolean
     *
     * @ORM\Column(name="notified", type="boolean")
     */
    private $notified = false;
    public function __construct()
    {
        $this->orderDate = new \DateTime('now');
        $this->alterDate = new \DateTime('now');
        $this->orderPackage = new ArrayCollection();
        $this->invoiceTax = new ArrayCollection();
        $this->invoice = new ArrayCollection();
        $this->quotes = new ArrayCollection();
        $this->orderType = 'purchase';
        $this->contract = null;
        $this->otherInformations = json_encode(new stdClass());
    }
    public function resetId()
    {
        $this->id = null;
        $this->order_date = new \DateTime('now');
        $this->alter_date = new \DateTime('now');
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
     * @param \App\Entity\PurchasingOrderInvoiceTax $invoice_tax
     * @return Order
     */
    public function addAInvoiceTax(\App\Entity\PurchasingOrderInvoiceTax $invoice_tax)
    {
        $this->invoiceTax[] = $invoice_tax;
        return $this;
    }
    /**
     * Remove invoiceTax
     *
     * @param \App\Entity\PurchasingOrderInvoiceTax $invoice_tax
     */
    public function removeInvoiceTax(\App\Entity\PurchasingOrderInvoiceTax $invoice_tax)
    {
        $this->address->removeElement($invoice_tax);
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
     * Add PurchasingOrderInvoice
     *
     * @param \ControleOnline\Entity\PurchasingOrderInvoice $invoice
     * @return People
     */
    public function addInvoice(\ControleOnline\Entity\PurchasingOrderInvoice $invoice)
    {
        $this->invoice[] = $invoice;
        return $this;
    }
    /**
     * Remove PurchasingOrderInvoice
     *
     * @param \ControleOnline\Entity\PurchasingOrderInvoice $invoice
     */
    public function removeInvoice(\ControleOnline\Entity\PurchasingOrderInvoice $invoice)
    {
        $this->invoice->removeElement($invoice);
    }
    /**
     * Get PurchasingOrderInvoice
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
     * @return Order
     */
    public function setContract($contract)
    {
        $this->contract = $contract;
        return $this;
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
    public function canAccess(User $currentUser): bool
    {
        if (($client = $this->getClient()) === null) {
            return false;
        }
        return $currentUser->getPeople()->getPeopleCompany()->exists(function ($key, $element) use ($client) {
            return $element->getCompany() === $client;
        });
    }
    public function justOpened(): bool
    {
        return $this->getStatus()->getStatus() == 'quote' || $this->getStatus()->getStatus() == 'analysis' || $this->getStatus()->getStatus() == 'waiting client invoice tax';
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
    public function getOneInvoice(): ?Invoice
    {
        return ($invoiceOrders = $this->getInvoice()->first()) === false ? null : $invoiceOrders->getInvoice();
    }
    /**
     * Get otherInformations
     *
     * @return stdClass
     */
    public function getOtherInformations($decode = false)
    {
        return $decode ? (object) json_decode(is_array($this->otherInformations) ? json_encode($this->otherInformations) : $this->otherInformations) : $this->otherInformations;
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
        $otherInformations->{$key} = $value;
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
}
