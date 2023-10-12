<?php
namespace App\Resource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Get(uriTemplate: '/configs/{id}', requirements: ['id' => '^\\d+$'], controller: App\Controller\GetSettingAction::class, security: 'is_granted(\'read\', object)')], security: 'is_granted(\'IS_AUTHENTICATED_FULLY\')')]
final class GetSetting extends ResourceEntity
{
    /**
     */
    #[ApiProperty(identifier: true)]
    public $id;
    /**
     * @var array
     * @Assert\Type(type={"array"})
     */
    public $integrations = null;
}
