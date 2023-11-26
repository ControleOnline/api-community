<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="particulars")
 * @ORM\Entity (repositoryClass="App\Repository\ParticularsRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['particulars_read']], denormalizationContext: ['groups' => ['particulars_write']])]
class Particulars
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
     * @var \App\Entity\ParticularsType
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ParticularsType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="particulars_type_id", referencedColumnName="id")
     * })
     * @Groups({"particulars_read"})
     */
    private $type;
    /**
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;
    /**
     * @ORM\Column(name="particular_value", type="string", nullable=false)
     * @Groups({"particulars_read"})
     */
    private $value;
    /**
     * Constructor
     */
    public function __construct()
    {
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    public function setType(ParticularsType $type) : self
    {
        $this->type = $type;
        return $this;
    }
    public function getType() : ParticularsType
    {
        return $this->type;
    }
    public function setPeople(People $people) : self
    {
        $this->people = $people;
        return $this;
    }
    public function getPeople() : People
    {
        return $this->people;
    }
    public function setValue(string $value) : self
    {
        $this->value = $value;
        return $this;
    }
    public function getValue() : string
    {
        return $this->value;
    }
}
