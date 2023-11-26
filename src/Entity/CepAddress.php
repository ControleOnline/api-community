<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;

use DoctrineExtensions\Query\Mysql\Lpad;
use Symfony\Component\Serializer\Annotation\Groups;

/** 
 * @ApiResource(
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "jsonhal", "csv"={"text/csv"}},
 *          "access_control"="is_granted('ROLE_CLIENT')" 
 *     },  
 *     collectionOperations={},
 *     itemOperations      ={
 *        "get"={
 *            "access_control"="is_granted('ROLE_CLIENT')",
 *            "method"        ="GET",
 *            "path"          ="/cep_address/{id}",
 *            "requirements"  ={"id"="^\d{8}$"},
 *        },
 *     },
 * )
 */
final class CepAddress
{
    /**
     * @ApiProperty(identifier=true)
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $country;

    /**
     * @var string
     */
    public $state;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $district;

    /**
     * @var string
     */
    public $street;

    /**
     * @var string
     */
    public $provider;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
