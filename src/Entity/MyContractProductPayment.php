<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="contract_product_payment")
 * @ORM\Entity(repositoryClass="App\Repository\MyContractProductPaymentRepository")
 * @ORM\EntityListeners({App\Listener\LogListener::class}) 
 */
class MyContractProductPayment
{

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MyContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $contract;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MyContractProduct")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payer_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $payer;

    /**
     * @ORM\Column(name="amount", type="float", nullable=false)
     */
    private $amount;

    /**
     * @ORM\Column(name="duedate", type="date",  nullable=true)
     */
    private $dueDate;

    /**
     * @ORM\Column(name="sequence", type="integer", nullable=false)
     */
    private $sequence;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $processed = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return MyContract
     */
    public function getContract(): MyContract
    {
        return $this->contract;
    }

    /**
     * @param MyContract $contract
     * @return MyContractProductPayment
     */
    public function setContract(MyContract $contract): self
    {
        $this->contract = $contract;

        return $this;
    }

    /**
     * @return MyContractProduct
     */
    public function getProduct(): MyContractProduct
    {
        return $this->product;
    }

    /**
     * @param  MyContractProduct $product
     * @return MyContractProductPayment
     */
    public function setProduct(MyContractProduct $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return People
     */
    public function getPayer(): People
    {
        return $this->payer;
    }

    /**
     * @param  People $payer
     * @return MyContractProductPayment
     */
    public function setPayer(People $payer): self
    {
        $this->payer = $payer;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param  float $amount
     * @return MyContractProductPayment
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDuedate(): \DateTime
    {
        return $this->dueDate;
    }

    /**
     * @param  DateTime $dueDate
     * @return MyContractProductPayment
     */
    public function setDuedate(\DateTime $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * @param  int $sequence
     * @return MyContractProductPayment
     */
    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function isProcessed()
    {
        return $this->processed === 1;
    }

    public function setProcessed($processed): self
    {
        $this->processed = $processed ?: 0;

        return $this;
    }
}
