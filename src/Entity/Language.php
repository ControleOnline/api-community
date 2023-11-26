<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LanguageRepository")
 * @ORM\Table(name="language", uniqueConstraints={@ORM\UniqueConstraint(name="language", columns={"language"})})
 * @ORM\EntityListeners({App\Listener\LogListener::class}) 
 */
class Language
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10, nullable=false)
     */
    private $language;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $locked;

    /**
     *
     * @ORM\OneToMany(targetEntity="App\Entity\People", mappedBy="language")
     */
    private $people;

    public function __construct()
    {
        $this->people = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addPeople(People $people)
    {
        $this->people[] = $people;

        return $this;
    }

    public function removePeople(People $people)
    {
        $this->people->removeElement($people);
    }

    public function getPeople()
    {
        return $this->people;
    }

    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked()
    {
        return $this->locked;
    }
}
