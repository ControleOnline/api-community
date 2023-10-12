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
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="status", uniqueConstraints={@ORM\UniqueConstraint (name="status", columns={"status"})}, indexes={@ORM\Index (name="IDX_real_status", columns={"real_status"})})
 * @ORM\Entity (repositoryClass="App\Repository\StatusRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')', normalizationContext: ['groups' => ['status_read']], denormalizationContext: ['groups' => ['status_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact', 'visibility' => 'exact', 'realStatus' => 'exact'])]
class Status
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"hardware_read","logistic_read"})
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string",  nullable=false)
     * @Groups({"hardware_read","order_read", "invoice_read", "status_read", "order_detail_status_read", "logistic_read","queue_read",
     * "queue_people_queue_read"})
     */
    private $status;
    /**
     * @var string
     *
     * @ORM\Column(name="real_status", type="string",  nullable=false)
     * @Groups({"hardware_read","order_read", "invoice_read", "status_read", "order_detail_status_read", "logistic_read","queue_read"})
     */
    private $realStatus;
    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string",  nullable=false)
     */
    private $visibility;
    /**
     * @var boolean
     *
     * @ORM\Column(name="notify", type="boolean",  nullable=false)
     */
    private $notify;
    /**
     * @var boolean
     *
     * @ORM\Column(name="system", type="boolean",  nullable=false)
     */
    private $system;
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string",  nullable=false)
     * @Groups({"hardware_read","order_read", "invoice_read", "status_read", "order_detail_status_read","queue_read"})
     */
    private $color;
    /**
     * @var string
     *
     * @ORM\Column(name="context", type="string",  nullable=false)
     * @Groups({"hardware_read","order_read", "invoice_read", "status_read", "order_detail_status_read","queue_read"})
     */
    private $context;
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
     * @param string $status
     * @return Status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Set realStatus
     *
     * @param string $real_status
     * @return Status
     */
    public function setRealStatus($real_status)
    {
        $this->realStatus = $real_status;
        return $this;
    }
    /**
     * Get realStatus
     *
     * @return string
     */
    public function getRealStatus()
    {
        return $this->realStatus;
    }
    /**
     * Set visibility
     *
     * @param string $visibility
     * @return Status
     */
    public function setVisibility(string $visibility) : self
    {
        $this->visibility = $visibility;
        return $this;
    }
    /**
     * Get visibility
     *
     * @return string
     */
    public function getVisibility() : string
    {
        return $this->visibility;
    }
    /**
     * Set notify
     *
     * @param boolean $notify
     * @return Status
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
        return $this;
    }
    /**
     * Get notify
     *
     * @return boolean
     */
    public function getNotify()
    {
        return $this->notify;
    }
    /**
     * Set system
     *
     * @param boolean $system
     * @return Status
     */
    public function setSystem($system)
    {
        $this->system = $system;
        return $this;
    }
    /**
     * Get system
     *
     * @return boolean
     */
    public function getSystem()
    {
        return $this->system;
    }
    /**
     * Set color
     *
     * @param string $color
     * @return Status
     */
    public function setColor(string $color) : self
    {
        $this->color = $color;
        return $this;
    }
    /**
     * Get color
     *
     * @return string
     */
    public function getColor() : string
    {
        return $this->color;
    }
    /**
     * Set context
     *
     * @param string $context
     * @return Status
     */
    public function setContext(string $context) : self
    {
        $this->context = $context;
        return $this;
    }
    /**
     * Get context
     *
     * @return string
     */
    public function getContext() : string
    {
        return $this->context;
    }
}
