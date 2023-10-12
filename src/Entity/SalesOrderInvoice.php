<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * SalesOrderInvoice
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="order_invoice", uniqueConstraints={@ORM\UniqueConstraint (name="order_id", columns={"order_id", "invoice_id"})}, indexes={@ORM\Index (name="invoice_id", columns={"invoice_id"})})
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['order_invoice_read']], denormalizationContext: ['groups' => ['order_invoice_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['order.id' => 'exact'])]
class SalesOrderInvoice
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"order_invoice_read","order_read"})
     */
    private $id;
    /**
     * @var \ControleOnline\Entity\ReceiveInvoice
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\ReceiveInvoice", inversedBy="order")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="invoice_id", referencedColumnName="id")
     * })
     * @Groups({"order_invoice_read","order_read"}) 
     */
    private $invoice;
    /**
     * @var \App\Entity\SalesOrder
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder", inversedBy="invoice")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     * @Groups({"invoice_read","order_invoice_read"})
     */
    private $order;
    /**
     * @var float
     *
     * @ORM\Column(name="real_price", type="float",  nullable=false)
     * @Groups({"order_invoice_read","order_read"})
     * 
     */
    private $realPrice = 0;
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
     * Set invoice
     *
     * @param \ControleOnline\Entity\ReceiveInvoice $invoice
     * @return SalesOrderInvoice
     */
    public function setInvoice(\ControleOnline\Entity\ReceiveInvoice $invoice = null)
    {
        $this->invoice = $invoice;
        return $this;
    }
    /**
     * Get invoice
     *
     * @return \ControleOnline\Entity\ReceiveInvoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }
    /**
     * Set order
     *
     * @param \App\Entity\SalesOrder $order
     * @return SalesOrderInvoice
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
     * Set realPrice
     *
     * @param float $realPrice
     * @return SalesOrderInvoice
     */
    public function setRealPrice($realPrice)
    {
        $this->realPrice = $realPrice;
        return $this;
    }
    /**
     * Get realPrice
     *
     * @return float
     */
    public function getRealPrice()
    {
        return $this->realPrice;
    }
}
