<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Filter\MyContractEntityFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="contract")
 * @ORM\Entity (repositoryClass="App\Repository\MyContractRepository")
 */
#[ApiResource(
    operations: [
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            normalizationContext: ['groups' => ['my_contract_item_read']]
        ), new Put(
            security: 'is_granted(\'edit\', object)',
            validationContext: ['groups' => ['mycontract_edit_validation']],
            denormalizationContext: ['groups' => ['mycontract_edit']],
            normalizationContext: ['groups' => ['mycontract_put_read']]
        ), new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/participants',
            controller: \App\Controller\GetContractParticipantsAction::class
        ),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/create-addendum',
            controller: \App\Controller\CreateContractAddendumAction::class,
            normalizationContext: ['groups' => ['mycontract_addendum_read']]
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/cancel-contract',
            controller: \App\Controller\UpdateCancelContractAction::class,
            validationContext: ['groups' => ['mycontract_cancel_validation']],
            denormalizationContext: ['groups' => ['mycontract_cancel_edit']],
            normalizationContext: ['groups' => ['mycontract_addendum_read']]
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/contract-amended',
            controller: \App\Controller\UpdateAmendedContractAction::class,
            validationContext: ['groups' => ['mycontract_amended_validation']],
            denormalizationContext: ['groups' => ['mycontract_amended_edit']],
            normalizationContext: ['groups' => ['mycontract_addendum_read']]            
        ), new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/request-signatures',
            controller: \App\Controller\UpdateContractRequestSignaturesAction::class,
            normalizationContext: ['groups' => ['mycontract_addendum_read']]
        ), new Put(
            security: 'is_granted(\'edit\', object)',
            uriTemplate: '/my_contracts/{id}/create-schedule',
            controller: \App\Controller\CreateTeamScheduleAction::class
        ),
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/document',
            controller: \App\Controller\GetContractDocumentAction::class
        ),
        new Put(
            security: 'is_granted(\'edit\', object)',
            uriTemplate: '/my_contracts/{id}/add-product',
            controller: \App\Controller\UpdateContractAddProductAction::class
        ), new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/products',
            controller: \App\Controller\GetContractProductsAction::class
        ),
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/my_contracts/{id}/pdf-contract',
            controller: \App\Controller\GetContractPdfAction::class
        ), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'), new Post(
            uriTemplate: '/my_contracts/provider/{provider}/order/{order}',
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
            controller: \App\Controller\CreateContractAction::class
        ), new Post(
            uriTemplate: '/my_contracts/signatures-finished/{provider}',
            requirements: ['provider' => '^(clicksign|zapsign)+$'],
            controller: \App\Controller\UpdateContractSignaturesFinishedAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    filters: [\App\Filter\MyContractEntityFilter::class],
    normalizationContext: ['groups' => ['mycontract_read']],
    denormalizationContext: ['groups' => ['mycontract_write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['startDate' => 'DESC'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['contractStatus' => 'exact'])]
class MyContract
{
    const CONTRACT_STATUSES = ['Draft', 'Waiting approval', 'Active', 'Canceled', 'Amended'];
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MyContractModel")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_model_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"my_contract_item_read", "mycontract_edit", "mycontract_put_read", "mycontract_addendum_read"})
     * @Assert\NotBlank(groups={"mycontract_edit_validation"})
     */
    private $contractModel;
    /**
     * @ORM\Column(name="contract_status", type="string", columnDefinition="enum('Active', 'Canceled', 'Amended')")
     * @Groups({
     *   "mycontract_read",
     *   "my_contract_item_read",
     *   "mycontract_put_read",
     *   "mycontract_addendum_read",
     *   "mycontractpeople_read"
     * })
     */
    private $contractStatus = 'Draft';
    /**
     * @ORM\Column(name="start_date", type="datetime",  nullable=false)
     * @Groups({
     *   "mycontract_read",
     *   "my_contract_item_read",
     *   "mycontract_edit",
     *   "mycontract_put_read",
     *   "mycontract_addendum_read",
     *   "mycontractpeople_read"
     * })
     * @Assert\NotBlank(groups={"mycontract_edit_validation"})
     * @Assert\DateTime(groups={"mycontract_edit_validation"})
     */
    private $startDate;
    /**
     * @ORM\Column(name="end_date", type="datetime",  nullable=true)
     * @Groups({
     *   "mycontract_read",
     *   "my_contract_item_read",
     *   "mycontract_cancel_edit",
     *   "mycontract_put_read",
     *   "mycontract_addendum_read",
     *   "mycontractpeople_read"
     * })
     * @Assert\DateTime(groups={"mycontract_cancel_validation"})
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
     * @ORM\ManyToOne(targetEntity="App\Entity\MyContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contract_parent_id", referencedColumnName="id", nullable=true)
     * })
     * @Groups({"my_contract_item_read", "mycontract_addendum_read"})
     */
    private $contractParent;
    /**
     *
     * @ORM\OneToMany(targetEntity="App\Entity\MyContract", mappedBy="contractParent")
     * @Groups({"my_contract_item_read", "mycontract_addendum_read"})
     */
    private $contractChild;
    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany (targetEntity="App\Entity\MyContractPeople", mappedBy="contract")
     * @Groups ({"mycontract_read", "my_contract_item_read", "mycontract_put_read", "mycontract_addendum_read"})
     */
    private $contractPeople;
    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany (targetEntity="App\Entity\MyContractProduct", mappedBy="contract")
     */
    private $contractProduct;
    /**
     * @ORM\Column(name="html_content", type="text", nullable=true)
     */
    private $htmlContent = null;
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $docKey = null;
    public function __construct()
    {
        $this->startDate = new \DateTime('now');
        $this->endDate = null;
        $this->creationDate = new \DateTime('now');
        $this->alterDate = new \DateTime('now');
        $this->contractPeople = new ArrayCollection();
        $this->contractProduct = new ArrayCollection();
    }
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * @return MyContractModel
     */
    public function getContractModel(): MyContractModel
    {
        return $this->contractModel;
    }
    /**
     * @param MyContractModel $contractModel
     */
    public function setContractModel(MyContractModel $contractModel): MyContract
    {
        $this->contractModel = $contractModel;
        return $this;
    }
    /**
     * @return string
     */
    public function getContractStatus(): string
    {
        return $this->contractStatus;
    }
    /**
     * @param string $contractStatus
     * @return MyContract
     */
    public function setContractStatus(string $contractStatus): MyContract
    {
        $this->contractStatus = $contractStatus;
        return $this;
    }
    /**
     * @return DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * @param DateTime $startDate
     * @return MyContract
     */
    public function setStartDate(\DateTime $startDate): MyContract
    {
        $this->startDate = $startDate;
        return $this;
    }
    /**
     * @return DateTime
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }
    /**
     * @param DateTime $endDate
     * @return MyContract
     */
    public function setEndDate(?\DateTime $endDate): MyContract
    {
        $this->endDate = $endDate;
        return $this;
    }
    /**
     * @return DateTime
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }
    /**
     * @param DateTime $creationDate
     * @return MyContract
     */
    public function setCreationDate(\DateTime $creationDate): MyContract
    {
        $this->creationDate = $creationDate;
        return $this;
    }
    /**
     * @return DateTime
     */
    public function getAlterDate(): \DateTime
    {
        return $this->alterDate;
    }
    /**
     * @param DateTime $alterDate
     * @return MyContract
     */
    public function setAlterDate(\DateTime $alterDate): MyContract
    {
        $this->alterDate = $alterDate;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getContractParent()
    {
        return $this->contractParent;
    }
    /**
     * @param MyContract $contractParent
     * @return MyContract
     */
    public function setContractParent(MyContract $contractParent): MyContract
    {
        $this->contractParent = $contractParent;
        return $this;
    }
    /**
     * @return Collection
     */
    public function getContractPeople(): Collection
    {
        return $this->contractPeople;
    }
    /**
     * @return Collection
     */
    public function getContractProduct(): Collection
    {
        return $this->contractProduct;
    }
    /**
     * @return mixed
     */
    public function getContractChild()
    {
        return $this->contractChild;
    }
    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }
    public function setHtmlContent(string $content): self
    {
        $this->htmlContent = $content;
        return $this;
    }
    public function getDocKey(): ?string
    {
        return $this->docKey;
    }
    public function setDocKey(string $key): self
    {
        $this->docKey = $key;
        return $this;
    }
}
