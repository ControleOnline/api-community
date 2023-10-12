<?php
namespace App\Resource;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as MyAssert;
/**
 */
#[ApiResource(operations: [new Post(uriTemplate: '/users', controller: App\Controller\CreateUserAction::class, securityPostDenormalize: 'is_granted(\'create\', object)')], security: 'is_granted(\'IS_AUTHENTICATED\')')]
final class CreateUser extends ResourceEntity
{
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(
     *  min       = 3,
     *  max       = 150,
     *  minMessage= "Your first name must be at least {{ limit }} characters long",
     *  maxMessage= "Your first name cannot be longer than {{ limit }} characters"
     * )
     */
    public $firstName = '';
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(
     *  min       = 3,
     *  max       = 150,
     *  minMessage= "Your last name must be at least {{ limit }} characters long",
     *  maxMessage= "Your last name cannot be longer than {{ limit }} characters"
     * )
     */
    public $lastName = '';
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Email
     */
    public $email = '';
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(
     *  min       = 3,
     *  max       = 150,
     *  minMessage= "Your username must be at least {{ limit }} characters long",
     *  maxMessage= "Your username cannot be longer than {{ limit }} characters"
     * )
     */
    public $userName = '';
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\NotCompromisedPassword
     */
    public $password = '';
    /**
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Choice({"student", "professional", "salesman", "admin"})
     */
    public $userRole = '';
    /**
     * @Assert\NotBlank
     * @Assert\Type("integer")
     * @Assert\Positive
     */
    public $company = null;
}
