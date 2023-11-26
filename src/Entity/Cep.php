<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use DoctrineExtensions\Query\Mysql\Lpad;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * Cep
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="cep", uniqueConstraints={@ORM\UniqueConstraint (name="CEP", columns={"cep"})})
 * @ORM\Entity (repositoryClass="App\Repository\CepRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['cep_read']], denormalizationContext: ['groups' => ['cep_write']])]
class Cep
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
     * @ORM\Column(name="cep", type="integer", nullable=false)
     * @Groups({"people_read", "address_read"})
     */
    private $cep;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Street", mappedBy="cep")
     */
    private $street;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->street = new \Doctrine\Common\Collections\ArrayCollection();
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
    /**
     * Set cep
     *
     * @param integer $cep
     * @return Cep
     */
    public function setCep($cep)
    {
        $this->cep = $cep;
        return $this;
    }
    /**
     * Get cep
     *
     * @return integer
     */
    public function getCep()
    {
        return STR_PAD($this->cep, 8, "0", STR_PAD_LEFT);
    }
    /**
     * Add street
     *
     * @param \App\Entity\Street $street
     * @return Cep
     */
    public function addStreet(\App\Entity\Street $street)
    {
        $this->street[] = $street;
        return $this;
    }
    /**
     * Remove street
     *
     * @param \App\Entity\Street $street
     */
    public function removeStreet(\App\Entity\Street $street)
    {
        $this->street->removeElement($street);
    }
    /**
     * Get street
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStreet()
    {
        return $this->street;
    }
}
