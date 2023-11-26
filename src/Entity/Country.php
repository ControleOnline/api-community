<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * Country
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="country", uniqueConstraints={@ORM\UniqueConstraint (name="countryCode", columns={"countryCode"})})
 * @ORM\Entity (repositoryClass="App\Repository\CountryRepository")
 */
#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], normalizationContext: ['groups' => ['country_read']], denormalizationContext: ['groups' => ['country_write']])]
class Country
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
     * @var string
     *
     * @ORM\Column(name="countryCode", type="string", length=3, nullable=false)
     */
    private $countrycode;
    /**
     * @var string
     *
     * @ORM\Column(name="countryName", type="string", length=45, nullable=false)
     * @Groups({"people_read", "address_read"})
     */
    private $countryname;
    /**
     * @var string
     *
     * @ORM\Column(name="currencyCode", type="string", length=3, nullable=true)
     */
    private $currencycode;
    /**
     * @var integer
     *
     * @ORM\Column(name="population", type="integer", nullable=true)
     */
    private $population;
    /**
     * @var string
     *
     * @ORM\Column(name="fipsCode", type="string", length=2, nullable=true)
     */
    private $fipscode;
    /**
     * @var string
     *
     * @ORM\Column(name="isoNumeric", type="string", length=4, nullable=true)
     */
    private $isonumeric;
    /**
     * @var string
     *
     * @ORM\Column(name="north", type="string", length=30, nullable=true)
     */
    private $north;
    /**
     * @var string
     *
     * @ORM\Column(name="south", type="string", length=30, nullable=true)
     */
    private $south;
    /**
     * @var string
     *
     * @ORM\Column(name="east", type="string", length=30, nullable=true)
     */
    private $east;
    /**
     * @var string
     *
     * @ORM\Column(name="west", type="string", length=30, nullable=true)
     */
    private $west;
    /**
     * @var string
     *
     * @ORM\Column(name="capital", type="string", length=30, nullable=true)
     */
    private $capital;
    /**
     * @var string
     *
     * @ORM\Column(name="continentName", type="string", length=15, nullable=true)
     */
    private $continentname;
    /**
     * @var string
     *
     * @ORM\Column(name="continent", type="string", length=2, nullable=true)
     */
    private $continent;
    /**
     * @var string
     *
     * @ORM\Column(name="areaInSqKm", type="string", length=20, nullable=true)
     */
    private $areainsqkm;
    /**
     * @var string
     *
     * @ORM\Column(name="isoAlpha3", type="string", length=3, nullable=true)
     */
    private $isoalpha3;
    /**
     * @var integer
     *
     * @ORM\Column(name="geonameId", type="integer", nullable=true)
     */
    private $geonameid;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\LanguageCountry", mappedBy="country")
     */
    private $languageCountry;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\State", mappedBy="country")
     */
    private $state;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->languageCountry = new \Doctrine\Common\Collections\ArrayCollection();
        $this->state = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set countrycode
     *
     * @param string $countrycode
     * @return Country
     */
    public function setCountrycode($countrycode)
    {
        $this->countrycode = $countrycode;
        return $this;
    }
    /**
     * Get countrycode
     *
     * @return string
     */
    public function getCountrycode()
    {
        return $this->countrycode;
    }
    /**
     * Set countryname
     *
     * @param string $countryname
     * @return Country
     */
    public function setCountryname($countryname)
    {
        $this->countryname = $countryname;
        return $this;
    }
    /**
     * Get countryname
     *
     * @return string
     */
    public function getCountryname()
    {
        return $this->countryname;
    }
    /**
     * Set currencycode
     *
     * @param string $currencycode
     * @return Country
     */
    public function setCurrencycode($currencycode)
    {
        $this->currencycode = $currencycode;
        return $this;
    }
    /**
     * Get currencycode
     *
     * @return string
     */
    public function getCurrencycode()
    {
        return $this->currencycode;
    }
    /**
     * Set population
     *
     * @param integer $population
     * @return Country
     */
    public function setPopulation($population)
    {
        $this->population = $population;
        return $this;
    }
    /**
     * Get population
     *
     * @return integer
     */
    public function getPopulation()
    {
        return $this->population;
    }
    /**
     * Set fipscode
     *
     * @param string $fipscode
     * @return Country
     */
    public function setFipscode($fipscode)
    {
        $this->fipscode = $fipscode;
        return $this;
    }
    /**
     * Get fipscode
     *
     * @return string
     */
    public function getFipscode()
    {
        return $this->fipscode;
    }
    /**
     * Set isonumeric
     *
     * @param string $isonumeric
     * @return Country
     */
    public function setIsonumeric($isonumeric)
    {
        $this->isonumeric = $isonumeric;
        return $this;
    }
    /**
     * Get isonumeric
     *
     * @return string
     */
    public function getIsonumeric()
    {
        return $this->isonumeric;
    }
    /**
     * Set north
     *
     * @param string $north
     * @return Country
     */
    public function setNorth($north)
    {
        $this->north = $north;
        return $this;
    }
    /**
     * Get north
     *
     * @return string
     */
    public function getNorth()
    {
        return $this->north;
    }
    /**
     * Set south
     *
     * @param string $south
     * @return Country
     */
    public function setSouth($south)
    {
        $this->south = $south;
        return $this;
    }
    /**
     * Get south
     *
     * @return string
     */
    public function getSouth()
    {
        return $this->south;
    }
    /**
     * Set east
     *
     * @param string $east
     * @return Country
     */
    public function setEast($east)
    {
        $this->east = $east;
        return $this;
    }
    /**
     * Get east
     *
     * @return string
     */
    public function getEast()
    {
        return $this->east;
    }
    /**
     * Set west
     *
     * @param string $west
     * @return Country
     */
    public function setWest($west)
    {
        $this->west = $west;
        return $this;
    }
    /**
     * Get west
     *
     * @return string
     */
    public function getWest()
    {
        return $this->west;
    }
    /**
     * Set capital
     *
     * @param string $capital
     * @return Country
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;
        return $this;
    }
    /**
     * Get capital
     *
     * @return string
     */
    public function getCapital()
    {
        return $this->capital;
    }
    /**
     * Set continentname
     *
     * @param string $continentname
     * @return Country
     */
    public function setContinentname($continentname)
    {
        $this->continentname = $continentname;
        return $this;
    }
    /**
     * Get continentname
     *
     * @return string
     */
    public function getContinentname()
    {
        return $this->continentname;
    }
    /**
     * Set continent
     *
     * @param string $continent
     * @return Country
     */
    public function setContinent($continent)
    {
        $this->continent = $continent;
        return $this;
    }
    /**
     * Get continent
     *
     * @return string
     */
    public function getContinent()
    {
        return $this->continent;
    }
    /**
     * Set areainsqkm
     *
     * @param string $areainsqkm
     * @return Country
     */
    public function setAreainsqkm($areainsqkm)
    {
        $this->areainsqkm = $areainsqkm;
        return $this;
    }
    /**
     * Get areainsqkm
     *
     * @return string
     */
    public function getAreainsqkm()
    {
        return $this->areainsqkm;
    }
    /**
     * Set isoalpha3
     *
     * @param string $isoalpha3
     * @return Country
     */
    public function setIsoalpha3($isoalpha3)
    {
        $this->isoalpha3 = $isoalpha3;
        return $this;
    }
    /**
     * Get isoalpha3
     *
     * @return string
     */
    public function getIsoalpha3()
    {
        return $this->isoalpha3;
    }
    /**
     * Set geonameid
     *
     * @param integer $geonameid
     * @return Country
     */
    public function setGeonameid($geonameid)
    {
        $this->geonameid = $geonameid;
        return $this;
    }
    /**
     * Get geonameid
     *
     * @return integer
     */
    public function getGeonameid()
    {
        return $this->geonameid;
    }
    /**
     * Add languageCountry
     *
     * @param \App\Entity\LanguageCountry $languageCountry
     * @return Country
     */
    public function addLanguageCountry(\App\Entity\LanguageCountry $languageCountry)
    {
        $this->languageCountry[] = $languageCountry;
        return $this;
    }
    /**
     * Remove languageCountry
     *
     * @param \App\Entity\LanguageCountry $languageCountry
     */
    public function removeLanguageCountry(\App\Entity\LanguageCountry $languageCountry)
    {
        $this->languageCountry->removeElement($languageCountry);
    }
    /**
     * Get languageCountry
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLanguageCountry()
    {
        return $this->languageCountry;
    }
    /**
     * Add state
     *
     * @param \App\Entity\State $state
     * @return Country
     */
    public function addState(\App\Entity\State $state)
    {
        $this->state[] = $state;
        return $this;
    }
    /**
     * Remove state
     *
     * @param \App\Entity\State $state
     */
    public function removeState(\App\Entity\State $state)
    {
        $this->state->removeElement($state);
    }
    /**
     * Get state
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getState()
    {
        return $this->state;
    }
}
