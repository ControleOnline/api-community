<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Post(status: 202)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'IS_AUTHENTICATED\')', messenger: true)]
final class OrderNf
{
    /**
     * @Assert\NotBlank
     */
    public $key;
}
