<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Entity\OrderTracking;
use Doctrine\ORM\Mapping as ORM;
/**
 * TrackBack
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="order_tracking")
 * @ORM\Entity (repositoryClass="App\Repository\OrderTrackingRepository")
 */
#[ApiResource(operations: [new GetCollection(uriTemplate: 'track/back/{orderId}/{document}', controller: \App\Controller\GetTrackBackAction::class, openapiContext: [])], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')')]
class TrackBack extends OrderTracking
{
}