<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="config", uniqueConstraints={@ORM\UniqueConstraint (name="people_id", columns={"people_id","config_key"})})
 * @ORM\Entity (repositoryClass="App\Repository\ConfigRepository")
 */
#[ApiResource(operations: [new GetCollection(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', 
uriTemplate: '/configs/app-config', controller: \App\Controller\GetAppConfigAction::class)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class Config
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
     * @ORM\ManyToOne(targetEntity="App\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;
    /**
     * @var string
     *
     * @ORM\Column(name="config_key", type="string", length=255, nullable=false)
     */
    private $config_key;
    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=255, nullable=false)
     */
    private $visibility;
    /**
     * @var string
     *
     * @ORM\Column(name="config_value", type="string", length=255, nullable=false)
     */
    private $config_value;
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
     * @param \App\Entity\People $people
     * @return PeopleConfigKey
     */
    public function setPeople(\App\Entity\People $people = null)
    {
        $this->people = $people;
        return $this;
    }
    /**
     * Get people
     *
     * @return \App\Entity\People
     */
    public function getPeople()
    {
        return $this->people;
    }
    /**
     * Set config_key
     *
     * @param string config_key
     * @return PeopleConfigKey
     */
    public function setConfigKey($config_key)
    {
        $this->config_key = $config_key;
        return $this;
    }
    /**
     * Get config_key
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
    }
    /**
     * Set visibility
     *
     * @param string visibility
     * @return Config
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }
    /**
     * Get visibility
     *
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }
    /**
     * Set config_value
     *
     * @param string config_value
     * @return PeopleConfigKey
     */
    public function setConfigValue($config_value)
    {
        $this->config_value = $config_value;
        return $this;
    }
    /**
     * Get config_value
     *
     * @return string
     */
    public function getConfigValue()
    {
        return $this->config_value;
    }
}
