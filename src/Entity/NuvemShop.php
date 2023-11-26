<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
/**
 */
#[ApiResource(operations: [new GetCollection(uriTemplate: '/nuvem_shop/install/{code}', security: 'is_granted(\'ROLE_CLIENT\')', controller: \App\Controller\NuvemshopInstallAction::class), new Post(uriTemplate: '/nuvem_shop/rates', controller: \App\Controller\NuvemshopRatesAction::class), new Post(uriTemplate: '/nuvem_shop/order-created', controller: \App\Controller\NuvemshopOrderCreatedAction::class)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', messenger: true)]
final class NuvemShop
{
    /**
     */
    #[ApiProperty(identifier: true)]
    public $id;
}
