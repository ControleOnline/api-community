<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * DeliveryRestrictionMaterial
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="delivery_restriction_material", uniqueConstraints={@ORM\UniqueConstraint (name="people_id", columns={"people_id", "product_material_id"})}, indexes={@ORM\Index (name="product_material_id", columns={"product_material_id"}), @ORM\Index(name="IDX_3EA6FA873147C936", columns={"people_id"})})
 * @ORM\Entity
 * @ORM\Table (name="product_material", uniqueConstraints={@ORM\UniqueConstraint (name="material", columns={"material"})})
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'read\', object)'), new Put(security: 'is_granted(\'edit\', object)', denormalizationContext: ['groups' => ['delivery_restriction_material_edit']]), new Delete(security: 'is_granted(\'delete\', object)'), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'), new Post(securityPostDenormalize: 'is_granted(\'create\', object)')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['delivery_restriction_material_read']], denormalizationContext: ['groups' => ['delivery_restriction_material_write']])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact', 'public' => 'exact'])]
class DeliveryRestrictionMaterial
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
     * @ORM\Column(name="restriction_type", type="string", length=0, nullable=false, options={"default"="'delivery_denied'"})
     */
    private $restrictionType = 'delivery_denied';
    /**
     * @var bool
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = false;
    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;
    /**
     * @var \ProductMaterial
     *
     * @ORM\ManyToOne(targetEntity="ProductMaterial")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_material_id", referencedColumnName="id")
     * })
     */
    private $productMaterial;
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    public function getRestrictionType() : ?string
    {
        return $this->restrictionType;
    }
    public function setRestrictionType(string $restrictionType) : self
    {
        $this->restrictionType = $restrictionType;
        return $this;
    }
    public function getPublic() : ?bool
    {
        return $this->public;
    }
    public function setPublic(bool $public) : self
    {
        $this->public = $public;
        return $this;
    }
    public function getPeople() : ?People
    {
        return $this->people;
    }
    public function setPeople(?People $people) : self
    {
        $this->people = $people;
        return $this;
    }
    public function getProductMaterial() : ?ProductMaterial
    {
        return $this->productMaterial;
    }
    public function setProductMaterial(?ProductMaterial $productMaterial) : self
    {
        $this->productMaterial = $productMaterial;
        return $this;
    }
}
