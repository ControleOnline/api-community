<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
/**
 */
#[ApiResource(operations: [new GetCollection(uriTemplate: '/tray/quote/{origin}', controller: \App\Controller\TrayRatesAction::class, openapiContext: []), new GetCollection(uriTemplate: '/tray/quote', controller: \App\Controller\TrayRatesAction::class)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', messenger: true)]
final class Tray
{
    /**
     */
    #[ApiProperty(identifier: true)]
    public $id;
}
