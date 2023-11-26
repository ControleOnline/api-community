<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\FileRepository")
 * @ORM\Table (name="file", uniqueConstraints={@ORM\UniqueConstraint (name="url", columns={"url"}), @ORM\UniqueConstraint(name="path", columns={"path"})})
 */
#[ApiResource(operations: [new GetCollection(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/configs/app-theme', controller: 'App\\Controller\\GetAppThemeAction')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class Theme
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $url;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $path;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\People", mappedBy="file")
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
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    public function getUrl()
    {
        return $this->url;
    }
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    public function getPath()
    {
        return $this->path;
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
}
