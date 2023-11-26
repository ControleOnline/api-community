<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\SchoolClassFilesRepository;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass=SchoolClassFilesRepository::class)
 */
#[ApiResource(formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class SchoolClassFiles
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity=SchoolClass::class, inversedBy="schoolClassFiles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $schoolClass;
    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $file;
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getSchoolClass() : ?SchoolClass
    {
        return $this->schoolClass;
    }
    public function setSchoolClass(?SchoolClass $schoolClass) : self
    {
        $this->schoolClass = $schoolClass;
        return $this;
    }
    public function getFile() : ?File
    {
        return $this->file;
    }
    public function setFile(File $file) : self
    {
        $this->file = $file;
        return $this;
    }
}
