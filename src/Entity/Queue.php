<?php

namespace App\Entity;



use Doctrine\ORM\Mapping as ORM;



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
 *     normalizationContext  ={"groups"={"queue_read"}},
 *     denormalizationContext={"groups"={"queue_write"}},
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
 * @ORM\Table(name="queue", uniqueConstraints={@ORM\UniqueConstraint(name="queue", columns={"queue", "company_id"})}, indexes={@ORM\Index(name="company_id", columns={"company_id"})})
 * @ORM\Entity
 */


class Queue
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"order_read","queue_read", "queue_write"})   
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="queue", type="string", length=50, nullable=false)
     * @Groups({"order_read","queue_read", "queue_write"})   
     */
    private $queue;

    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","queue_read", "queue_write"})   
     */
    private $company;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\OrderQueue", mappedBy="queue")
     */
    private $orderQueue;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\HardwareQueue", mappedBy="queue")     
     */
    private $hardwareQueue;


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

    /**
     * Get the value of company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the value of company
     */
    public function setCompany($company): self
    {
        $this->company = $company;

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


    /**
     * Add HardwareQueue
     *
     * @param \App\Entity\HardwareQueue $invoice_tax
     * @return Order
     */
    public function addAHardwareQueue(\App\Entity\HardwareQueue $hardwareQueue)
    {
        $this->hardwareQueue[] = $hardwareQueue;

        return $this;
    }

    /**
     * Remove HardwareQueue
     *
     * @param \App\Entity\HardwareQueue $invoice_tax
     */
    public function removeHardwareQueue(\App\Entity\HardwareQueue $hardwareQueue)
    {
        $this->hardwareQueue->removeElement($hardwareQueue);
    }

    /**
     * Get HardwareQueue
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHardwareQueue()
    {
        return $this->hardwareQueue;
    }
}
