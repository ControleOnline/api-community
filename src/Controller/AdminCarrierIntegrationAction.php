<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Carrier;
use App\Entity\People;
use App\Entity\CarrierIntegration;

class AdminCarrierIntegrationAction
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

  public function __invoke(Carrier $data, Request $request): JsonResponse
  {
    try {
      $methods = [
        Request::METHOD_GET => 'getIntegration',
        Request::METHOD_PUT => 'updateIntegration',
      ];

      $payload   = json_decode($request->getContent(), true);
      $operation = $methods[$request->getMethod()];

      if ($request->getMethod() != Request::METHOD_GET && empty($payload)) {
        throw new \InvalidArgumentException('Payload is missing');
      }

      return new JsonResponse([
        'response' => [
          'data'    => $this->$operation($data, $payload),
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'response' => [
          'data'    => null,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }

  private function getIntegration(Carrier $carrier): ?array
  {
    $carrierIntegration = $this->manager->getRepository(CarrierIntegration::class)
      ->findOneBy(['carrier' => $carrier]);

    if ($carrierIntegration === null) {
      return null;
    }

    return [
      'type'     => $carrierIntegration->getIntegrationType(),
      'user'     => $carrierIntegration->getIntegrationUser(),
      'password' => $carrierIntegration->getIntegrationPassword(),
    ];
  }

  private function updateIntegration(Carrier $carrier, array $payload): ?array
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      // do update
      $carrierIntegration = $this->manager->getRepository(CarrierIntegration::class)
        ->findOneBy(['carrier' => $carrier]);

      if ($carrierIntegration === null) {
        $carrierIntegration = new CarrierIntegration();
      }

      $carrierIntegration->setCarrier            ($this->manager->getRepository(People::class)->find($carrier->getId()));
      $carrierIntegration->setIntegrationType    ($payload['type']);
      $carrierIntegration->setIntegrationUser    ($payload['user']);
      $carrierIntegration->setIntegrationPassword($payload['password']);

      $this->manager->persist($carrierIntegration);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return [
          'id' => $carrier->getId()
      ];
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
          $this->manager->getConnection()->rollBack();

      throw new \InvalidArgumentException($e->getMessage());
    }
  }
}
