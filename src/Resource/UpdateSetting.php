<?php
namespace App\Resource;

use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Put(uriTemplate: '/configs/{id}', requirements: ['id' => '^\\d+$'], controller: App\Controller\UpdateSettingAction::class, security: 'is_granted(\'edit\', object)')], security: 'is_granted(\'IS_AUTHENTICATED_FULLY\')')]
final class UpdateSetting extends ResourceEntity
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
