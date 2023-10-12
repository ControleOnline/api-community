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
 * Menu
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="menu", uniqueConstraints={@ORM\UniqueConstraint (name="route", columns={"route"})}, indexes={ @ORM\Index(name="category_id", columns={"category_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\MenuRepository")
 * @ORM\Entity
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['menu_write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            uriTemplate: '/menus-people',
            controller: \App\Controller\GetMenuByPeopleAction::class
        ),
        new GetCollection(
            uriTemplate: '/actions/people',
            controller: \App\Controller\GetActionByPeopleAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['menu_read']],
    denormalizationContext: ['groups' => ['menu_write']]
)]
class Menu
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"menu_read"})  
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="menu", type="string", length=50, nullable=false)
     * @Groups({"menu_read","menu_write"}) 
     */
    private $menu;
    /**
     * @var \Route
     *
     * @ORM\ManyToOne(targetEntity="Routes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="route_id", referencedColumnName="id")
     * })
     * @Groups({"menu_read","menu_write"})  
     */
    private $route;
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=50, nullable=false, options={"default"="'$primary'"})
     * @Groups({"menu_read"})  
     */
    private $color = '$primary';
    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=50, nullable=false)
     * @Groups({"menu_read","menu_write"})  
     */
    private $icon;

    /**
     * @var \Category
     *
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     * @Groups({"menu_read","menu_write"}) 
     */
    private $category;
    /**
     * Get the value of id
     */
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * Get the value of menu
     */
    public function getMenu(): string
    {
        return $this->menu;
    }
    /**
     * Set the value of menu
     */
    public function setMenu($menu): self
    {
        $this->menu = $menu;
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
     * Get the value of color
     */
    public function getColor(): string
    {
        return $this->color;
    }
    /**
     * Set the value of color
     */
    public function setColor($color): self
    {
        $this->color = $color;
        return $this;
    }
    /**
     * Get the value of icon
     */
    public function getIcon(): string
    {
        return $this->icon;
    }
    /**
     * Set the value of icon
     */
    public function setIcon($icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Get the value of category
     */
    public function getCategory()
    {
        return $this->category;
    }
    /**
     * Set the value of category
     */
    public function setCategory($category): self
    {
        $this->category = $category;
        return $this;
    }
}
