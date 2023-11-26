<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as MyAssert;
/**
 */
#[ApiResource(operations: [new Post(status: 202), new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')', uriTemplate: '/company/get_cnpj', controller: \App\Controller\CompanyCnpjAction::class)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')', messenger: true)]
final class Company
{
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    public $name;
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    public $alias;
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^(\d{11}|\d{14})$/",
     *     match  =true,
     *     message="Your document is not valid (does not have a CNPJ or CPF format)"
     * )
     */
    public $document;
    /**
     * @Assert\NotBlank
     * @MyAssert\FullAddress
     */
    public $address;
    /**
     * @MyAssert\QuoteAddress
     */
    public $origin;
}
