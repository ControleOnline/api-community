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
final class Account
{
    /**
     * @Assert\NotBlank
     */
    public $name;
    /**
     * @Assert\NotBlank
     */
    public $username;
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^\d{2}$/",
     *     match  =true,
     *     message="Your DDD number is not valid"
     * )
     */
    public $ddd;
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^\d{8,9}$/",
     *     match  =true,
     *     message="Your phone number is not valid"
     * )
     */
    public $phone;
    /**
     * @Assert\NotBlank
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     mode    = "html5",
     * )
     */
    public $email;
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
     *     "this.password === this.confirmPassword",
     *     message="Password and Confirm Password must be identical"
     * )
     */
    public $confirmPassword;
}
