<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Post(status: 202, uriTemplate: '/dashboard'), new Post(status: 202, uriTemplate: '/dashboard/financial')], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')', messenger: true)]
final class Dashboard
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/",
     *     match  =true,
     *     message="Date from is not valid"
     * )
     */
    public $fromDate;
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/",
     *     match  =true,
     *     message="Date to is not valid"
     * )
     */
    public $toDate;
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^\d+$/",
     *     match  =true,
     *     message="Provider Id is not valid"
     * )
     */
    public $providerId;
}
