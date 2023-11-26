<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="package")
 * @ORM\Entity (repositoryClass="App\Repository\PackageRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['package_read']], denormalizationContext: ['groups' => ['package_write']])]
class Package
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;
    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false, options={"default"="1"})
     */
    private $active = true;
    /**
     * Get the value of id
     */
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * Get the value of name
     */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * Set the value of name
     */
    public function setName(string $name) : self
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Get the value of active
     */
    public function isActive() : bool
    {
        return $this->active;
    }
    /**
     * Set the value of active
     */
    public function setActive(bool $active) : self
    {
        $this->active = $active;
        return $this;
    }
}
