<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * Retrieve
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="retrieve", indexes={@ORM\Index (name="IDX_order_id", columns={"order_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\RetrieveRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['retrieve_read', 'order_read']], denormalizationContext: ['groups' => ['retrieve_write', 'order_write']])]
class Retrieve
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
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder", inversedBy="retrieves")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $order;
    /**
     * @ORM\Column(name="retrieve_date", type="datetime",  nullable=false)
     * @Groups({"order_read"})
     */
    private $retrieveDate;
    /**
     * @var integer
     *
     * @ORM\Column(name="retrieve_number", type="integer",  nullable=true)
     * @Groups({"order_read"})
     */
    private $retrieveNumber;
    public function __construct()
    {
        $this->retrieveDate = new \DateTime('now');
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
     * Set order
     *
     * @param \App\Entity\SalesOrder $order
     * @return Order
     */
    public function setOrder(\App\Entity\SalesOrder $order = null)
    {
        $this->order = $order;
        return $this;
    }
    /**
     * Get order
     *
     * @return \App\Entity\Order
     */
    public function getOrder()
    {
        return $this->order;
    }
    /**
     * Set retrieve_date
     *
     * @param \DateTimeInterface $retrieve_date
     */
    public function setRetrieveDate(\DateTimeInterface $retrieve_date) : self
    {
        $this->retrieveDate = $retrieve_date;
        return $this;
    }
    /**
     * Get retrieve_date
     *
     */
    public function getRetrieveDate() : ?\DateTimeInterface
    {
        return $this->retrieveDate;
    }
    /**
     * Set retrieve_number
     *
     * @param integer $retrieveNumber
     * @return Order
     */
    public function setRetrieveNumber($retrieveNumber)
    {
        $this->retrieveNumber = $retrieveNumber;
        return $this;
    }
    /**
     * Get retrieve_number
     *
     * @return integer
     */
    public function getRetrieveNumber()
    {
        return $this->retrieveNumber;
    }
}
