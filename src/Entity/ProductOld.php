<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductOld
 *
 * @ORM\Table(name="product_old", indexes={@ORM\Index(name="product_parent", columns={"product_parent"}), @ORM\Index(name="product_provider", columns={"product_provider"})})
 * @ORM\Entity
 */
class ProductOld
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
     * @ORM\Column(name="product", type="string", length=255, nullable=false)
     */
    private $product;

    /**
     * @var int
     *
     * @ORM\Column(name="product_quantity", type="integer", nullable=false, options={"default"="1"})
     */
    private $productQuantity = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="product_type", type="string", length=0, nullable=false)
     */
    private $productType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="product_subtype", type="string", length=0, nullable=true, options={"default"="NULL"})
     */
    private $productSubtype = 'NULL';

    /**
     * @var string|null
     *
     * @ORM\Column(name="product_period", type="string", length=0, nullable=true, options={"default"="NULL"})
     */
    private $productPeriod = 'NULL';

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=10, scale=0, nullable=false)
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="billing_unit", type="string", length=0, nullable=false, options={"default"="'Single'"})
     */
    private $billingUnit = '\'Single\'';

    /**
     * @var string|null
     *
     * @ORM\Column(name="other_informations", type="text", length=0, nullable=true, options={"default"="NULL"})
     */
    private $otherInformations = 'NULL';

    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_provider", referencedColumnName="id")
     * })
     */
    private $productProvider;

    /**
     * @var \ProductOld
     *
     * @ORM\ManyToOne(targetEntity="ProductOld")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_parent", referencedColumnName="id")
     * })
     */
    private $productParent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?string
    {
        return $this->product;
    }

    public function setProduct(string $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProductQuantity(): ?int
    {
        return $this->productQuantity;
    }

    public function setProductQuantity(int $productQuantity): self
    {
        $this->productQuantity = $productQuantity;

        return $this;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(string $productType): self
    {
        $this->productType = $productType;

        return $this;
    }

    public function getProductSubtype(): ?string
    {
        return $this->productSubtype;
    }

    public function setProductSubtype(?string $productSubtype): self
    {
        $this->productSubtype = $productSubtype;

        return $this;
    }

    public function getProductPeriod(): ?string
    {
        return $this->productPeriod;
    }

    public function setProductPeriod(?string $productPeriod): self
    {
        $this->productPeriod = $productPeriod;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getBillingUnit(): ?string
    {
        return $this->billingUnit;
    }

    public function setBillingUnit(string $billingUnit): self
    {
        $this->billingUnit = $billingUnit;

        return $this;
    }

    public function getOtherInformations(): ?string
    {
        return $this->otherInformations;
    }

    public function setOtherInformations(?string $otherInformations): self
    {
        $this->otherInformations = $otherInformations;

        return $this;
    }

    public function getProductProvider(): ?People
    {
        return $this->productProvider;
    }

    public function setProductProvider(?People $productProvider): self
    {
        $this->productProvider = $productProvider;

        return $this;
    }

    public function getProductParent(): ?self
    {
        return $this->productParent;
    }

    public function setProductParent(?self $productParent): self
    {
        $this->productParent = $productParent;

        return $this;
    }


}
