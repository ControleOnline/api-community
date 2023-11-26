<?php

namespace App\Resource;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
/**
 */
#[ApiResource(operations: [new Get(uriTemplate: '/accept-order-payer/{id}', controller: App\Controller\GetAcceptOrderPayerAction::class), 
    new Post(uriTemplate: '/accept/order/{id}/payer', controller: App\Controller\SaveAcceptOrderPayerAction::class)])]
final class AcceptOrder extends ResourceEntity
{
    /**
     */
    #[ApiProperty(identifier: true)]
    public $id;
}
