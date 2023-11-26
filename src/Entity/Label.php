<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
/**
 * Label
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="labels")
 * @ORM\Entity ()
 */
#[ApiResource(operations: [new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/label/{orderId}', openapiContext: [], controller: \App\Controller\CreateNewLabel::class)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class Label
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
     * @var integer
     *
     * @ORM\Column(name="people_id", type="integer",  nullable=false)
     */
    private $peopleId;
    /**
     * @var string
     *
     * @ORM\Column(name="shipment_id", type="string",  nullable=false)
     */
    private $shipmentId;
    /**
     * @var integer
     *
     * @ORM\Column(name="carrier_id", type="integer",  nullable=false)
     */
    private $carrierId;
    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="integer",  nullable=false)
     */
    private $orderId;
    /**
     * @var string
     *
     * @ORM\Column(name="cod_barra", type="string",  nullable=false)
     */
    private $codBarra;
    /**
     * @var string
     *
     * @ORM\Column(name="last_mile", type="string",  nullable=false)
     */
    private $lastMile;
    /**
     * @var string
     *
     * @ORM\Column(name="posicao", type="string",  nullable=false)
     */
    private $posicao;
    /**
     * @var integer
     *
     * @ORM\Column(name="prioridade", type="integer",  nullable=false)
     */
    private $prioridade;
    /**
     * @var string
     *
     * @ORM\Column(name="rota", type="string",  nullable=false)
     */
    private $rota;
    /**
     * @var string
     *
     * @ORM\Column(name="rua", type="string",  nullable=false)
     */
    private $rua;
    /**
     * @var integer
     *
     * @ORM\Column(name="seq_volume", type="integer",  nullable=false)
     */
    private $seqVolume;
    /**
     * @var string
     *
     * @ORM\Column(name="unidade_destino", type="string",  nullable=false)
     */
    private $unidadeDestino;
    /**
     * @var \DateTimeInterface
     * @ORM\Column(name="created_at", type="datetime",  nullable=false, columnDefinition="DATETIME")
     */
    private $createdAt;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * Get the value of peopleId
     *
     * @return integer
     */
    public function getPeopleId()
    {
        return $this->peopleId;
    }
    /**
     * Set the value of peopleId
     *
     * @param integer $peopleId
     * @return self
     */
    public function setPeopleId($peopleId) : self
    {
        $this->peopleId = $peopleId;
        return $this;
    }
    /**
     * Get the value of carrierId
     *
     * @return integer
     */
    public function getCarrierId()
    {
        return $this->carrierId;
    }
    /**
     * Set the value of carrierId
     *
     * @param integer $carrierId
     * @return self
     */
    public function setCarrierId($carrierId) : self
    {
        $this->carrierId = $carrierId;
        return $this;
    }
    /**
     * Get the value of orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }
    /**
     * Set the value of orderId
     *
     * @param integer $orderId
     * @return self
     */
    public function setOrderId($orderId) : self
    {
        $this->orderId = $orderId;
        return $this;
    }
    /**
     * Get the value of codBarra
     *
     * @return string
     */
    public function getCodBarra()
    {
        return $this->codBarra;
    }
    /**
     * Set the value of codBarra
     *
     * @param string $codBarra
     * @return self
     */
    public function setCodBarra($codBarra) : self
    {
        $this->codBarra = $codBarra;
        return $this;
    }
    /**
     * Get the value of lastMile
     *
     * @return string
     */
    public function getLastMile()
    {
        return $this->lastMile;
    }
    /**
     * Set the value of lastMile
     *
     * @param string $lastMile
     * @return self
     */
    public function setLastMile($lastMile) : self
    {
        $this->lastMile = $lastMile;
        return $this;
    }
    /**
     * Get the value of posicao
     *
     * @return string
     */
    public function getPosicao()
    {
        return $this->posicao;
    }
    /**
     * Set the value of posicao
     *
     * @param string $posicao
     * @return self
     */
    public function setPosicao($posicao) : self
    {
        $this->posicao = $posicao;
        return $this;
    }
    /**
     * Get the value of prioridade
     *
     * @return integer
     */
    public function getPrioridade()
    {
        return $this->prioridade;
    }
    /**
     * Set the value of prioridade
     *
     * @param integer $prioridade
     * @return self
     */
    public function setPrioridade($prioridade) : self
    {
        $this->prioridade = $prioridade;
        return $this;
    }
    /**
     * Get the value of rota
     *
     * @return string
     */
    public function getRota()
    {
        return $this->rota;
    }
    /**
     * Set the value of rota
     *
     * @param string $rota
     * @return self
     */
    public function setRota($rota) : self
    {
        $this->rota = $rota;
        return $this;
    }
    /**
     * Get the value of rua
     *
     * @return string
     */
    public function getRua()
    {
        return $this->rua;
    }
    /**
     * Set the value of rua
     *
     * @param string $rua
     * @return self
     */
    public function setRua($rua) : self
    {
        $this->rua = $rua;
        return $this;
    }
    /**
     * Get the value of seqVolume
     *
     * @return integer
     */
    public function getSeqVolume()
    {
        return $this->seqVolume;
    }
    /**
     * Set the value of seqVolume
     *
     * @param integer $seqVolume
     * @return self
     */
    public function setSeqVolume($seqVolume) : self
    {
        $this->seqVolume = $seqVolume;
        return $this;
    }
    /**
     * Get the value of unidadeDestino
     *
     * @return string
     */
    public function getUnidadeDestino()
    {
        return $this->unidadeDestino;
    }
    /**
     * Set the value of unidadeDestino
     *
     * @param string $unidadeDestino
     * @return self
     */
    public function setUnidadeDestino($unidadeDestino) : self
    {
        $this->unidadeDestino = $unidadeDestino;
        return $this;
    }
    /**
     * Get the value of createdAt
     *
     * @return \DateTimeInterface
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    /**
     * Get the value of shipmentId
     */
    public function getShipmentId()
    {
        return $this->shipmentId;
    }
    /**
     * Set the value of shipmentId
     */
    public function setShipmentId($shipmentId) : self
    {
        $this->shipmentId = $shipmentId;
        return $this;
    }
}
// create table labels (
//     id int primary key auto_increment,
//     people_id int not null,
//     carrier_id int not null,
//     shipment_id varchar(255) not null,
//     order_id int not null,
//     cod_barra varchar(255) not null,
//     last_mile varchar(255) not null,
//     unidade_destino varchar(255) not null,
//     posicao varchar(255) not null,
//     prioridade int not null,
//     seq_volume int not null,
//     rota varchar(255) not null,
//     rua varchar(255) not null,
//     created_at datetime not null DEFAULT CURRENT_TIMESTAMP,
//     foreign key (people_id) references people (id),
//     foreign key (carrier_id) references people (id),
//     foreign key (order_id) references orders (id)
// )
