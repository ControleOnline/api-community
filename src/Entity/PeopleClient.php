<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="people_client", uniqueConstraints={@ORM\UniqueConstraint (name="client_id", columns={"client_id", "company_id"})}, indexes={@ORM\Index (name="provider_id", columns={"company_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\PeopleClientRepository")
 */
#[ApiResource(operations: [new Put(uriTemplate: '/people_customers/{id}/change-status', controller: \App\Controller\ChangeClientStatusAction::class, security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'edit\', object)', requirements: ['id' => '^\\d+$']), new Post(), new GetCollection()], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class PeopleClient
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
     * @ORM\Column(type="integer", nullable=false)
     */
    private $company_id;
    /**
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $client;
    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;
    /**
     * @var float
     *
     * @ORM\Column(name="commission", type="float", nullable=false)
     */
    private $commission = 0;
    public function getId()
    {
        return $this->id;
    }
    public function setCompanyId($company_id)
    {
        $this->company_id = $company_id;
        return $this;
    }
    public function getCompanyId()
    {
        return $this->company_id;
    }
    public function setClient(People $client = null)
    {
        $this->client = $client;
        return $this;
    }
    public function getClient()
    {
        return $this->client;
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
    /**
     * Set commission
     *
     * @param float $commission
     * @return PeopleSalesman
     */
    public function setCommission($commission) : self
    {
        $this->commission = $commission;
        return $this;
    }
    /**
     * Get commission
     *
     * @return float
     */
    public function getCommission() : float
    {
        return $this->commission;
    }
}
