<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Carrier
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\CarrierRepository")
 * @ORM\Table (name="people")
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/carriers/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)'
        ),
        new Get(
            uriTemplate: '/carriers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Delete(
            uriTemplate: '/carriers/{id}/employees',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonEmployeesAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Delete(
            uriTemplate: '/carriers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Delete(
            uriTemplate: '/carriers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Delete(
            uriTemplate: '/carriers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Delete(
            uriTemplate: '/carriers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ), new Get(
            uriTemplate: '/carriers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/files/{fileId}',
            requirements: ['id' => '^\\d+$', 'fileId' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\DownloadPersonFileAction::class
        ),
        new Delete(
            uriTemplate: '/carriers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/carriers/{id}/regions',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/regions',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/regions/{regionId}',
            requirements: ['id' => '^\\d+$', 'regionId' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Delete(
            uriTemplate: '/carriers/{id}/regions',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Post(
            uriTemplate: '/carriers/{id}/upload-logo',
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\UploadPersonImageAction::class,
            deserialize: false,
            openapiContext: ['consumes' => ['multipart/form-data']]
        ),
        new Get(
            uriTemplate: '/carriers/{id}/integration',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminCarrierIntegrationAction::class
        ),
        new Put(
            uriTemplate: '/carriers/{id}/integration',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminCarrierIntegrationAction::class
        ),
        new Post(
            uriTemplate: '/carriers/{id}/rates',
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\GetRemoteCarrierRatesAction::class
        ),
        new Post(
            uriTemplate: '/carriers',
            controller: \App\Controller\CreateCarrierAction::class,
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/carriers',
            controller: \App\Controller\GetCarrierCollectionAction::class)
        ],
        formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
        security: 'is_granted(\'ROLE_CLIENT\')',
        normalizationContext: ['groups' => ['carrier_read']],
        denormalizationContext: ['groups' => ['carrier_write']])]
class Carrier extends Person
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"carrier_read"})
     */
    private $name;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $registerDate;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"carrier_read"})
     */
    private $alias;
    /**
     *
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    private $peopleType;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleEmployee", mappedBy="company")
     */
    private $peopleEmployee;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleEmployee", mappedBy="employee")
     * @ORM\OrderBy({"company" = "ASC"})
     */
    private $peopleCompany;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     * @Groups({"carrier_read"})
     */
    private $foundationDate = null;
    public function __construct()
    {
        $this->enable = 0;
        $this->registerDate = new \DateTime('now');
        $this->peopleEmployee = new \Doctrine\Common\Collections\ArrayCollection();
        $this->peopleCompany = new \Doctrine\Common\Collections\ArrayCollection();
    }
    public function getId()
    {
        return $this->id;
    }
    public function getEnabled()
    {
        return $this->enable;
    }
    public function setEnabled($enable)
    {
        $this->enable = $enable ?: 0;
        return $this;
    }
    public function setPeopleType($people_type)
    {
        $this->peopleType = $people_type;
        return $this;
    }
    public function getPeopleType()
    {
        return $this->peopleType;
    }
    /**
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    public function getAlias()
    {
        return $this->alias;
    }
    public function getRegisterDate()
    {
        return $this->registerDate;
    }
    public function setRegisterDate()
    {
        return $this->registerDate;
    }
    public function getFoundationDate(): ?\DateTime
    {
        return $this->foundationDate;
    }
    public function setFoundationDate(\DateTimeInterface $date): self
    {
        $this->foundationDate = $date;
        return $this;
    }
    /**
     * Get peopleEmployee
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeopleEmployee()
    {
        return $this->peopleEmployee;
    }
    /**
     * Get peopleCompany
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeopleCompany()
    {
        return $this->peopleCompany;
    }
}
