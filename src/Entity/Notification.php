<?php


namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Filter\SalesOrderEntityFilter;
use App\Entity\Order;
use stdClass;
/**
 * Module
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="notification", indexes={@ORM\Index (name="people_id", columns={"people_id"})})
 * @ORM\Entity
 * @ORM\Entity (repositoryClass="App\Repository\NotificationRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and previous_object.canAccess(user))'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['notifications_read']], denormalizationContext: ['groups' => ['notifications_write']])]
class Notification
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"notifications_read"}) 
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="notification", type="text", length=65535, nullable=false)
     * @Groups({"notifications_read"})  
     */
    private $notification;
    /**
     * @var string
     *
     * @ORM\Column(name="route", type="string", length=50, nullable=false)
     * @Groups({"notifications_read"})  
     */
    private $route;
    /**
     * @var int
     *
     * @ORM\Column(name="route_id", type="integer", nullable=false)
     * @Groups({"notifications_read"})  
     */
    private $routeId;
    /**
     * @var bool
     *
     * @ORM\Column(name="read", type="boolean", nullable=false)
     * @Groups({"notifications_read"})  
     */
    private $read;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     * @Groups({"notifications_read"})  
     */
    private $people;
    /**
     * Get the value of color
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set the value of name
     */
    public function setNotification($notification) : self
    {
        $this->notification = $notification;
        return $this;
    }
    /**
     * Get the value of color
     */
    public function getNotification()
    {
        return $this->notification;
    }
    /**
     * Set the value of name
     */
    public function setRoute($route) : self
    {
        $this->route = $route;
        return $this;
    }
    /**
     * Get the value of color
     */
    public function getRoute()
    {
        return $this->route;
    }
    /**
     * Set the value of name
     */
    public function setRouteId($routeId) : self
    {
        $this->routeId = $routeId;
        return $this;
    }
    /**
     * Get the value of color
     */
    public function getRouteId()
    {
        return $this->routeId;
    }
    /**
     * Set the value of name
     */
    public function setRead($read) : self
    {
        $this->read = $read;
        return $this;
    }
    /**
     * Get the value of color
     */
    public function getRead()
    {
        return $this->read;
    }
    /**
     * Set the value of name
     */
    public function setPeople($people) : self
    {
        $this->people = $people;
        return $this;
    }
    /**
     * Get the value of color
     */
    public function getPeople()
    {
        return $this->people;
    }
}
