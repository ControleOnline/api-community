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
 *     normalizationContext  ={"groups"={"hardware_queue_read"}},
 *     denormalizationContext={"groups"={"hardware_queue_write"}},
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
 * @ORM\Table(name="hardware_queue", uniqueConstraints={@ORM\UniqueConstraint(name="hardware_id", columns={"hardware_id", "queue_id"})}, indexes={@ORM\Index(name="queue_id", columns={"queue_id"}), @ORM\Index(name="IDX_7EAD648851A2DF33", columns={"hardware_id"})})
 * @ORM\Entity
 */

class HardwareQueue
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"order_read","hardware_queue_read", "hardware_queue_write"})    
     */
    private $id;

    /**
     * @var \Hardware
     *
     * @ORM\ManyToOne(targetEntity="Hardware")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="hardware_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","hardware_queue_read", "hardware_queue_write"})    
     */
    private $hardware;

    /**
     * @var \Queue
     *
     * @ORM\ManyToOne(targetEntity="Queue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="queue_id", referencedColumnName="id")
     * })     
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
    public function setId( $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of hardware
     */
    public function getHardware()
    {
        return $this->hardware;
    }

    /**
     * Set the value of hardware
     */
    public function setHardware($hardware): self
    {
        $this->hardware = $hardware;

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
