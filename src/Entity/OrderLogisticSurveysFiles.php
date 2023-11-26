<?php

namespace App\Entity;

use App\Repository\OrderLogisticSurveysFilesRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderLogisticSurveysFilesRepository::class)
 * @ORM\EntityListeners({App\Listener\LogListener::class})
 * @ORM\Table (name="order_logistic_surveys_files")
 */
class OrderLogisticSurveysFiles
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $created_at;

    /**
     * @ORM\Column(type="integer")
     */
    private $order_logistic_surveys_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $region;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $breakdown;

    public function __construct(){
        $this->created_at = new DateTime('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getOrderLogisticSurveysId()
    {
        return $this->order_logistic_surveys_id;
    }

    public function setOrderLogisticSurveysId($order_logistic_surveys_id): self
    {
        $this->order_logistic_surveys_id = $order_logistic_surveys_id;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->filename;
    }

    public function setFileName(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getBreakdown(): ?string
    {
        return $this->breakdown;
    }

    public function setBreakdown(string $breakdown): self
    {
        $this->breakdown = $breakdown;
        return $this;
    }
}
