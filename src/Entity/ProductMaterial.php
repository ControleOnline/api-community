<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Entity\People;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * ProductMaterial
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'read\', object)'), new Put(security: 'is_granted(\'edit\', object)', denormalizationContext: ['groups' => ['product_material_edit']]), new Delete(security: 'is_granted(\'delete\', object)'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(securityPostDenormalize: 'is_granted(\'create\', object)')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['product_material_read']], denormalizationContext: ['groups' => ['product_material_write']])]
class ProductMaterial
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
     * @ORM\Column(name="material", type="string", length=500, nullable=false)
     */
    private $material;
    /**
     * @var bool
     *
     * @ORM\Column(name="revised", type="boolean", nullable=false, options={"default"="'0'"})
     */
    private $revised = false;
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
     * Set people
     *
     * @param People $people
     * @return DeliveryTax
     */
    public function setPeople(People $people = null)
    {
        $this->people = $people;
        return $this;
    }
    /**
     * Get people
     *
     * @return People
     */
    public function getPeople()
    {
        return $this->people;
    }
    public function getMaterial() : ?string
    {
        return $this->material;
    }
    public function setMaterial(string $material) : self
    {
        $this->material = $material;
        return $this;
    }
    public function getRevised() : ?bool
    {
        return $this->revised;
    }
    public function setRevised(bool $revised) : self
    {
        $this->revised = $revised;
        return $this;
    }
}
