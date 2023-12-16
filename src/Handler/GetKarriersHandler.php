<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Karrier;
use ControleOnline\Repository\KarrierRepository;

class GetKarriersHandler implements MessageHandlerInterface
{
    private $repository;

    public function __construct(KarrierRepository $carrierRepository)
    {
      $this->repository = $carrierRepository;
    }

    public function __invoke(Karrier $carrier)
    {
      $companyId = isset($carrier->searchBy['companyId']) ? $carrier->searchBy['companyId'] : 3;
      $carriers  = $this->repository->getCompanyCarriersGroupsAndTaxes($companyId, $carrier->searchBy);

      if ($carriers === null)
        return new JsonResponse([
          'response' => [
            'data'    => [],
            'count'   => 0,
            'success' => true,
          ],
        ]);

      return new JsonResponse([
        'response' => [
          'data'    => [
            'carriers'     => $carriers,
            'regions'      => $this->repository->getCompanyCarriersCities($companyId),
            'restrictions' => $this->repository->getCompanyCarriersRestrictionMaterial($companyId),
          ],
          'count'   => null,
          'success' => true,
        ],
      ]);
    }
}
