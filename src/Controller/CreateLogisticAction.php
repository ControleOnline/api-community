<?php

namespace App\Controller;

use ControleOnline\Entity\OrderLogistic;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\People;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Repository\OrderLogisticRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Datetime;
use Exception;
use LDAP\Result;

class CreateLogisticAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Request
   *
   * @var Request
   */
  private $request  = null;



  public function __construct(EntityManagerInterface $manager)
  {
    $this->manager     = $manager;
  }

  public function __invoke(Request $request): JsonResponse
  {
    $this->request = $request;

    try {

      $payload = json_decode($this->request->getContent(), true);
      
      // $this->manager->getConnection()->beginTransaction();
      // $orderLogistic = new OrderLogistic();
      // $orderLogistic->setShippingDate(new DateTime($payload['shippingDate']));
      // $orderLogistic->setArrivalDate(new DateTime($payload['arrivalDate']));
      // $orderLogistic->setOriginType($payload['originType']);
      // $orderLogistic->setOriginRegion($payload['originRegion']);
      // $orderLogistic->setOriginState($payload['originState']);
      // $orderLogistic->setOriginCity($payload['originCity']);
      // $orderLogistic->setOriginAdress($payload['originAddress']);
      // $orderLogistic->setValue($payload['value']);
      
      // $status = $this->manager->getRepository(Status::class)->find($payload['status']);
      // $orderLogistic->setStatus($status);
      
      // $provider = $this->manager->getRepository(People::class)->find($payload['provider']);
      // $orderLogistic->setStatus($provider);
      
      // $order = $this->manager->getRepository(SalesOrder::class)->find($payload['orderId']);
      // $orderLogistic->setStatus($order);
      
      // $this->manager->persist($orderLogistic);
      // $this->manager->flush();
      // $this->manager->getConnection()->commit();
      
      $sql = "INSERT INTO `order_logistic`(`provider_id`, `order_id`, `status_id`, `shipping_date`, `arrival_date`, `origin_type`,
      `origin_region`, `origin_state`, `origin_city`, `origin_adress`, `value`) 
      VALUES (:providerId , :orderId, :statusId, :shippingDate, :arrivalDate, :originType, :originRegion, :originState, :originCity, :originAddress, :orderValue);
      ";

      $provider = $payload['provider'] === null ? null : $payload['provider'];
      $order = $payload['orderId'] === null ? null : $payload['orderId'];
      $status = $payload['status'] === null ? null : $payload['status'];
      $shipping = $payload['shippingDate'] === null ? null : $payload['shippingDate'];
      $arrival = $payload['arrivalDate'] === null ? null : $payload['arrivalDate'];
      $originType = $payload['originType'] === null ? null : $payload['originType'];
      $originRegion = $payload['originRegion'] === null ? null : $payload['originRegion'];
      $originState = $payload['originState'] === null ? null : $payload['originState'];
      $originCity = $payload['originCity'] === null ? null : $payload['originCity'];
      $originAddress = $payload['originAddress'] === null ? null : $payload['originAddress'];
      $value = $payload['value'] === null ? null : $payload['value'];
      // dd($status);
      
      $params = [];
      // parametros obrigatorios
      if ($order && $status && $value) {
        $params['orderId'] = $order;
        $params['statusId'] = $status;
        $params['orderValue'] = $value;
      } else {
        throw new Exception();
      }

      $params['providerId'] = $provider;
      
      
      if ($shipping === null) {
        $shippingDate = null;  
      } else {
        $shippingDate = new DateTime($shipping);
        $shippingDate = $shippingDate->format('Y-m-d');
      }
      
      if ($arrival === null) {
        $arrivalDate = null;  
      } else {
        $arrivalDate = new DateTime($arrival);
        $arrivalDate = $arrivalDate->format('Y-m-d');
      }
      
      
      $params['shippingDate'] = $shippingDate;
      $params['arrivalDate'] = $arrivalDate;
      $params['originType'] = $originType;
      $params['originRegion'] = $originRegion;
      $params['originState'] = $originState;
      $params['originCity'] = $originCity;
      $params['originAddress'] = $originAddress;
      
      $connection = $this->manager->getConnection();
      $statement = $connection->prepare($sql);
      $statement->executeQuery($params);
      $orderLogisticId = $connection->lastInsertId();

      return new JsonResponse([
        'response' => [
          'data'    => [
          'id' => $orderLogisticId
          ],
          'payload' => $payload,
          'count'   => 1,
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

      return new JsonResponse([
        'response' => [
          'data'    => [$status],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }
}
