<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\OrderLogisticSurveysRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass=OrderLogisticSurveysRepository::class)
 * @ORM\Table (name="order_logistic_surveys")
 */
#[ApiResource(operations: [
    new Get(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/order_logistic_surveys/findsurveyor/{id}', defaults: ['_api_receive' => false]),
    // new Get(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/tasks_surveys/{default_company_id}/findpeopleprofessional', requirements: ['default_company_id' => '^\\d+$'], controller: \App\Controller\OrderLogisticSurveysController::class, defaults: ['_api_receive' => false]), 
    // new Get(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/tasks_surveys/{survey_id}/{token_url}/getonesurvey', requirements: ['survey_id' => '^\\d+$', 'token_url' => '^[a-zA-Z0-9]+'], controller: \App\Controller\OrderLogisticSurveysController::class, defaults: ['_api_receive' => false]), 
    // new Put(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/tasks_surveys/{survey_id}/{token_url}/survey', requirements: ['survey_id' => '^\\d+$', 'token_url' => '^[a-zA-Z0-9]+'], controller: \App\Controller\OrderLogisticSurveysController::class, defaults: ['_api_receive' => false]), 
    // new Post(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/tasks_surveys/{survey_id}/{token_url}/filesfiles', requirements: ['survey_id' => '^\\d+$', 'token_url' => '^[a-zA-Z0-9]+'], controller: \App\Controller\OrderLogisticSurveysController::class, defaults: ['_api_receive' => false]), 
    // new Get(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/tasks_surveys/{survey_id}/{file_id}/viewphoto/{type}', requirements: ['survey_id' => '^\\d+$', 'file_id' => '^\\d+$', 'type' => '(realsize|thumb)'], controller: \App\Controller\OrderLogisticSurveysController::class, defaults: ['_api_receive' => false]), 
    // new Get(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/tasks_surveys/{survey_id}/{token_url}/getphotogallery', requirements: ['survey_id' => '^\\d+$', 'token_url' => '^[a-zA-Z0-9]+'], controller: \App\Controller\OrderLogisticSurveysController::class, defaults: ['_api_receive' => false]), 
    // new Put(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/tasks_surveys/{survey_id}/surveys', requirements: ['survey_id' => '^\\d+$'], controller: \App\Controller\OrderLogisticSurveysController::class, defaults: ['_api_receive' => false]), 
], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['logistic_surveys_read']],     denormalizationContext: ['groups' => ['logistic_surveys_write']])]
class OrderLogisticSurveys
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"logistic_read"})
     */
    private $id;
    /**
     * @ORM\Column(type="datetime", nullable=false, columnDefinition="DATETIME")
     */
    private $created_at;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;
    /**
     * @ORM\Column(type="string", length=30)
     */
    private $type_survey;
    /**
     * @ORM\Column(type="string", length=500)
     */
    private $other_informations;
    /**
     * @ORM\Column(type="string", length=10)
     */
    private $belongings_removed;
    /**
     * @var \App\Entity\OrderLogistic
     *
     * @ORM\OneToOne(targetEntity="App\Entity\OrderLogistic", inversedBy="orderLogisticSurvey")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_logistic_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $order_logistic_id;
    /**
     * @ORM\Column(type="integer")
     */
    private $professional_id;
    /**
     * @ORM\Column(type="integer")
     */
    private $address_id;
    /**
     * @ORM\Column(type="integer")
     */
    private $surveyor_id;
    /**
     * @ORM\Column(type="integer")
     */
    private $vehicle_km;
    /**
     * @ORM\Column(type="string", length=10)
     */
    private $status;
    /**
     * @ORM\Column(type="string", length=500)
     */
    private $comments;
    /**
     * @ORM\Column(type="string", length=7)
     * @Groups({"logistic_read"})
     */
    private $token_url;
    public function __construct()
    {
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
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updated_at;
    }
    public function setUpdatedAt(DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
    public function getTypeSurvey(): ?string
    {
        return $this->type_survey;
    }
    public function setTypeSurvey(string $type_survey): self
    {
        $this->type_survey = $type_survey;
        return $this;
    }
    public function getOrderLogistcId()
    {
        return $this->order_logistic_id;
    }
    public function setOrderLogistcId($order_logistic_id): self
    {
        $this->order_logistic_id = $order_logistic_id;

        return $this;
    }
    public function getProfessionalId(): ?int
    {
        return $this->professional_id;
    }
    public function setProfessionalId(int $professional_id): self
    {
        $this->professional_id = $professional_id;
        return $this;
    }
    public function getSurveyorId(): ?int
    {
        return $this->surveyor_id;
    }
    public function setSurveyorId(int $surveyor_id): self
    {
        $this->surveyor_id = $surveyor_id;
        return $this;
    }
    public function getOtherInformations(): ?string
    {
        return $this->other_informations;
    }
    public function setOtherInformations(string $other_informations): self
    {
        $this->other_informations = $other_informations;
        return $this;
    }
    public function getBelongingsRemoved(): ?string
    {
        return $this->belongings_removed;
    }
    public function setBelongingsRemoved(string $belongings_removed): self
    {
        $this->belongings_removed = $belongings_removed;
        return $this;
    }
    public function getVehicleKm(): ?int
    {
        return $this->vehicle_km;
    }
    public function setVehicleKm(int $vehicle_km): self
    {
        $this->vehicle_km = $vehicle_km;
        return $this;
    }
    public function getAddressId(): ?int
    {
        return $this->address_id;
    }
    public function setAddressId(int $address_id): self
    {
        $this->address_id = $address_id;
        return $this;
    }
    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
    public function getComments(): ?string
    {
        return $this->comments;
    }
    public function setComments(string $comments): self
    {
        $this->comments = $comments;
        return $this;
    }
    public function getTokenUrl(): ?string
    {
        return $this->token_url;
    }
    public function setTokenUrl(string $token_url): self
    {
        $this->token_url = $token_url;
        return $this;
    }
}
