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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\PhoneRepository")
 * @ORM\Table (name="phone", uniqueConstraints={@ORM\UniqueConstraint (name="phone", columns={"phone","ddd","people_id"})}, indexes={@ORM\Index (columns={"people_id"})})
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['phone_read']], denormalizationContext: ['groups' => ['phone_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
class Phone
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     *
     * @ORM\Column(type="integer", length=10, nullable=false)
     * @Groups({"people_read", "phone_read", "client_read", "carrier_read"})
     */
    private $phone;
    /**
     *
     * @ORM\Column(type="integer", length=2, nullable=false)
     * @Groups({"people_read", "phone_read", "client_read", "carrier_read"})
     */
    private $ddd;
    /**
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $confirmed = false;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="phone")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;
    public function getId()
    {
        return $this->id;
    }
    public function setDdd($ddd)
    {
        $this->ddd = $ddd;
        return $this;
    }
    public function getDdd()
    {
        return $this->ddd;
    }
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }
    public function getPhone()
    {
        return $this->phone;
    }
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }
    public function getConfirmed()
    {
        return $this->confirmed;
    }
    public function setPeople(People $people = null)
    {
        $this->people = $people;
        return $this;
    }
    public function getPeople()
    {
        return $this->people;
    }
}
