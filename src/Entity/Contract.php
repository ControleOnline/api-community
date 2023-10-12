<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\ContractRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new Put(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/contracts/{id}/change/payment', controller: \App\Controller\ChangeContractPaymentAction::class), new Put(uriTemplate: 'contracts/{id}/status/{status}', controller: \App\Controller\ChangeContractStatusAction::class, openapiContext: []), new Post(), new GetCollection()], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class Contract
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"contract_people:read", "task_read","logistic_read"})
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractModel")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(referencedColumnName="id", nullable=false)
     * })
     */
    private $contractModel;
    /**
     * @ORM\Column(name="contract_status", type="string")
     * @Groups("contract_people:read")
     */
    private $contractStatus;
    //, columnDefinition="enum('Active', 'Canceled', 'Amended')"
    /**
     * @ORM\Column(name="doc_key", type="string")
     * @Groups("contract_people:read")
     */
    private $doc_key;
    /**
     * @ORM\Column(name="start_date", type="datetime",  nullable=false)
     * @Groups("contract_people:read")
     */
    private $startDate;
    /**
     * @ORM\Column(name="end_date", type="datetime",  nullable=false)
     * @Groups("contract_people:read")
     */
    private $endDate;
    /**
     * @ORM\Column(name="creation_date", type="datetime",  nullable=false)
     */
    private $creationDate;
    /**
     * @ORM\Column(name="alter_date", type="datetime",  nullable=false)
     */
    private $alterDate;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractModel")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_parent_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $contractParent;
    /**
     * Many Contracts have Many Peoples.
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\People")
     * @ORM\JoinTable(name="contract_people",
     *      joinColumns={@ORM\JoinColumn(name="contract_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="people_id", referencedColumnName="id")}
     *      )
     * @Groups({"task_read"})
     */
    private $peoples;
    /**
     * @ORM\OneToOne(targetEntity=Team::class, mappedBy="contract")
     */
    private $team;
    /**
     * @ORM\Column(name="html_content", type="text", nullable=true)
     */
    private $htmlContent = null;
    public function __construct()
    {
        $this->startDate = new DateTime('now');
        $this->endDate = new DateTime('now');
        $this->creationDate = new DateTime('now');
        $this->alterDate = new DateTime('now');
    }
    public function getId() : int
    {
        return $this->id;
    }
    public function getContractModel() : ContractModel
    {
        return $this->contractModel;
    }
    public function setContractModel(ContractModel $contract_model) : Contract
    {
        $this->contractModel = $contract_model;
        return $this;
    }
    public function getKey() : ?string
    {
        return $this->doc_key;
    }
    public function setKey(string $doc_key) : Contract
    {
        $this->doc_key = $doc_key;
        return $this;
    }
    public function getContractStatus() : string
    {
        return $this->contractStatus;
    }
    public function setContractStatus(string $contract_status) : Contract
    {
        $this->contractStatus = $contract_status;
        return $this;
    }
    public function getStartDate() : DateTime
    {
        return $this->startDate;
    }
    public function setStartDate(DateTime $start_date) : Contract
    {
        $this->startDate = $start_date;
        return $this;
    }
    public function getEndDate() : ?DateTime
    {
        return $this->endDate;
    }
    public function setEndDate(DateTime $end_date) : Contract
    {
        $this->endDate = $end_date;
        return $this;
    }
    public function getCreationDate() : DateTime
    {
        return $this->creationDate;
    }
    public function setCreationDate(DateTime $creation_date) : Contract
    {
        $this->creationDate = $creation_date;
        return $this;
    }
    public function getAlterDate() : DateTime
    {
        return $this->alterDate;
    }
    public function setAlterDate(DateTime $alter_date) : Contract
    {
        $this->alterDate = $alter_date;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getContractParent()
    {
        return $this->contractParent;
    }
    public function setContractParentId(Contract $contract_parent) : Contract
    {
        $this->contract_parent = $contract_parent;
        return $this;
    }
    public function getPeoples() : Collection
    {
        return $this->peoples;
    }
    public function getTeam() : ?Team
    {
        return $this->team;
    }
    public function setTeam(Team $team) : self
    {
        $this->team = $team;
        // set the owning side of the relation if necessary
        if ($team->getContract() !== $this) {
            $team->setContract($this);
        }
        return $this;
    }
    public function getHtmlContent() : ?string
    {
        return $this->htmlContent;
    }
    public function setHtmlContent(?string $content) : self
    {
        $this->htmlContent = $content;
        return $this;
    }
}
