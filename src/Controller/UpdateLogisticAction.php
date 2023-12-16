<?php

namespace App\Controller;

use ControleOnline\Entity\OrderLogistic;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\People;
use ControleOnline\Entity\SalesOrder;
use App\Repository\OrderLogisticRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Datetime;
use LDAP\Result;

class UpdateLogisticAction
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
      $sql = "INSERT INTO `order_logistic`(`shipping_date`) 
      VALUES (:shippingDate);
      ";
      $sql.= "WHERE id = :idLogistic";
      
      $params = [];
      if ($payload['orderId'] && $payload['status']) {
        $params['orderId'] = $payload['orderId'];
        $params['statusId'] = $payload['status'];
      }
      
      $param['providerId'] = null;
      if ($payload['provider']) {
        $params['providerId'] = $payload['provider'];
      }

      $shippingDate = $payload['shippingDate'] == null ? null : new DateTime($payload['shippingDate']);
      if ($shippingDate) {
        $shippingDate = $shippingDate->format('Y-m-d');
      }

      $arrivalDate = $payload['arrivalDate'] == null ? null : new DateTime($payload['arrivalDate']);
      if ($arrivalDate) {
        $arrivalDate = $arrivalDate->format('Y-m-d');
      }

      $params['shippingDate'] = $shippingDate;
      $params['arrivalDate'] = $arrivalDate;
      $params['originType'] = $payload['originType'];
      $params['originRegion'] = $payload['originRegion'];
      $params['originState'] = $payload['originState'];
      $params['originCity'] = $payload['originCity'];
      $params['originAddress'] = $payload['originAddress'];
      $params['orderValue'] = $payload['value'];
      
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
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }
}
