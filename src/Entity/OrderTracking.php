<?php

namespace App\Entity;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="order_tracking")
 * @ORM\Entity (repositoryClass="App\Repository\OrderTrackingRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'edit\', object)', uriTemplate: '/tracking', controller: \App\Controller\CreateTrackingAction::class),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['order_tracking_read']],
    denormalizationContext: ['groups' => ['order_tracking_write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['dataHora' => 'DESC', 'dataHoraEfetiva' => 'DESC'])]
#[ApiResource(
    uriTemplate: '/sales_orders/{id}/trackings.{_format}',
    uriVariables: ['id' =>
    new Link(fromClass: \App\Entity\SalesOrder::class, identifiers: ['id'], toProperty: 'order')],
    status: 200,
    filters: ['annotated_app_entity_order_tracking_api_platform_core_bridge_doctrine_orm_filter_order_filter'],
    normalizationContext: ['groups' => ['order_tracking_read']],
    operations: [new GetCollection()]
)]
class OrderTracking
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var \App\Entity\SalesOrder
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SalesOrder", inversedBy="tracking")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $order;
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $systemType;
    /**
     * @var boolean
     *
     * @ORM\Column(name="notified", type="boolean", nullable=false)
     */
    private $notified = false;
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $trackingStatus;
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $dataHora;
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $dominio;
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $filial;
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $cidade;
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $ocorrencia;
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $descricao;
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $tipo;
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $dataHoraEfetiva;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $nomeRecebedor;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"order_tracking_read"})
     */
    private $nroDocRecebedor;
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set order
     *
     * @param \App\Entity\SalesOrder $order
     * @return OrderPackage
     */
    public function setOrder(\App\Entity\SalesOrder $order = null)
    {
        $this->order = $order;
        return $this;
    }
    /**
     * Get order
     *
     * @return \App\Entity\SalesOrder
     */
    public function getOrder()
    {
        return $this->order;
    }
    public function getSystemType(): string
    {
        return $this->systemType;
    }
    public function setSystemType(string $systemType): self
    {
        $this->systemType = $systemType;
        return $this;
    }
    /**
     * Get Notified
     *
     * @return boolean
     */
    public function getNotified()
    {
        return $this->notified;
    }
    /**
     * Set Notified
     *
     * @param boolean $notified
     */
    public function setNotified($notified)
    {
        $this->notified = $notified ? 1 : 0;
        return $this;
    }
    /**
     * @param integer $trackingStatus
     */
    public function setTrackingStatus($trackingStatus): self
    {
        $this->trackingStatus = $trackingStatus;
        return $this;
    }
    /**
     * @return integer
     */
    public function getTrackingStatus(): ?int
    {
        return $this->trackingStatus;
    }
    public function getDataHora(): ?string
    {
        return $this->dataHora;
    }
    public function setDataHora(string $dataHora): self
    {
        $this->dataHora = $dataHora;
        return $this;
    }
    public function getDominio(): ?string
    {
        return $this->dominio;
    }
    public function setDominio(string $dominio): self
    {
        $this->dominio = $dominio;
        return $this;
    }
    public function getFilial(): ?string
    {
        return $this->filial;
    }
    public function setFilial(string $filial): self
    {
        $this->filial = $filial;
        return $this;
    }
    public function getCidade(): ?string
    {
        return $this->cidade;
    }
    public function setCidade(string $cidade): self
    {
        $this->cidade = $cidade;
        return $this;
    }
    public function getOcorrencia(): ?string
    {
        return $this->ocorrencia;
    }
    public function setOcorrencia(string $ocorrencia): self
    {
        $this->ocorrencia = $ocorrencia;
        return $this;
    }
    public function getDescricao(): ?string
    {
        return $this->descricao;
    }
    public function setDescricao(string $descricao): self
    {
        $this->descricao = $descricao;
        return $this;
    }
    public function getTipo(): ?string
    {
        return $this->tipo;
    }
    public function setTipo(?string $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }
    public function getDataHoraEfetiva(): ?string
    {
        return $this->dataHoraEfetiva;
    }
    public function setDataHoraEfetiva(string $dataHoraEfetiva): self
    {
        $this->dataHoraEfetiva = $dataHoraEfetiva;
        return $this;
    }
    public function getNomeRecebedor(): ?string
    {
        return $this->nomeRecebedor;
    }
    public function setNomeRecebedor(?string $nomeRecebedor): self
    {
        $this->nomeRecebedor = $nomeRecebedor;
        return $this;
    }
    public function getNroDocRecebedor(): ?string
    {
        return $this->nroDocRecebedor;
    }
    public function setNroDocRecebedor(?string $nroDocRecebedor): self
    {
        $this->nroDocRecebedor = $nroDocRecebedor;
        return $this;
    }
}
