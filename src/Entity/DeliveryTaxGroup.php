<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\GetDeliveryGroupTaxNamesAction;
use App\Controller\GetTablesAction;
use App\Controller\UpdateDeliveryGroupTaxPriceAction;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DeliveryTaxGroup
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="delivery_tax_group")
 * @ORM\Entity (repositoryClass="App\Repository\DeliveryTaxGroupRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'read\', object)'),
        new Put(
            security: 'is_granted(\'edit\', object)',
            denormalizationContext: ['groups' => ['delivery_group_edit']]
        ),
        new Put(
            uriTemplate: '/delivery_tax_groups/{id}/copy-taxes',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: 'App\\Controller\\CopyDeliveryGroupTaxesAction'
        ), new Put(
            uriTemplate: '/delivery_tax_groups/{id}/increase-taxes',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: UpdateDeliveryGroupTaxPriceAction::class
        ),
        new Get(
            uriTemplate: '/delivery_tax_groups/{id}/tax-names',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: GetDeliveryGroupTaxNamesAction::class
        ),
        new Delete(security: 'is_granted(\'delete\', object)'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'create\', object)'),
        new GetCollection(
            uriTemplate: '/delivery_tax_groups_grouped',
            controller: GetTablesAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['delivery_group_read']],
    denormalizationContext: ['groups' => ['delivery_group_write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['carrier' => 'exact'])]
class DeliveryTaxGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"delivery_group_read"})
     */
    private $id;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="carrier_id", referencedColumnName="id")
     * })
     * @Groups({"delivery_group_read", "delivery_group_write"})
     * @Assert\NotBlank(groups={"delivery_group_write"})
     */
    private $carrier;
    /**
     * @var string
     *
     * @ORM\Column(name="group_name", type="string", length=255)
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\NotBlank(groups={"delivery_group_write"})
     */
    private $groupName;
    /**
     * @var boolean
     *
     * @ORM\Column(name="marketplace", type="boolean")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\Type("bool")
     */
    private $marketplace;
    /**
     * @var boolean
     *
     * @ORM\Column(name="remote", type="boolean")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\Type("bool")
     */
    private $remote;
    /**
     * @var boolean
     *
     * @ORM\Column(name="website", type="boolean")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\Type("bool")
     */
    private $website;
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=50, nullable=true)
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     */
    private $code;
    /**
     * @var string
     *
     * @ORM\Column(name="max_height", type="float")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\NotBlank(groups={"delivery_group_write"})
     * @Assert\Type(type={"float", "integer"}, groups={"delivery_group_write"})
     * @Assert\Positive(groups={"delivery_group_write"})
     */
    private $maxHeight;
    /**
     * @var string
     *
     * @ORM\Column(name="max_width", type="float")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\NotBlank(groups={"delivery_group_write"})
     * @Assert\Type(type={"float", "integer"}, groups={"delivery_group_write"})
     * @Assert\Positive(groups={"delivery_group_write"})
     */
    private $maxWidth;
    /**
     * @var string
     *
     * @ORM\Column(name="max_depth", type="float")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\NotBlank(groups={"delivery_group_write"})
     * @Assert\Type(type={"float", "integer"}, groups={"delivery_group_write"})
     * @Assert\Positive(groups={"delivery_group_write"})
     */
    private $maxDepth;
    /**
     * @var string
     *
     * @ORM\Column(name="max_cubage", type="float")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\NotBlank(groups={"delivery_group_write"})
     * @Assert\Type(type={"float", "integer"}, groups={"delivery_group_write"})
     * @Assert\Positive(groups={"delivery_group_write"})
     */
    private $maxCubage;
    /**
     * @var string
     *
     * @ORM\Column(name="min_cubage", type="float")
     * @Groups({"delivery_group_read", "delivery_group_write", "delivery_group_edit"})
     * @Assert\NotBlank(groups={"delivery_group_write"})
     * @Assert\Type(type={"float", "integer"}, groups={"delivery_group_write"})
     * @Assert\Positive(groups={"delivery_group_write"})
     */
    private $minCubage;
    /**
     * @var \Collection
     * @ORM\OneToMany (targetEntity="App\Entity\DeliveryTax", mappedBy="groupTax")
     * @ORM\OrderBy ({"company" = "ASC"})
     */
    private $deliveryTaxes;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->marketplace = true;
        $this->website = true;
        $this->remote = false;
    }
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
     * Get website
     *
     * @return boolean
     */
    public function getWebsite()
    {
        return $this->website;
    }
    /**
     * Set website
     *
     * @param boolean $website
     * @return People
     */
    public function setWebsite($website)
    {
        $this->website = $website;
        return $this;
    }
    /**
     * Get remote
     *
     * @return boolean
     */
    public function getRemote()
    {
        return $this->remote;
    }
    /**
     * Set remote
     *
     * @param boolean $remote
     * @return People
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;
        return $this;
    }
    /**
     * Get marketplace
     *
     * @return boolean
     */
    public function getMarketPlace()
    {
        return $this->marketplace;
    }
    /**
     * Set marketplace
     *
     * @param boolean $marketplace
     * @return People
     */
    public function setMarketPlace($marketplace)
    {
        $this->marketplace = $marketplace;
        return $this;
    }
    /**
     * Set group_name
     *
     * @param string $group_name
     * @return DeliveryTaxGroup
     */
    public function setGroupName($group_name)
    {
        $this->groupName = $group_name;
        return $this;
    }
    /**
     * Get group_name
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }
    /**
     * Set code
     *
     * @param string $code
     * @return DeliveryTaxGroup
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }
    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
    /**
     * Set people
     *
     * @param \App\Entity\People $people
     * @return people
     */
    public function setCarrier(\App\Entity\People $people = null)
    {
        $this->carrier = $people;
        return $this;
    }
    /**
     * Get people
     *
     * @return \App\Entity\People
     */
    public function getCarrier()
    {
        return $this->carrier;
    }
    /**
     * Set max_height
     *
     * @param float $max_height
     * @return DeliveryTaxGroup
     */
    public function setMaxHeight($max_height)
    {
        $this->maxHeight = $max_height;
        return $this;
    }
    /**
     * Get max_height
     *
     * @return float
     */
    public function getMaxHeight()
    {
        return $this->maxHeight;
    }
    /**
     * Set max_width
     *
     * @param float $max_width
     * @return DeliveryTaxGroup
     */
    public function setMaxWidth($max_width)
    {
        $this->maxWidth = $max_width;
        return $this;
    }
    /**
     * Get max_width
     *
     * @return float
     */
    public function getMaxWidth()
    {
        return $this->maxWidth;
    }
    /**
     * Set max_depth
     *
     * @param float $max_depth
     * @return DeliveryTaxGroup
     */
    public function setMaxDepth($max_depth)
    {
        $this->maxDepth = $max_depth;
        return $this;
    }
    /**
     * Get max_depth
     *
     * @return float
     */
    public function getMaxDepth()
    {
        return $this->maxDepth;
    }
    /**
     * Set max_cubage
     *
     * @param float $max_cubage
     * @return DeliveryTaxGroup
     */
    public function setMaxCubage($max_cubage)
    {
        $this->maxCubage = $max_cubage;
        return $this;
    }
    /**
     * Get max_cubage
     *
     * @return float
     */
    public function getMaxCubage()
    {
        return $this->maxCubage;
    }
    /**
     * Set min_cubage
     *
     * @param float $min_cubage
     * @return DeliveryTaxGroup
     */
    public function setMinCubage($min_cubage)
    {
        $this->minCubage = $min_cubage;
        return $this;
    }
    /**
     * Get min_cubage
     *
     * @return float
     */
    public function getMinCubage()
    {
        return $this->minCubage;
    }
    /**
     * @return Collection|DeliveryTax[]
     */
    public function getDeliveryTaxes()
    {
        return $this->deliveryTaxes;
    }
    public function addDeliveryTaxes(DeliveryTax $deliveryTax): self
    {
        if (!$this->deliveryTaxes->contains($deliveryTax)) {
            $this->deliveryTaxes[] = $deliveryTax;
            $deliveryTax->setGroupTax($this);
        }
        return $this;
    }
    public function removeDeliveryTaxes(DeliveryTax $deliveryTax): self
    {
        if ($this->deliveryTaxes->removeElement($deliveryTax)) {
            if ($deliveryTax->getGroupTax() === $this) {
                $deliveryTax->setGroupTax($deliveryTax);
            }
        }
        return $this;
    }
}
