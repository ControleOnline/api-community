<?php
namespace App\Resource;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Delete(uriTemplate: '/invoices/{id}', requirements: ['id' => '^\\d+$'], 
controller: ControleOnline\Controller\DeleteInvoiceAction::class, 
security: 'is_granted(\'delete\', object)')], security: 'is_granted(\'IS_AUTHENTICATED_FULLY\')')]
final class DeleteInvoice extends ResourceEntity
{
    /**
     */
    #[ApiProperty(identifier: true)]
    public $id;
    /**
     * @var integer
     * @Assert\NotBlank
     */
    public $company;
}
