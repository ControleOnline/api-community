<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Carrier;
use App\Entity\CarrierIntegration;
use App\Entity\People;

use App\Library\Rates\RateServiceFactory;

use App\Library\Rates\Model\Quotation;
use App\Library\Rates\Model\Product;

class GetRemoteCarrierRatesAction
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

      $payload   = json_decode($request->getContent(), true);
      $access    = $this->manager->getRepository(CarrierIntegration::class)
        ->findOneBy([
          'carrier' => $data
        ]);

      if ($access === null) {
        throw new \Exception('Remote service access data is missing');
      }

      $service = RateServiceFactory::create(
        $access->getIntegrationType(),
        $access->getIntegrationUser(),
        $access->getIntegrationPassword()
      );

      $quotation = new Quotation();
      $quotation->setOrigin     ($payload['origin']);
      $quotation->setDestination($payload['destination']);
      $quotation->setTotalPrice ($payload['totalPrice']);

      if (isset($payload['products']) && is_array($payload['products'])) {
        foreach ($payload['products'] as $product) {
          $quotation->addProduct(
            (new Product)
              ->setWidth   ($product['width'])
              ->setHeight  ($product['height'])
              ->setDepth   ($product['depth'])
              ->setWeight  ($product['weight'])
              ->setQuantity($product['quantity'])
          );
        }
      }

      $rates  = [];
      $result = $service->getRates($quotation);

      if (!empty($result)) {
        foreach ($result as $carrierRate) {
          $rates[] = [
            'carrier'  => $carrierRate->getCarrier (),
            'table'    => $carrierRate->getTable   (),
            'code'     => $carrierRate->getCode    (),
            'price'    => $carrierRate->getPrice   (),
            'deadline' => $carrierRate->getDeadline(),
            'error'    => $carrierRate->getError   (),
          ];
        }
      }

      return new JsonResponse([
        'response' => [
          'data'    => $rates,
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
}
