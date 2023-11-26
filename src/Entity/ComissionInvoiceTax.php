<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * ComissionInvoiceTax
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="invoice_tax")
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['invoice_tax_read']], denormalizationContext: ['groups' => ['invoice_tax_write']])]
class ComissionInvoiceTax
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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ComissionOrderInvoiceTax", mappedBy="invoiceTax")
     */
    private $order;
    /**
     * @var string
     *
     * @ORM\Column(name="invoice", type="string",  nullable=false)
     */
    private $invoice;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ServiceInvoiceTax", mappedBy="service_invoice_tax")
     */
    private $service_invoice_tax;
    /**
     * @var string
     *
     * @ORM\Column(name="invoice_number", type="integer",  nullable=false)
     * @Groups({"order_read"})
     */
    private $invoiceNumber;
    public function __construct()
    {
        $this->order = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add PurchasingOrderInvoice
     *
     * @param \ControleOnline\Entity\PurchasingOrderInvoice $order
     * @return People
     */
    public function addOrder(\ControleOnline\Entity\PurchasingOrderInvoice $order)
    {
        $this->order[] = $order;
        return $this;
    }
    /**
     * Remove PurchasingOrderInvoice
     *
     * @param \ControleOnline\Entity\PurchasingOrderInvoice $order
     */
    public function removeOrder(\ControleOnline\Entity\PurchasingOrderInvoice $order)
    {
        $this->order->removeElement($order);
    }
    /**
     * Get PurchasingOrderInvoice
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrder()
    {
        return $this->order;
    }
    /**
     * Set invoice
     *
     * @param string $invoice
     * @return Order
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
        return $this;
    }
    /**
     * Get invoice
     *
     * @return string
     */
    public function getInvoice()
    {
        return $this->invoice;
    }
    /**
     * Set invoiceNumber
     *
     * @param integer $invoice_number
     * @return Order
     */
    public function setInvoiceNumber($invoice_number)
    {
        $this->invoiceNumber = $invoice_number;
        return $this;
    }
    /**
     * Get invoiceNumber
     *
     * @return integer
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }
    /**
     * Add ServiceInvoiceTax
     *
     * @param \App\Entity\ServiceInvoiceTax $service_invoice_tax
     * @return InvoiceTax
     */
    public function addServiceInvoiceTax(\App\Entity\ServiceInvoiceTax $service_invoice_tax)
    {
        $this->service_invoice_tax[] = $service_invoice_tax;
        return $this;
    }
    /**
     * Remove ServiceInvoiceTax
     *
     * @param \App\Entity\ServiceInvoiceTax $service_invoice_tax
     */
    public function removeServiceInvoiceTax(\App\Entity\ServiceInvoiceTax $service_invoice_tax)
    {
        $this->service_invoice_tax->removeElement($service_invoice_tax);
    }
    /**
     * Get ServiceInvoiceTax
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getServiceInvoiceTax()
    {
        return $this->service_invoice_tax;
    }
}
