<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sector
 *
 * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ORM\Table(name="sector", uniqueConstraints={@ORM\UniqueConstraint(name="sector", columns={"sector", "company_id"})}, indexes={@ORM\Index(name="company_id", columns={"company_id"})})
 * @ORM\Entity
 */
class Sector
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
     * @ORM\Column(name="sector", type="string", length=50, nullable=false)
     */
    private $sector;

    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     */
    private $company;


}
