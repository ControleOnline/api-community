<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Post(status: 202)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', messenger: true)]
final class RecoveryAccess
{
    /**
     * @Assert\NotBlank
     */
    public $hash;
    /**
     * @Assert\NotBlank
     */
    public $lost;
    /**
     * @Assert\NotBlank
     * @Assert\Length(
     *    min        = 6,
     *    minMessage = "Your password name must be at least {{ limit }} characters long",
     * )
     * @Assert\NotCompromisedPassword
     */
    public $password;
    /**
     * @Assert\NotBlank
     * @Assert\Expression(
     *     "this.password === this.confirm",
     *     message="Password and Confirm Password must be identical"
     * )
     */
    public $confirm;
}
