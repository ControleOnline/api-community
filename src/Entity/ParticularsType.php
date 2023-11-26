<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="particulars_type")
 * @ORM\Entity (repositoryClass="App\Repository\ParticularsTypeRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['particularstype_read']], denormalizationContext: ['groups' => ['particularstype_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact', 'context' => 'partial', 'fieldType' => 'exact'])]
class ParticularsType
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
     * @ORM\Column(name="people_type", type="string", length=1, nullable=false)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $peopleType;
    /**
     * @ORM\Column(name="type_value", type="string", length=255, nullable=false)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $typeValue;
    /**
     * @ORM\Column(name="field_type", type="string", length=255, nullable=false)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $fieldType;
    /**
     * @ORM\Column(name="context", type="string", length=255, nullable=false)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $context;
    /**
     * @ORM\Column(name="required", type="string", length=255, nullable=true)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $required;
    /**
     * @ORM\Column(name="field_configs", type="string", nullable=true)
     * @Groups({"particularstype_read", "particulars_read"})
     */
    private $fieldConfigs;
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
    public function setPeopleType(string $type) : self
    {
        $this->peopleType = $type;
        return $this;
    }
    public function getPeopleType() : string
    {
        return $this->peopleType;
    }
    public function setTypeValue(string $value) : self
    {
        $this->typeValue = $value;
        return $this;
    }
    public function getTypeValue() : string
    {
        return $this->typeValue;
    }
    public function setFieldType(string $value) : self
    {
        $this->fieldType = $value;
        return $this;
    }
    public function getFieldType() : string
    {
        return $this->fieldType;
    }
    public function setContext(string $value) : self
    {
        $this->context = $value;
        return $this;
    }
    public function getContext() : string
    {
        return $this->context;
    }
    public function setRequired(string $value) : self
    {
        $this->required = $value;
        return $this;
    }
    public function getRequired() : ?string
    {
        return $this->required;
    }
    public function setFieldConfigs(string $value) : self
    {
        $this->fieldConfigs = $value;
        return $this;
    }
    public function getFieldConfigs() : ?string
    {
        return $this->fieldConfigs;
    }
}
