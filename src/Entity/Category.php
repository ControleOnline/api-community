<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="category")
 * @ORM\Entity (repositoryClass="App\Repository\CategoryRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'), new Put(security: 'is_granted(\'ROLE_CLIENT\')', denormalizationContext: ['groups' => ['category_edit']]), new Delete(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['category_read']], denormalizationContext: ['groups' => ['category_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact', 'parent' => 'exact', 'company' => 'exact'])]
#[ApiFilter(filterClass: ExistsFilter::class, properties: ['parent'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['name' => 'ASC'])]
class Category
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"category_read", "company_expense_read","menu_read"})
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Groups({"menu_read","category_read", "category_write", "company_expense_read", "category_edit","queue_read"})
     * @Assert\NotBlank
     * @Assert\Type(type={"string"})
     */
    private $name;
    /**
     * @var string
     *
     * @ORM\Column(name="context", type="string", length=100, nullable=false)
     * @Groups({"category_read", "category_write","menu_read","queue_read"})
     * @Assert\NotBlank
     * @Assert\Type(type={"string"})
     */
    private $context;
    /**
     * @var \App\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * })
     * @Groups({"category_read", "category_write", "category_edit","menu_read","queue_read"})
     */
    private $parent;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     * @Groups({"category_read", "category_write","menu_read","queue_read"})
     * @Assert\NotBlank
     */
    private $company;
    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=50, nullable=false)
     * @Groups({"category_read", "category_write", "company_expense_read", "category_edit","menu_read","queue_read"})   
     * @Assert\Type(type={"string"})
     */
    private $icon;
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=50, nullable=false)
     * @Groups({"category_read", "category_write", "company_expense_read", "category_edit","menu_read","queue_read"})   
     * @Assert\Type(type={"string"})
     */
    private $color;
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }
    public function getContext()
    {
        return $this->context;
    }
    public function setParent(Category $category = null)
    {
        $this->parent = $category;
        return $this;
    }
    public function getParent() : ?Category
    {
        return $this->parent;
    }
    public function setCompany(People $company)
    {
        $this->company = $company;
        return $this;
    }
    public function getCompany()
    {
        return $this->company;
    }
    /**
     * Get the value of icon
     */
    public function getIcon()
    {
        return $this->icon;
    }
    /**
     * Set the value of icon
     */
    public function setIcon($icon) : self
    {
        $this->icon = $icon;
        return $this;
    }
    /**
     * Get the value of color
     */
    public function getColor()
    {
        return $this->color;
    }
    /**
     * Set the value of color
     */
    public function setColor($color) : self
    {
        $this->color = $color;
        return $this;
    }
}
