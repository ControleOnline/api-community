<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CarrierIntegration
 *
  * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ORM\Table(name="carrier_integration")
 * @ORM\Entity(repositoryClass="App\Repository\CarrierIntegrationRepository")
 */
class CarrierIntegration
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
     * @var \App\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\People", inversedBy="peopleCompany")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="carrier_id", referencedColumnName="id")
     * })
     * @ORM\OrderBy({"alias" = "ASC"})
     */
    private $carrier;

    /**
     *
     * @ORM\Column(type="string",  nullable=true)
     */
    private $integrationType = null;

    /**
     *
     * @ORM\Column(type="string",  nullable=true)
     */
    private $integrationUser = null;

    /**
     *
     * @ORM\Column(type="string",  nullable=true)
     */
    private $integrationPassword = null;

    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 1;

    /**
     *
     * @ORM\Column(type="integer",  nullable=true)
     */
    private $averageRating = null;

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
     * Set carrier
     *
     * @param \App\Entity\People $carrier
     */
    public function setCarrier(\App\Entity\People $carrier = null)
    {
        $this->carrier = $carrier;

        return $this;
    }

    /**
     * Get carrier
     *
     * @return \App\Entity\People
     */
    public function getCarrier()
    {
        return $this->carrier;
    }
    
    public function getAverageRating()
    {
        return $this->averageRating;
    }

    public function setAverageRating($averageRating)
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getIntegrationType()
    {
        return $this->integrationType;
    }

    public function setIntegrationType($type)
    {
        $this->integrationType = $type;

        return $this;
    }

    public function getIntegrationUser()
    {
        return $this->integrationUser;
    }

    public function setIntegrationUser($user)
    {
        $this->integrationUser = $user;

        return $this;
    }

    public function getIntegrationPassword()
    {
        return $this->integrationPassword;
    }

    public function setIntegrationPassword($password)
    {
        $this->integrationPassword = $password;

        return $this;
    }

    public function getEnabled()
    {
        return $this->enable;
    }

    public function setEnabled($enable)
    {
        $this->enable = $enable ? : 0;

        return $this;
    }
}
