<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\DeliveryTaxGroup;

class UpdateDeliveryGroupTaxPriceAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
      $this->manager = $entityManager;
    }

    public function __invoke(DeliveryTaxGroup $data, Request $request): DeliveryTaxGroup
    {
      $payload = json_decode($request->getContent(), true);

      if (!isset($payload['increase']) || !is_numeric($payload['increase'])) {
        throw new \Exception('Tax increase value is not defined');
      }

      $increase    = (float) (1 + ($payload['increase'] / 100));
      $taxName     = isset($payload['taxName']) ? $payload['taxName'] : null;
      $origin      = isset($payload['origin']) ? $payload['origin'] : null;
      $destination = isset($payload['destination']) ? $payload['destination'] : null;

      $this->manager->getRepository(DeliveryTaxGroup::class)
        ->updateAllTaxPrices($data->getId(), $increase, $taxName, $origin, $destination);

      return $data;
    }
}
