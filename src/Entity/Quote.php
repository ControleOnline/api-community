<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as MyAssert;
/**
 */
#[ApiResource(operations: [new Post(status: 202)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', messenger: true)]
final class Quote
{
    /**
     * @Assert\NotBlank
     * @MyAssert\QuoteAddress
     */
    public $origin;
    /**
     * @Assert\NotBlank
     * @MyAssert\QuoteAddress
     */
    public $destination;
    /**
     * @Assert\Type("string")
     */
    public $groupTable;
    /**
     * @Assert\Type("string")
     */
    public $groupCode;
    /**
     * @Assert\Type("boolean")
     */
    public $noRetrieve = true;
    /**
     * @Assert\NotBlank
     * @Assert\Type("numeric")
     * @Assert\GreaterThan(0)
     */
    public $productTotalPrice;
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    public $productType;
    /**
     * @Assert\NotBlank
     * @MyAssert\QuotePackage
     */
    public $packages;
    /**
     * @Assert\Type("string")
     */
    public $domain;
    /**
     * @Assert\Type("string")
     */
    public $app;
    /**
     * @Assert\Type("string")
     */
    public $quoteType;
    /**
     * @Assert\Type("string")
     */
    public $routeType;
    /**
     * @Assert\Type("numeric")
     */
    public $mainOrder;
    /**
     * @Assert\Type("numeric")
     */
    public $myCompany;
    /**
     * @Assert\Type("numeric")
     */
    public $selectedCompany;
    /**
     * @Assert\Type("array")
     */
    public $denyCarriers;
    /**
     * @MyAssert\ContactData
     */
    public $contact;
    /**
     * @MyAssert\QuotePeople
     */
    public $pickup = null;
    /**
     * @MyAssert\QuotePeople
     */
    public $delivery = null;
}
