<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\SchoolClassStatusRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass=SchoolClassStatusRepository::class)
 */
#[ApiResource(operations: [new Get(), new GetCollection()], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')')]
class SchoolClassStatus
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"school_class:item:get"})
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $lessonStatus;
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"school_class:item:get", "school_class:item:put"})
     */
    private $lessonRealStatus;
    /**
     * @ORM\Column(type="string", length=7)
     * @Groups({"school_class:item:get"})
     */
    private $lessonColor;
    /**
     * @ORM\Column(type="boolean")
     * @Groups({"school_class:item:get"})
     */
    private $generatePayment;
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getLessonStatus() : ?string
    {
        return $this->lessonStatus;
    }
    public function setLessonStatus(string $lessonStatus) : self
    {
        $this->lessonStatus = $lessonStatus;
        return $this;
    }
    public function getLessonRealStatus() : ?string
    {
        return $this->lessonRealStatus;
    }
    public function setLessonRealStatus(string $lessonRealStatus) : self
    {
        $this->lessonRealStatus = $lessonRealStatus;
        return $this;
    }
    public function getLessonColor() : ?string
    {
        return $this->lessonColor;
    }
    public function setLessonColor(string $lessonColor) : self
    {
        $this->lessonColor = $lessonColor;
        return $this;
    }
    public function getGeneratePayment() : ?bool
    {
        return $this->generatePayment;
    }
    public function setGeneratePayment(bool $generatePayment) : self
    {
        $this->generatePayment = $generatePayment;
        return $this;
    }
}
