<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
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
 * @ORM\Table (name="module", uniqueConstraints={@ORM\UniqueConstraint (name="UX_MODULE_NAME", columns={"name"})})
 * @ORM\Entity
 * @ORM\Entity (repositoryClass="App\Repository\ModuleRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['module_write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['module_read']],
    denormalizationContext: ['groups' => ['module_write']]
)]
class Module
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"menu_read","module_read"}) 
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Groups({"menu_read","module_read","module_write"})  
     */
    private $name;
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=50, nullable=false, options={"default"="'$primary'"})
     * @Groups({"menu_read","module_read","module_write"})   
     */
    private $color = '$primary';
    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=50, nullable=false)
     * @Groups({"menu_read","module_read","module_write"})   
     */
    private $icon;
    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true, options={"default"="NULL"})
     * @Groups({"menu_read","module_read","module_write"})   
     */
    private $description = NULL;
    /**
     * Get the value of id
     */
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * Get the value of name
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Set the value of name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
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
    public function setColor(string $color): self
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
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }
    /**
     * Get the value of description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
    /**
     * Set the value of description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
