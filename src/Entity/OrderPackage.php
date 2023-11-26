<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * OrderPackage
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="order_package", indexes={@ORM\Index (name="IDX_order_id", columns={"order_id"})})
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['order_package_read']], denormalizationContext: ['groups' => ['order_package_write']])]
class OrderPackage
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
     * @var \App\Entity\SalesOrder
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder", inversedBy="orderPackage")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $order;
    /**
     * @var float
     *
     * @ORM\Column(name="qtd", type="float",  nullable=false)
     * @Groups({"order_read"})
     */
    private $qtd;
    /**
     * @var float
     *
     * @ORM\Column(name="height", type="float",  nullable=false)
     * @Groups({"order_read"})
     */
    private $height;
    /**
     * @var float
     *
     * @ORM\Column(name="width", type="float",  nullable=false)
     * @Groups({"order_read"})
     */
    private $width;
    /**
     * @var float
     *
     * @ORM\Column(name="depth", type="float",  nullable=false)
     * @Groups({"order_read"})
     */
    private $depth;
    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float",  nullable=false)
     * @Groups({"order_read"})
     */
    private $weight;
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
     * Set order
     *
     * @param \App\Entity\SalesOrder $order
     * @return OrderPackage
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
     * Set qtd
     *
     * @param string $qtd
     * @return OrderPackage
     */
    public function setQtd($qtd)
    {
        $this->qtd = $qtd;
        return $this;
    }
    /**
     * Get qtd
     *
     * @return float
     */
    public function getQtd()
    {
        return $this->qtd;
    }
    /**
     * Set height
     *
     * @param string $height
     * @return OrderPackage
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }
    /**
     * Get height
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }
    /**
     * Set width
     *
     * @param string $width
     * @return OrderPackage
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }
    /**
     * Get width
     *
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }
    /**
     * Set depth
     *
     * @param string $depth
     * @return OrderPackage
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
        return $this;
    }
    /**
     * Get depth
     *
     * @return float
     */
    public function getDepth()
    {
        return $this->depth;
    }
    /**
     * Set weight
     *
     * @param string $weight
     * @return OrderPackage
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }
    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }
}
