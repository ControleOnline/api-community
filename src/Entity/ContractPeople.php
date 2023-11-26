<?php

namespace App\Entity;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\Table (name="contract_people")
 * @ORM\Entity
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
#[ApiResource(uriTemplate: '/people/{id}/contracts_peoples.{_format}', uriVariables: ['id' => new Link(fromClass: \App\Entity\People::class, identifiers: ['id'], toProperty: 'people')], status: 200, operations: [new GetCollection()])]
class ContractPeople
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups("contract_people:read")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Contract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups("contract_people:read")
     */
    private $contract;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="contractsPeople")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups("contract_people:read")
     */
    private $people;
    /**
     * @ORM\Column(name="people_type", type="string", columnDefinition="enum('Beneficiary', 'Witness', 'Payer', 'Provider')")
     * @Groups("contract_people:read")
     */
    private $people_type;
    /**
     * @ORM\Column(name="contract_percentage", type="float",  nullable=true)
     * @Groups("contract_people:read")
     */
    private $contract_percentage;
    public function getId() : int
    {
        return $this->id;
    }
    public function getContract() : Contract
    {
        return $this->contract;
    }
    public function setContract(Contract $contract) : ContractPeople
    {
        $this->contract = $contract;
        return $this;
    }
    public function getPeople() : People
    {
        return $this->people;
    }
    public function setPeople(People $people) : ContractPeople
    {
        $this->people = $people;
        return $this;
    }
    public function getPeopleType() : string
    {
        return $this->people_type;
    }
    public function setPeopleType(string $people_type) : ContractPeople
    {
        $this->people_type = $people_type;
        return $this;
    }
    public function getContractPercentage() : float
    {
        return $this->contract_percentage ?: 0;
    }
    public function setContractPercentage(float $contract_percentage) : ContractPeople
    {
        $this->contract_percentage = $contract_percentage;
        return $this;
    }
}
