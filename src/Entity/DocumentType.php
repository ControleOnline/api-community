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
 * DocumentType
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="document_type")
 * @ORM\Entity (repositoryClass="App\Repository\DocumentTypeRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['document_type_read']], denormalizationContext: ['groups' => ['document_type_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['peopleType' => 'exact'])]
class DocumentType
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
     * @var string
     *
     * @ORM\Column(name="document_type", type="string", length=50, nullable=false)
     * @Groups({"people_read", "document_read", "document_type_read", "carrier_read"})
     */
    private $documentType;
    /**
     * @var string
     *
     * @ORM\Column(name="people_type", type="string", length=1, nullable=false)
     * @Groups({"people_read", "document_read", "document_type_read"})
     */
    private $peopleType;
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
     * Set documentType
     *
     * @param string $documentType
     * @return DocumentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
        return $this;
    }
    /**
     * Get documentType
     *
     * @return string
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }
    /**
     * Set peopleType
     *
     * @param string $peopleType
     * @return DocumentType
     */
    public function setPeopleType($peopleType)
    {
        $this->peopleType = $peopleType;
        return $this;
    }
    /**
     * Get peopleType
     *
     * @return string
     */
    public function getPeopleType()
    {
        return $this->peopleType;
    }
}
