<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use ControleOnline\Repository\PeopleLinkRepository;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ControleOnline\State\PeopleLinkUpsertProcessor;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'people_link')]
#[ORM\Index(name: 'company_id', columns: ['company'])]
#[ORM\UniqueConstraint(name: 'people_id', columns: ['people_id', 'company'])]
#[ORM\Entity(repositoryClass: PeopleLinkRepository::class)]

#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['people_link:read']],
    denormalizationContext: ['groups' => ['people_link:write']],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new GetCollection(securityPostDenormalize: "is_granted('ROLE_HUMAN')"),
        // Single-item read required for store hydration after write.
        new Get(security: "is_granted('ROLE_HUMAN')"),
        // Create people_link (franchise / My Company Details + My Companies auto-link).
        // Collection previously exposed GET only → POST produced 405 Allow: GET (app-community#642).
        new Post(
            securityPostDenormalize: "is_granted('ROLE_HUMAN')",
            processor: PeopleLinkUpsertProcessor::class,
        ),
        // Update commission fields (comission / minimum_comission / closing_period / payment_term_days) and link metadata.
        // ROLE_SUPER (superadmin) or ROLE_OWNER (owner of franchisor) may write.
        new Put(security: "is_granted('ROLE_SUPER') or is_granted('ROLE_OWNER')"),
        // Unlink collaborator / salesman / franchise (physical remove of people_link only).
        // AuthZ enforced again in PeopleLinkService::preRemove via doctrine listeners / resource security.
        new Delete(security: "is_granted('ROLE_SUPER') or is_granted('ROLE_OWNER')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'company' => 'exact',
    'people' => 'exact',
    // linkType + enable: handled only in PeopleLinkService (SET + OR semantics).
])]
class PeopleLink
{
    public const HUMAN_LINK = [
        'employee',
        'owner',
        'director',
        'manager',
        'salesman',
        'after-sales',
        'courier',
    ];

    public const COMMERCIAL_LINK = ['client', 'provider', 'franchisee', 'filial'];

    public const PANEL_LINK = ['client', 'provider', 'franchisee', 'filial'];

    public const ADMIN_LINK = ['owner', 'director', 'manager'];

    public const API_ROLE_MAP = [
        'employee' => 'ROLE_EMPLOYEE',
        'owner' => 'ROLE_OWNER',
        'director' => 'ROLE_DIRECTOR',
        'manager' => 'ROLE_MANAGER',
        'salesman' => 'ROLE_SALESMAN',
        'after-sales' => 'ROLE_AFTER_SALES',
        'courier' => 'ROLE_COURIER',
        'client' => 'ROLE_CLIENT',
        'provider' => 'ROLE_PROVIDER',
        'franchisee' => 'ROLE_FRANCHISEE',
        'filial' => 'ROLE_FRANCHISEE',
    ];

    public const EMPLOYEE_LINK = self::HUMAN_LINK;
    public const MANAGER_LINK  = self::ADMIN_LINK;

    public const CLOSING_PERIODS = ['daily', 'weekly', 'biweekly', 'monthly'];

    public const DEFAULT_CLOSING_PERIOD = 'monthly';

    public const DEFAULT_PAYMENT_TERM_DAYS = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people_link:read', 'people_link:write'])]
    private $id;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'company')]
    #[Groups(['people_link:read', 'people_link:write'])]
    private $company;

    /**
     * @var People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'link')]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $people;

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $enable = 1;


    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'link_type', type: 'string', columnDefinition: "SET('prospect','employee','client','provider','franchisee','filial','professor','family','salesman','owner','sellers-client','director','manager','admin','courier') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci", nullable: true)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $linkType;


    /**
     * @var float
     */
    #[ORM\Column(name: 'comission', type: 'float', nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $comission = 0;


    /**
     * @var float
     */
    #[ORM\Column(name: 'minimum_comission', type: 'float', nullable: false)]
    #[Groups(['people_link:read', 'people_link:write'])]

    private $minimum_comission = 0;


    /**
     * Closing period for commission/royalty invoices aggregation.
     * Allowed: daily, weekly, biweekly, monthly.
     *
     * @var string
     */
    #[ORM\Column(name: 'closing_period', type: 'string', length: 20, nullable: false, options: ['default' => 'monthly'])]
    #[Groups(['people_link:read', 'people_link:write'])]
    private $closing_period = self::DEFAULT_CLOSING_PERIOD;

    /**
     * Days after closing period end until invoice due date.
     *
     * @var int
     */
    #[ORM\Column(name: 'payment_term_days', type: 'integer', nullable: false, options: ['default' => 0])]
    #[Groups(['people_link:read', 'people_link:write'])]
    private $payment_term_days = self::DEFAULT_PAYMENT_TERM_DAYS;


    public function getId()
    {
        return $this->id;
    }

    public function setCompany(People $company = null)
    {
        $this->company = $company;

        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setPeople(People $people = null)
    {
        $this->people = $people;

        return $this;
    }

    public function getPeople()
    {
        return $this->people;
    }

    public function getEnabled()
    {
        return $this->enable;
    }

    public function setEnabled($enable)
    {
        $this->enable = $enable ?: 0;

        return $this;
    }


    /**
     * Set minimum_comission
     *
     * @param float $minimum_comission
     * @return PeopleSalesman
     */
    public function setMinimumComission($minimum_comission): self
    {
        $this->minimum_comission = $minimum_comission;

        return $this;
    }

    /**
     * Get minimum_comission
     *
     * @return float
     */
    public function getMinimumComission(): float
    {
        return $this->minimum_comission;
    }


    /**
     * Set comission
     *
     * @param float $comission
     * @return PeopleSalesman
     */
    public function setComission($comission): self
    {
        $this->comission = $comission;

        return $this;
    }

    /**
     * Get comission
     *
     * @return float
     */
    public function getComission(): float
    {
        return (float) ($this->comission ?? 0);
    }

    /**
     * Get the value of linkType
     */
    public function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * Set the value of linkType
     */
    public function setLinkType($linkType): self
    {
        $this->linkType = $linkType;

        return $this;
    }


    public function getClosingPeriod(): string
    {
        return (string) ($this->closing_period ?: self::DEFAULT_CLOSING_PERIOD);
    }

    public function setClosingPeriod(?string $closing_period): self
    {
        $value = strtolower(trim((string) ($closing_period ?? '')));
        if ($value === '' || !in_array($value, self::CLOSING_PERIODS, true)) {
            $value = self::DEFAULT_CLOSING_PERIOD;
        }
        $this->closing_period = $value;

        return $this;
    }

    public function getPaymentTermDays(): int
    {
        return (int) ($this->payment_term_days ?? self::DEFAULT_PAYMENT_TERM_DAYS);
    }

    public function setPaymentTermDays($payment_term_days): self
    {
        $days = (int) $payment_term_days;
        if ($days < 0) {
            $days = 0;
        }
        $this->payment_term_days = $days;

        return $this;
    }

    public static function toRole(string $linkType): ?string
    {
        $normalizedLinkType = trim(strtolower($linkType));

        return self::API_ROLE_MAP[$normalizedLinkType] ?? null;
    }
}
