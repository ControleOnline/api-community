<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContractProduct
 *
 * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ORM\Table(name="contract_product", indexes={@ORM\Index(name="product_id", columns={"product_id"}), @ORM\Index(name="contract_id", columns={"contract_id"})})
 * @ORM\Entity
 */
class ContractProduct
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
     * @var float
     *
     * @ORM\Column(name="quantity", type="float", precision=10, scale=0, nullable=false, options={"default"="1"})
     */
    private $quantity = 1;

    /**
     * @var float
     *
     * @ORM\Column(name="product_price", type="float", precision=10, scale=0, nullable=false)
     */
    private $productPrice;

    /**
     * @var string|null
     *
     * @ORM\Column(name="other_informations", type="text", length=0, nullable=true, options={"default"="NULL"})
     */
    private $otherInformations = 'NULL';

    /**
     * @var \Contract
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_id", referencedColumnName="id")
     * })
     */
    private $contract;

    /**
     * @var \ProductOld
     *
     * @ORM\ManyToOne(targetEntity="ProductOld")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $product;


}
