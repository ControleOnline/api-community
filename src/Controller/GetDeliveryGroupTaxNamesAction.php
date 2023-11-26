<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\DeliveryTaxGroup;

class GetDeliveryGroupTaxNamesAction
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

    public function __invoke(DeliveryTaxGroup $data, Request $request): JsonResponse
    {
      try {

        $repository = $this->manager->getRepository(DeliveryTaxGroup::class);
        $taxNames   = $repository->getAllTaxNamesByGroup($data->getId());

        $output = [
          'response' => [
            'data'    => [
              'members' => $taxNames,
              'total'   => count($taxNames)
            ],
            'success' => true,
          ],
        ];

        return new JsonResponse($output, 200);

      } catch (\Exception $e) {

        $output = [
          'response' => [
            'data'    => [],
            'error'   => $e->getMessage(),
            'success' => false,
          ],
        ];

        return new JsonResponse($output, $e->getCode() >= 400 ? $e->getCode() : 500);
      }
    }
}
