<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleRole
 *
 * @ORM\Table(name="people_role", uniqueConstraints={@ORM\UniqueConstraint(name="company_id", columns={"company_id", "people_id", "role_id"})}, indexes={@ORM\Index(name="people_id", columns={"people_id"}), @ORM\Index(name="role_id", columns={"role_id"}), @ORM\Index(name="IDX_55A046DA979B1AD6", columns={"company_id"})})
 * @ORM\Entity
 *  @ORM\EntityListeners({App\Listener\LogListener::class})
 */
class PeopleRole
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
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     */
    private $company;

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
     * @var \Role
     *
     * @ORM\ManyToOne(targetEntity="Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     * })
     */
    private $role;



    /**
     * Get the value of id
     */
    public function getId(): int
    {
        return $this->id;
    }



    /**
     * Get the value of company
     */
    public function getCompany(): \App\Entity\People
    {
        return $this->company;
    }

    /**
     * Set the value of company
     */
    public function setCompany(\App\Entity\People $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get the value of people
     */
    public function getPeople(): \App\Entity\People
    {
        return $this->people;
    }

    /**
     * Set the value of people
     */
    public function setPeople(\App\Entity\People $people): self
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get the value of role
     */
    public function getRole(): \App\Entity\Role
    {
        return $this->role;
    }

    /**
     * Set the value of role
     */
    public function setRole(\App\Entity\Role $role): self
    {
        $this->role = $role;

        return $this;
    }
}
