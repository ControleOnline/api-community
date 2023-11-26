<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\SalesOrder as Orders;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;


/**
 * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ApiResource(
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "jsonhal", "csv"={"text/csv"}},
 *          "access_control"="is_granted('ROLE_CLIENT')"
 *     }, 
 *     normalizationContext  ={"groups"={"order_queue_read"}},
 *     denormalizationContext={"groups"={"order_queue_write"}},
 *     attributes            ={"access_control"="is_granted('ROLE_CLIENT')"},
 *     collectionOperations  ={
 *          "get"              ={
 *            "access_control"="is_granted('ROLE_CLIENT')", 
 *          },
 *     },
 *     itemOperations        ={
 *         "get"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')", 
 *         },
 *         "put"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')",  
 *         },
 *         "delete"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')",  
 *         }, 
 *     }
 * )
 * @ORM\Table(name="order_queue", indexes={@ORM\Index(name="status_id", columns={"status_id"}), @ORM\Index(name="queue_id", columns={"queue_id"}), @ORM\Index(name="people_id", columns={"order_id"})})
 * @ORM\Entity
 */


class OrderQueue
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"order_read","order_queue_read", "order_queue_write"}) 
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="priority", type="string", length=0, nullable=false)
     * @Groups({"order_read","order_queue_read", "order_queue_write"})  
     */
    private $priority;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="register_time", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     * @Groups({"order_read","order_queue_read", "order_queue_write"})   
     */
    private $registerTime = 'current_timestamp()';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_time", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     * @Groups({"order_read","order_queue_read", "order_queue_write"})  
     */
    private $updateTime = 'current_timestamp()';

    /**
     * @var Orders
     *
     * @ORM\ManyToOne(targetEntity="SalesOrder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     * @Groups({"order_queue_read", "order_queue_write"})  
     */
    private $order;

    /**
     * @var ControleOnline\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","order_queue_read", "order_queue_write"})  
     */
    private $status;

    /**
     * @var \Queue
     *
     * @ORM\ManyToOne(targetEntity="Queue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="queue_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","order_queue_read", "order_queue_write"})  
     */
    private $queue;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the value of priority
     */
    public function setPriority($priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get the value of registerTime
     */
    public function getRegisterTime()
    {
        return $this->registerTime;
    }

    /**
     * Set the value of registerTime
     */
    public function setRegisterTime($registerTime): self
    {
        $this->registerTime = $registerTime;

        return $this;
    }

    /**
     * Get the value of updateTime
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * Set the value of updateTime
     */
    public function setUpdateTime($updateTime): self
    {
        $this->updateTime = $updateTime;

        return $this;
    }

    /**
     * Get the value of order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the value of order
     */
    public function setOrder($order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set the value of queue
     */
    public function setQueue($queue): self
    {
        $this->queue = $queue;

        return $this;
    }
}
