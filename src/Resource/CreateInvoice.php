<?php
namespace App\Resource;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Post(uriTemplate: '/invoices', controller: App\Controller\CreateInvoiceAction::class, securityPostDenormalize: 'is_granted(\'create\', object)')], security: 'is_granted(\'IS_AUTHENTICATED\')')]
final class CreateInvoice extends ResourceEntity
{
    /**
     * @var integer
     * @Assert\NotBlank
     */
    public $company;
    /**
     * @var integer
     * @Assert\NotBlank
     */
    public $category;
    /**
     * @var integer
     * @Assert\NotBlank
     */
    public $provider;
    /**
     * @var integer
     * @Assert\Type(type={"integer"})
     * @Assert\PositiveOrZero
     */
    public $paymentMode = null;
    /**
     * @var float
     * @Assert\NotBlank
     * @Assert\Type(type={"float", "integer"})
     * @Assert\Positive
     */
    public $amount;
    /**
     * @var \DateTimeInterface
     * @Assert\NotBlank
     * @Assert\DateTime
     */
    public $dueDate;
    /**
     * @var string
     * @Assert\Type(type={"string"})
     */
    public $description = null;
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Choice({"sale", "purchase"})
     */
    public $orderType = 'purchase';
    public function isRecurrent() : bool
    {
        return $this->paymentMode === 0;
    }
    public function isParceled() : bool
    {
        return is_int($this->paymentMode) && !$this->isRecurrent() && $this->paymentMode > 1;
    }
}
