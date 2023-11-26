<?php

namespace App\Resource;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\GetDashboardsAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/dashboards',
            controller: GetDashboardsAction::class,
            securityPostDenormalize: 'is_granted(\'read\', object)'
        )
    ],
    security: 'is_granted(\'IS_AUTHENTICATED_FULLY\')'
)]
final class GetDashboard extends ResourceEntity
{
    /**
     * @var \DateTimeInterface
     * @Assert\NotBlank
     * @Assert\Date
     */
    public $fromDate;
    /**
     * @var \DateTimeInterface
     * @Assert\NotBlank
     * @Assert\Date
     */
    public $toDate;
    /**
     * @var integer
     * @Assert\NotBlank
     */
    public $company;
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Choice({"financial", "main"})
     */
    public $viewType = 'financial';
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    public $query = null;
    public function isMainView(): bool
    {
        return $this->viewType === 'main';
    }
}
