<?php

namespace App\Entity;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="contract_people")
 * @ORM\Entity (repositoryClass="App\Repository\MyContractPeopleRepository")
 * @UniqueEntity (
 *     fields   ={"contract", "people", "peopleType"},
 *     errorPath="peopleType",
 *     message  ="Este participante já foi adicionado ao contrato"
 * )
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new Delete(security: 'is_granted(\'delete\', object)'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(securityPostDenormalize: 'is_granted(\'create\', object)')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['mycontractpeople_read']], denormalizationContext: ['groups' => ['mycontractpeople_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['contract' => 'exact', 'people' => 'exact', 'peopleType' => 'exact'])]
#[ApiResource(uriTemplate: '/my_contracts/{id}/contract_peoples.{_format}', uriVariables: ['id' => new Link(fromClass: \App\Entity\MyContract::class, identifiers: ['id'], toProperty: 'contract')], status: 200, filters: ['annotated_app_entity_my_contract_people_api_platform_core_bridge_doctrine_orm_filter_search_filter'], normalizationContext: ['groups' => ['mycontractpeople_read']], operations: [new GetCollection()])]
class MyContractPeople
{
    const PEOPLE_TYPES = ['Beneficiary', 'Witness', 'Payer', 'Provider'];
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MyContract", inversedBy="contractPeople")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     * @Groups({"mycontractpeople_write", "mycontractpeople_read"})
     */
    private $contract;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     * @Groups({"mycontractpeople_read", "mycontract_read", "my_contract_item_read", "mycontractpeople_write"})
     */
    private $people;
    /**
     * @ORM\Column(name="people_type", type="string", columnDefinition="enum('Beneficiary', 'Witness', 'Payer', 'Provider')")
     * @Assert\NotBlank
     * @Assert\Choice(choices=App\Entity\MyContractPeople::PEOPLE_TYPES)
     * @Groups({"mycontractpeople_read", "mycontract_read", "my_contract_item_read", "mycontractpeople_write"})
     */
    private $peopleType;
    /**
     * @ORM\Column(name="contract_percentage", type="float",  nullable=true)
     * @Assert\PositiveOrZero
     * @Groups({"mycontractpeople_read", "mycontract_read", "my_contract_item_read", "mycontractpeople_write"})
     */
    private $contractPercentage = 0;
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
     * @return MyContractPeople
     */
    public function setContract(MyContract $contract) : MyContractPeople
    {
        $this->contract = $contract;
        return $this;
    }
    /**
     * @return People
     */
    public function getPeople() : People
    {
        return $this->people;
    }
    /**
     * @param People $people
     * @return MyContractPeople
     */
    public function setPeople(People $people) : MyContractPeople
    {
        $this->people = $people;
        return $this;
    }
    /**
     * @return string
     */
    public function getPeopleType() : string
    {
        return $this->peopleType;
    }
    /**
     * @param string $peopleType
     * @return MyContractPeople
     */
    public function setPeopleType(string $peopleType) : MyContractPeople
    {
        $this->peopleType = $peopleType;
        return $this;
    }
    /**
     * @return float
     */
    public function getContractPercentage() : float
    {
        if (is_null($this->contractPercentage)) {
            return 0;
        }
        return $this->contractPercentage;
    }
    /**
     * @param float $contractPercentage
     * @return MyContractPeople
     */
    public function setContractPercentage(float $contractPercentage) : MyContractPeople
    {
        $this->contractPercentage = $contractPercentage;
        return $this;
    }
}
