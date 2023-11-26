<?php

namespace App\Entity;


use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\GetActionByPeopleAction;
use App\Controller\GetMenuByPeopleAction;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Filter\SalesOrderEntityFilter;
use App\Entity\Order;
use stdClass;


/**
 * Routes
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table(name="routes", uniqueConstraints={@ORM\UniqueConstraint(name="route", columns={"route"})}, indexes={@ORM\Index(name="module_id", columns={"module_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\RouteRepository")
 * @ORM\Entity
 */

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['route_write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['route_read']],
    denormalizationContext: ['groups' => ['route_write']]
)]

class Routes
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"menu_read","route_read"})   
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="route", type="string", length=50, nullable=false)
     * @Groups({"menu_read","route_read"})   
     */
    private $route;

    /**
     * @var \Module
     *
     * @ORM\ManyToOne(targetEntity="Module")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     * @Groups({"menu_read","route_read"})  
     */
    private $module;


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
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set the value of route
     */
    public function setRoute($route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get the value of module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the value of module
     */
    public function setModule($module): self
    {
        $this->module = $module;

        return $this;
    }
}
