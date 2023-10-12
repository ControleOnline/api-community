<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContractProductPayment
 * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ORM\Table(name="contract_product_payment", indexes={@ORM\Index(name="payer_id", columns={"payer_id"}), @ORM\Index(name="product_id", columns={"product_id"}), @ORM\Index(name="contract_id", columns={"contract_id"})})
 * @ORM\Entity
 */
class ContractProductPayment
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
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $amount;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="duedate", type="date", nullable=true, options={"default"="NULL"})
     */
    private $duedate = 'NULL';

    /**
     * @var bool
     *
     * @ORM\Column(name="sequence", type="boolean", nullable=false)
     */
    private $sequence;

    /**
     * @var bool
     *
     * @ORM\Column(name="processed", type="boolean", nullable=false)
     */
    private $processed = '0';

    /**
     * @var \ContractProduct
     *
     * @ORM\ManyToOne(targetEntity="ContractProduct")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $product;

    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payer_id", referencedColumnName="id")
     * })
     */
    private $payer;

    /**
     * @var \Contract
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_id", referencedColumnName="id")
     * })
     */
    private $contract;


}
