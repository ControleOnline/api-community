<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LanguageCountry
 *
 * @ORM\Table(name="language_country", uniqueConstraints={@ORM\UniqueConstraint(name="language_id", columns={"language_id", "country_id"})}, indexes={@ORM\Index(name="country_id", columns={"country_id"}), @ORM\Index(name="IDX_F7BE1E3282F1BAF4", columns={"language_id"})})
 * @ORM\Entity
 * @ORM\EntityListeners({App\Listener\LogListener::class}) 
 */
class LanguageCountry
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
     * @var \App\Entity\Language
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="language_id", referencedColumnName="id")
     * })
     */
    private $language;

    /**
     * @var \App\Entity\Country
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Country", inversedBy="languageCountry")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;

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
     * Set language
     *
     * @param \App\Entity\Language $language
     * @return LanguageCountry
     */
    public function setLanguage(\App\Entity\Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return \App\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set country
     *
     * @param \App\Entity\Country $country
     * @return LanguageCountry
     */
    public function setCountry(\App\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \App\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }
}
