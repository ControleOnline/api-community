<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tax
 *
 * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ORM\Table(name="tax", uniqueConstraints={@ORM\UniqueConstraint(name="tax_name", columns={"tax_name", "tax_type", "tax_subtype", "people_id", "state_origin_id", "state_destination_id"})}, indexes={@ORM\Index(name="region_destination_id", columns={"state_destination_id"}), @ORM\Index(name="people_id", columns={"people_id"}), @ORM\Index(name="region_origin_id", columns={"state_origin_id"})})
 * @ORM\Entity
 */
class Tax
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
     * @ORM\Column(name="tax_name", type="string", length=255, nullable=false)
     */
    private $taxName;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_type", type="string", length=0, nullable=false)
     */
    private $taxType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tax_subtype", type="string", length=0, nullable=true, options={"default"="NULL"})
     */
    private $taxSubtype = 'NULL';

    /**
     * @var int
     *
     * @ORM\Column(name="tax_order", type="integer", nullable=false)
     */
    private $taxOrder = '0';

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=10, scale=0, nullable=false)
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="minimum_price", type="float", precision=10, scale=0, nullable=false)
     */
    private $minimumPrice = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="optional", type="boolean", nullable=false)
     */
    private $optional = '0';

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
     * @var \State
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_origin_id", referencedColumnName="id")
     * })
     */
    private $stateOrigin;

    /**
     * @var \State
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_destination_id", referencedColumnName="id")
     * })
     */
    private $stateDestination;
}
