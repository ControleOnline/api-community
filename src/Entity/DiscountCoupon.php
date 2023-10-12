<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Entity\People;
use Doctrine\ORM\Mapping as ORM;
use App\Filter\DiscountCouponEntityFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use stdClass;
/**
 * DiscountCoupon
 * SalesOrder
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="discount_coupon", uniqueConstraints={@ORM\UniqueConstraint (name="code", columns={"code"})}, indexes={@ORM\Index (name="creator_id", columns={"creator_id"}), @ORM\Index(name="client_id", columns={"client_id"})})
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(uriTemplate: '/coupon/{id}', security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and previous_object.canAccess(user))'), new GetCollection(extraProperties: ['filters' => [DiscountCouponEntityFilter::class]], security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/coupons'), new Post(uriTemplate: '/coupons', controller: \App\Controller\CreateCouponAction::class)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], filters: [\App\Filter\DiscountCouponEntityFilter::class], normalizationContext: ['groups' => ['coupon_read']], denormalizationContext: ['groups' => ['coupon_write']])]
class DiscountCoupon
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10, nullable=false)
     * @Groups({"coupon_read","order_read"})
     */
    private $code;
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=0, nullable=false, options={"default"="'percentage'"})
     * @Groups({"coupon_read","order_read"})
     */
    private $type = 'percentage';
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="discount_date", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     * @Groups({"coupon_read","order_read"}) 
     */
    private $discountDate = 'current_timestamp()';
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="discount_start_date", type="date", nullable=false, options={"default"="current_timestamp()"})
     *  @Groups({"coupon_read","order_read"}) 
     */
    private $discountStartDate = 'current_timestamp()';
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="discount_end_date", type="date", nullable=false)
     *  @Groups({"coupon_read","order_read"}) 
     */
    private $discountEndDate;
    /**
     * @var string
     *
     * @ORM\Column(name="config", type="text", length=0, nullable=false)
     *  @Groups({"coupon_read","order_read"}) 
     */
    private $config;
    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float", precision=10, scale=0, nullable=false)
     *  @Groups({"coupon_read","order_read"}) 
     */
    private $value;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     * })
     *  @Groups({"coupon_read","order_read"}) 
     */
    private $creator;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     *  @Groups({"coupon_read","order_read"}) 
     */
    private $company;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * })
     *  @Groups({"coupon_read","order_read"}) 
     */
    private $client;
    /**
     * @var \App\Entity\SalesOrder
     *
     * @ORM\OneToOne(targetEntity="App\Entity\SalesOrder", mappedBy="discountCoupon", cascade={"persist", "remove"})
     *  @Groups({"coupon_read"}) 
     */
    private $order;
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getCode() : ?string
    {
        return $this->code;
    }
    public function setCode(string $code) : self
    {
        $this->code = $code;
        return $this;
    }
    public function getType() : ?string
    {
        return $this->type;
    }
    public function setType(string $type) : self
    {
        $this->type = $type;
        return $this;
    }
    public function getDiscountDate() : ?\DateTimeInterface
    {
        return $this->discountDate;
    }
    public function setDiscountDate(\DateTimeInterface $discountDate) : self
    {
        $this->discountDate = $discountDate;
        return $this;
    }
    public function getDiscountStartDate() : ?\DateTimeInterface
    {
        return $this->discountStartDate;
    }
    public function setDiscountStartDate(?\DateTimeInterface $discountStartDate = null) : self
    {
        $this->discountStartDate = $discountStartDate;
        return $this;
    }
    public function getDiscountEndDate() : ?\DateTimeInterface
    {
        return $this->discountEndDate;
    }
    public function setDiscountEndDate(?\DateTimeInterface $discountEndDate = null) : self
    {
        $this->discountEndDate = $discountEndDate;
        return $this;
    }
    public function getConfig() : ?string
    {
        return $this->config;
    }
    public function setConfig(string $config) : self
    {
        $this->config = $config;
        return $this;
    }
    public function getValue() : ?float
    {
        return $this->value;
    }
    public function setValue(float $value) : self
    {
        $this->value = $value;
        return $this;
    }
    public function getCreator() : ?People
    {
        return $this->creator;
    }
    public function setCreator(People $creator) : self
    {
        $this->creator = $creator;
        return $this;
    }
    public function setCompany(People $company) : self
    {
        $this->company = $company;
        return $this;
    }
    public function getCompany() : ?People
    {
        return $this->company;
    }
    public function getClient() : ?People
    {
        return $this->client;
    }
    public function setClient(?People $client) : self
    {
        $this->client = $client;
        return $this;
    }
    /**
     * Set order
     *
     * @param \App\Entity\SalesOrder $order
     * @return DiscountCoupon
     */
    public function setOrder(\App\Entity\SalesOrder $order = null)
    {
        $this->order = $order;
        return $this;
    }
    /**
     * Get order
     *
     * @return \App\Entity\SalesOrder
     */
    public function getOrder()
    {
        return $this->order;
    }
}
