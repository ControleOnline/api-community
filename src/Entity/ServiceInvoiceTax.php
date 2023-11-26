<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ServiceInvoiceTax
 *
 * @ORM\Table(name="service_invoice_tax", uniqueConstraints={@ORM\UniqueConstraint(name="invoice_id", columns={"invoice_id", "invoice_tax_id"}),@ORM\UniqueConstraint(name="invoice_type", columns={"issuer_id", "invoice_type", "invoice_id"})}, indexes={@ORM\Index(name="invoice_tax_id", columns={"invoice_tax_id"})})
 * @ORM\Entity
 *  @ORM\EntityListeners({App\Listener\LogListener::class})
 */
class ServiceInvoiceTax
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
     * @var \App\Entity\PurchasingInvoiceTax
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\PurchasingInvoiceTax", inversedBy="service_invoice_tax")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="invoice_tax_id", referencedColumnName="id")
     * })
     */
    private $service_invoice_tax;

    /**
     * @var \ControleOnline\Entity\PayInvoice
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\PayInvoice", inversedBy="service_invoice_tax")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="invoice_id", referencedColumnName="id")
     * })
     */
    private $invoice;

    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="issuer_id", referencedColumnName="id")
     * })
     */
    private $issuer;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_type", type="integer",  nullable=false)
     */
    private $invoiceType;

    public function __construct()
    {
        $this->invoice             = new \Doctrine\Common\Collections\ArrayCollection();
        $this->service_invoice_tax = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set service_invoice_tax
     *
     * @param \App\Entity\PurchasingInvoiceTax $service_invoice_tax
     * @return InvoiceTax
     */
    public function setServiceInvoiceTax(\App\Entity\PurchasingInvoiceTax $service_invoice_tax = null)
    {
        $this->service_invoice_tax = $service_invoice_tax;

        return $this;
    }

    /**
     * Get service_invoice_tax
     *
     * @return \App\Entity\PurchasingInvoiceTax
     */
    public function getServiceInvoiceTax()
    {
        return $this->service_invoice_tax;
    }

    /**
     * Set invoice
     *
     * @param \ControleOnline\Entity\PayInvoice $invoice
     * @return Invoice
     */
    public function setInvoice(\ControleOnline\Entity\PayInvoice $invoice = null)
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Get invoice
     *
     * @return \ControleOnline\Entity\PayInvoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * Set invoice_type
     *
     * @param integer $invoice_type
     * @return Invoice
     */
    public function setInvoiceType($invoice_type)
    {
        $this->invoiceType = $invoice_type;

        return $this;
    }

    /**
     * Get invoice_type
     *
     * @return integer
     */
    public function getInvoiceType()
    {
        return $this->invoiceType;
    }

    /**
     * Set issuer
     *
     * @param \App\Entity\People $issuer
     * @return People
     */
    public function setIssuer(\App\Entity\People $issuer = null)
    {
        $this->issuer = $issuer;

        return $this;
    }

    /**
     * Get issuer
     *
     * @return \App\Entity\People
     */
    public function getIssuer()
    {
        return $this->issuer;
    }
}
