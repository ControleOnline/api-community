<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\GetFileDataAction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * File
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="App\Repository\FileRepository")
 * @ORM\Table (name="files", uniqueConstraints={@ORM\UniqueConstraint (name="url", columns={"url"}), @ORM\UniqueConstraint(name="path", columns={"path"})})
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Get(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/files/download/{id}',                        
            controller: GetFileDataAction::class
        ),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    
    normalizationContext: ['groups' => ['file_read']],
    denormalizationContext: ['groups' => ['file_write']]
)]
class File
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"product_file_read","lesson_upload_file:post", "people_read", "task_interaction_read","hardware_read"})
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"product_file_read","people_read", "lesson:read", "task_interaction_read","hardware_read"})
     */
    private $url;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"lesson_upload_file:post", "lesson:read"})
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
