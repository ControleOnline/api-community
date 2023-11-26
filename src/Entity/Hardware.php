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
 *     normalizationContext  ={"groups"={"hardware_read"}},
 *     denormalizationContext={"groups"={"hardware_write"}},
 *     attributes            ={"access_control"="is_granted('ROLE_CLIENT')"},
 *     collectionOperations  ={
 *          "get"              ={
 *            "access_control"="is_granted('ROLE_CLIENT')", 
 *          },
 *         "post"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')",  
 *         },
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
 * @ApiFilter(
 *   SearchFilter::class, properties={ 
 *     "hardwareQueue.queue.orderQueue.status.realStatus": "exact",
 *     "hardwareQueue.queue.orderQueue.status.status": "exact",
 *   }
 * ) 
 * @ORM\Table(name="hardware", indexes={@ORM\Index(name="company_id", columns={"company_id"})})
 * @ORM\Entity
 */

class Hardware
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"order_read","hardware_read", "hardware_write"})   
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="hardware", type="string", length=50, nullable=false)
     * @Groups({"order_read","hardware_read", "hardware_write"})   
     */
    private $hardware;

    /**
     * @var string
     *
     * @ORM\Column(name="hardware_type", type="string", length=0, nullable=false, options={"default"="'display'"})
     * @Groups({"order_read","hardware_read", "hardware_write"})   
     */
    private $hardwareType = '\'display\'';

    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     * @Groups({"order_read","hardware_read", "hardware_write"})   
     */
    private $company;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\HardwareQueue", mappedBy="hardware")     
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
     * Get the value of hardwareType
     */
    public function getHardwareType()
    {
        return $this->hardwareType;
    }

    /**
     * Set the value of hardwareType
     */
    public function setHardwareType($hardwareType): self
    {
        $this->hardwareType = $hardwareType;

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
