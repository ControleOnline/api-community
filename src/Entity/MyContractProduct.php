<?php

namespace App\Entity;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="contract_product")
 * @ORM\Entity (repositoryClass="App\Repository\MyContractProductRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new Delete(security: 'is_granted(\'delete\', object)'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(securityPostDenormalize: 'is_granted(\'create\', object)')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['mycontractproduct_read']], denormalizationContext: ['groups' => ['mycontractproduct_write']])]
#[ApiResource(uriTemplate: '/my_contracts/{id}/contract_products.{_format}', uriVariables: ['id' => new Link(fromClass: \App\Entity\MyContract::class, identifiers: ['id'], toProperty: 'contract')], status: 200, normalizationContext: ['groups' => ['mycontractproduct_read']], operations: [new GetCollection()])]
class MyContractProduct
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MyContract", inversedBy="contractProduct")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     * @Groups({"mycontractproduct_write"})
     */
    private $contract;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ProductOld")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     * @Groups({"mycontractproduct_read", "mycontractproduct_write"})
     */
    private $product;
    /**
     * @ORM\Column(name="quantity", type="float", nullable=false)
     * @Assert\Positive
     * @Groups({"mycontractproduct_read", "mycontractproduct_write"})
     */
    private $quantity = 0;
    /**
     * @ORM\Column(name="product_price", type="float", nullable=false)
     * @Assert\PositiveOrZero
     * @Groups({"mycontractproduct_read", "mycontractproduct_write"})
     */
    private $price = 0;
    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * @return MyContract
     */
    public function getContract() : MyContract
    {
        return $this->contract;
    }
    /**
     * @param MyContract $contract
     * @return MyContractProduct
     */
    public function setContract(MyContract $contract) : MyContractProduct
    {
        $this->contract = $contract;
        return $this;
    }
    /**
     * @return Product
     */
    public function getProduct() : ProductOld
    {
        return $this->product;
    }
    /**
     * @param ProductOld $product
     * @return MyContractProduct
     */
    public function setProduct(ProductOld $product) : MyContractProduct
    {
        $this->product = $product;
        return $this;
    }
    /**
     * @return float
     */
    public function getQuantity() : float
    {
        if (is_null($this->quantity)) {
            return 0;
        }
        return $this->quantity;
    }
    /**
     * @param float $quantity
     * @return MyContractProduct
     */
    public function setQuantity(float $quantity) : MyContractProduct
    {
        $this->quantity = $quantity;
        return $this;
    }
    /**
     * @return float
     */
    public function getPrice() : float
    {
        if (is_null($this->price)) {
            return 0;
        }
        return $this->price;
    }
    /**
     * @param float $price
     * @return MyContractProduct
     */
    public function setPrice(float $price) : MyContractProduct
    {
        $this->price = $price;
        return $this;
    }
}
