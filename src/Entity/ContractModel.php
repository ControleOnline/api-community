<?php


namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="contract_model")
 * @ORM\Entity
 * @ORM\EntityListeners({App\Listener\LogListener::class}) 
 */
class ContractModel
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="contract_model", type="string", nullable=false)
     */
    private $contract_model;

    /**
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * @ORM\Column(name="people_id", type="integer", nullable=true) 
     */
    private $peopleId;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContractModel(): string
    {
        return $this->contract_model;
    }

    /**
     * @param string $contract_model
     * @return ContractModel
     */
    public function setContractModel(string $contract_model): ContractModel
    {
        $this->contract_model = $contract_model;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return ContractModel
     */
    public function setContent(string $content): ContractModel
    {
        $this->content = $content;
        return $this;
    }

    public function getPeopleId(): int
    {
        return $this->peopleId;
    }

    public function setPeopleId(int $peopleId): ContractModel
    {
        $this->peopleId = $peopleId;
        return $this;
    }
}
