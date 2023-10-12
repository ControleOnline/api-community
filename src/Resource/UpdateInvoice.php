<?php
namespace App\Resource;

use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Put(uriTemplate: '/invoicess/{id}', requirements: ['id' => '^\\d+$'], controller: App\Controller\UpdateInvoiceAction::class, security: 'is_granted(\'edit\', object)')], security: 'is_granted(\'IS_AUTHENTICATED_FULLY\')')]
final class UpdateInvoice extends ResourceEntity
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
    /**
     * @var integer
     * @Assert\Type(type={"integer"})
     * @Assert\Positive
     */
    public $category = null;
    /**
     * @var integer
     * @Assert\Type(type={"integer"})
     * @Assert\Positive
     */
    public $provider = null;
    /**
     * @var float
     * @Assert\Type(type={"float", "integer"})
     * @Assert\Positive
     */
    public $amount = null;
    /**
     * @var \DateTimeInterface
     * @Assert\DateTime
     */
    public $dueDate = null;
    /**
     * @var string
     * @Assert\Type(type={"string"})
     */
    public $description = null;
    /**
     * @var string
     * @Assert\Type(type={"string"})
     */
    public $status = null;
}
