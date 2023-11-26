<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use GuzzleHttp\UriTemplate;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Provider
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\ProviderRepository")
 * @ORM\Table (name="people")
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/providers/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)'
        ),
        new Get(
            uriTemplate: '/providers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/summary',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonSummaryAction::class
        ),
        new Get(
            uriTemplate: '/providers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Delete(
            uriTemplate: '/providers/{id}/addresses',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonAddressesAction::class
        ),
        new Get(
            uriTemplate: '/providers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Delete(
            uriTemplate: '/providers/{id}/documents',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonDocumentsAction::class
        ),
        new Get(
            uriTemplate: '/providers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Delete(
            uriTemplate: '/providers/{id}/emails',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonEmailsAction::class
        ),
        new Get(
            uriTemplate: '/providers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Delete(
            uriTemplate: '/providers/{id}/phones',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonPhonesAction::class
        ),
        new Get(
            uriTemplate: '/providers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/billing',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonBillingAction::class
        ), new Get(
            uriTemplate: '/providers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/providers/{id}/files/{fileId}',
            requirements: ['id' => '^\\d+$', 'fileId' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\DownloadPersonFileAction::class
        ),
        new Delete(
            uriTemplate: '/providers/{id}/files',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonFilesAction::class
        ),
        new Get(
            uriTemplate: '/providers/{id}/regions',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'read\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/regions',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Put(
            uriTemplate: '/providers/{id}/regions/{regionId}',
            requirements: ['id' => '^\\d+$', 'regionId' => '^\\d+$'],
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Delete(
            uriTemplate: '/providers/{id}/regions',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'delete\', object)',
            controller: \App\Controller\AdminPersonRegionsAction::class
        ),
        new Post(
            uriTemplate: '/providers/{id}/upload-logo',
            security: 'is_granted(\'edit\', object)',
            controller: \App\Controller\UploadPersonImageAction::class,
            deserialize: false,
            openapiContext: ['consumes' => ['multipart/form-data']]
        ),
        new Post(
            uriTemplate: '/providers',
            controller: \App\Controller\CreateProviderAction::class,
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/providers',
            controller: \App\Controller\GetProviderCollectionAction::class
        ),
        ],
        formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
        security: 'is_granted(\'ROLE_CLIENT\')',
        normalizationContext: ['groups' => ['provider_read']],
        denormalizationContext: ['groups' => ['provider_write']])]

#[ApiFilter(filterClass: OrderFilter::class, properties: ['name' => 'ASC'])]
class Provider extends Person
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"provider_read", "company_expense_read"})
     */
    private $id;
    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 1;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"provider_read", "provider_write", "company_expense_read", "provider_edit"})
     * @Assert\NotBlank
     */
    private $name;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $registerDate;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"provider_read", "provider_write", "provider_edit"})
     * @Assert\NotBlank
     */
    private $alias;
    /**
     *
     * @ORM\Column(type="string", length=1, nullable=false)
     * @Groups({"provider_read", "provider_write"})
     * @Assert\NotBlank
     * @Assert\Choice({"J", "F"})
     */
    private $peopleType;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     * @Groups({"provider_read"})
     */
    private $foundationDate = null;

    public function __construct()
    {
        $this->enable = 0;
        $this->registerDate = new \DateTime('now');
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
    public function setName(string $name) : self
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Get name
     *
     * @return string
     */
    public function getName() : string
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
    public function getFoundationDate() : ?\DateTime
    {
        return $this->foundationDate;
    }
    public function setFoundationDate(\DateTimeInterface $date): self
    {
        $this->foundationDate = $date;
        return $this;
    }
}
