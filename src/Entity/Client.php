<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\CreateClientAction;
use App\Controller\GetClientCollectionAction;
use App\Controller\UpdateClientAction;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Filter\ClientEntityFilter;

/**
 * Client
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\ClientRepository")
 * @ORM\Table (name="people")
 */
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/clients/{id}', requirements: ['id' => '^\\d+$'], security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            uriTemplate: '/clients/{id}',
            requirements: ['id' => '^\\d+$'],
            security: 'is_granted(\'ROLE_CLIENT\')',
            controller: UpdateClientAction::class
        ), new GetCollection(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/clients',
            controller: GetClientCollectionAction::class
        ),
        new Post(
            uriTemplate: '/clients',
            controller: CreateClientAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    security: 'is_granted(\'ROLE_CLIENT\')',
    filters: [ClientEntityFilter::class],
    normalizationContext: ['groups' => ['client_read']],
    denormalizationContext: ['groups' => ['client_write']]
)]
class Client
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
     * @Groups({"client_read"})
     */
    private $name;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $registerDate;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"client_read"})
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
     * @ORM\OneToMany(targetEntity="App\Entity\Document", mappedBy="people")
     * @Groups({"client_read"})
     */
    private $document;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleEmployee", mappedBy="company")
     * @Groups({"client_read"})
     */
    private $peopleEmployee;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PeopleEmployee", mappedBy="employee")
     * @ORM\OrderBy({"company" = "ASC"})
     */
    private $peopleCompany;
    public function __construct()
    {
        $this->enable = 0;
        $this->registerDate = new \DateTime('now');
        $this->document = new \Doctrine\Common\Collections\ArrayCollection();
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
    /**
     * Get document
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocument()
    {
        return $this->document;
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
