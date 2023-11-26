<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Resource\ResourceEntity;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="company_expense")
 * @ORM\Entity (repositoryClass="App\Repository\CompanyExpenseRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'read\', object)'), new Put(uriTemplate: '/company_expenses/{id}', requirements: ['id' => '^\\d+$'], security: 'is_granted(\'edit\', object)', controller: \App\Controller\UpdateCompanyExpenseAction::class, denormalizationContext: ['groups' => ['company_expense_edit']]), new Delete(security: 'is_granted(\'delete\', object)'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(securityPostDenormalize: 'is_granted(\'create\', object)')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['company_expense_read']], denormalizationContext: ['groups' => ['company_expense_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['company' => 'exact'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['dueDate' => 'ASC'])]
class CompanyExpense extends ResourceEntity
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"company_expense_read"})
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"company_expense_read", "company_expense_write"})
     * @Assert\NotBlank
     */
    private $company;
    /**
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"company_expense_read", "company_expense_write"})
     * @Assert\NotBlank
     */
    private $category;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="provider_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"company_expense_read", "company_expense_write", "company_expense_edit"})
     * @Assert\NotBlank
     */
    private $provider;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"company_expense_read"})
     */
    private $order;
    /**
     * @ORM\Column(name="parcels", type="integer", nullable=true)
     * @Groups({"company_expense_read", "company_expense_write", "company_expense_edit"})
     * @Assert\Type(type={"integer"})
     * @Assert\Positive
     */
    private $parcels = null;
    /**
     * @ORM\Column(name="amount", type="float", nullable=false)
     * @Groups({"company_expense_read", "company_expense_write", "company_expense_edit"})
     * @Assert\NotBlank
     * @Assert\Type(type={"float", "integer"})
     * @Assert\Positive
     */
    private $amount;
    /**
     * @ORM\Column(name="duedate", type="date", nullable=false)
     * @Groups({"company_expense_read", "company_expense_write", "company_expense_edit"})
     * @Assert\NotBlank
     * @Assert\DateTime
     */
    private $dueDate;
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     * @Groups({"company_expense_read", "company_expense_write", "company_expense_edit"})
     * @Assert\Type(type={"string"})
     */
    private $description = null;
    /**
     * @ORM\Column(name="payment_day", type="integer", nullable=false)
     * @Groups({"company_expense_read", "company_expense_write", "company_expense_edit"})
     * @Assert\NotBlank
     * @Assert\Type(type={"integer"})
     * @Assert\Positive
     */
    private $paymentDay;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @Groups({"company_expense_read"})
     */
    private $active = 1;
    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }
    public function getCompany()
    {
        return $this->company;
    }
    public function setCompany(People $company)
    {
        $this->company = $company;
        return $this;
    }
    public function getCategory()
    {
        return $this->category;
    }
    public function setCategory(Category $category)
    {
        $this->category = $category;
        return $this;
    }
    public function getProvider()
    {
        return $this->provider;
    }
    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
        return $this;
    }
    public function getOrder()
    {
        return $this->order;
    }
    public function setOrder(SalesOrder $order)
    {
        $this->order = $order;
        return $this;
    }
    public function getParcels()
    {
        return $this->parcels;
    }
    public function setParcels(int $parcels = null)
    {
        $this->parcels = $parcels;
        return $this;
    }
    public function getAmount() : float
    {
        return $this->amount;
    }
    public function setAmount(float $amount) : self
    {
        $this->amount = $amount;
        return $this;
    }
    public function getDuedate() : ?\DateTime
    {
        return $this->dueDate;
    }
    public function setDuedate(\DateTime $dueDate = null) : self
    {
        $this->dueDate = $dueDate;
        return $this;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function setDescription(string $description = null)
    {
        $this->description = $description;
        return $this;
    }
    public function getPaymentDay()
    {
        return $this->paymentDay;
    }
    public function setPaymentDay(int $paymentDay)
    {
        $this->paymentDay = $paymentDay;
        return $this;
    }
    public function isActive()
    {
        return $this->active === 1;
    }
    public function getActive()
    {
        return $this->active;
    }
    public function setActive($active)
    {
        $this->active = $active ?: 0;
        return $this;
    }
    public function isRecurrent() : bool
    {
        return empty($this->getParcels());
    }
    public function isParceled() : bool
    {
        return $this->isRecurrent() == false && $this->getParcels() > 1;
    }
}
