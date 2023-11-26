<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\DocRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass=DocRepository::class)
 * @ORM\Table (name="docs")
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/filesb/{id}',
            requirements: ['id' => '^\\d+$'],
            controller: \App\Controller\AdminFilesController::class,
            defaults: ['_api_receive' => false]
        ), new Post(
            uriTemplate: '/filesb',
            controller: \App\Controller\AdminFilesController::class,
            defaults: ['_api_receive' => false]
        ), new Post(
            uriTemplate: '/filesb/{id}',
            requirements: ['id' => '^\\d+$'],
            controller: \App\Controller\AdminFilesController::class,
            defaults: ['_api_receive' => false]
        ), new Delete(
            uriTemplate: '/filesb/{id}',
            requirements: ['id' => '^\\d+$'],
            controller: \App\Controller\AdminFilesController::class,
            defaults: ['_api_receive' => false]
        ),
        new Get(
            uriTemplate: '/filesb/{id}/download',
            requirements: ['id' => '^\\d+$'],
            controller: \App\Controller\DownloadFilesGlobal::class,
            defaults: ['_api_receive' => false]
        ), new GetCollection(
            uriTemplate: '/filesb',
            controller: \App\Controller\AdminFilesController::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    security: 'is_granted(\'ROLE_CLIENT\')',
    normalizationContext: ['groups' => ['files_read']]
)]
class Filesb
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $register_date;
    /**
     * @ORM\Column(type="string", length=30)
     */
    private $type;
    /**
     * @ORM\Column(type="string", length=30, nullable=false)
     */
    private $name;
    /**
     * @ORM\Column(type="date", nullable=false)
     */
    private $date_period;
    /**
     * @var \ControleOnline\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })     
     */
    private $status;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $file_name_guide;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $file_name_receipt;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $people_id;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $companyId;
    public function __construct()
    {
        $this->register_date = new DateTime('now');
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function getRegisterDate(): ?DateTimeInterface
    {
        return $this->register_date;
    }
    public function setRegisterDate(DateTimeInterface $register_date): self
    {
        $this->register_date = $register_date;
        return $this;
    }
    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function getDatePeriodByName(): string
    {
        return DocRepository::formatDateByName($this->date_period->format('Y-m-d'));
    }
    public function getDatePeriod(): ?DateTimeInterface
    {
        return $this->date_period;
    }
    public function setDatePeriod(DateTimeInterface $date_period): self
    {
        $this->date_period = $date_period;
        return $this;
    }
    /**
     * Set status
     *
     * @param \ControleOnline\Entity\Status $status
     * @return Order
     */
    public function setStatus(\ControleOnline\Entity\Status $status = null)
    {
        $this->status = $status;
        return $this;
    }
    /**
     * Get status
     *
     * @return \ControleOnline\Entity\Status
     */
    public function getStatus()
    {
        return $this->status;
    }
    public function getFileNameGuideByLast(): ?string
    {
        return DocRepository::formatByLast($this->file_name_guide);
    }
    public function getFileNameGuide(): ?string
    {
        return $this->file_name_guide;
    }
    public function setFileNameGuide(?string $file_name_guide): self
    {
        $this->file_name_guide = $file_name_guide;
        return $this;
    }
    public function getFileNameReceiptByLast(): ?string
    {
        return DocRepository::formatByLast($this->file_name_receipt);
    }
    public function getFileNameReceipt(): ?string
    {
        return $this->file_name_receipt;
    }
    public function setFileNameReceipt(?string $file_name_receipt): self
    {
        $this->file_name_receipt = $file_name_receipt;
        return $this;
    }
    public function getPeopleId(): int
    {
        return $this->people_id;
    }
    public function setPeopleId(int $people_id): self
    {
        $this->people_id = $people_id;
        return $this;
    }
    public function getCompanyId()
    {
        return $this->companyId;
    }
    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }
}
