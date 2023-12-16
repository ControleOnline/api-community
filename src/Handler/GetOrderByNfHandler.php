<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;


use ControleOnline\Entity\OrderNf;
use ControleOnline\Entity\SalesInvoiceTax;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\SalesOrderInvoiceTax;
use App\Repository\SalesOrderRepository;

class GetOrderByNfHandler implements MessageHandlerInterface
{
  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager;

  /**
   * People Repository
   *
   * @var \App\Repository\SalesOrderRepository
   */
  private $order;

  /**
   * User entity
   *
   * @var \ControleOnline\Entity\User
   */
  private $myUser;

  public function __construct(EntityManagerInterface $manager, Security $security)
  {
    $this->manager          = $manager;
    $this->order            =  $manager->getRepository(SalesOrder::class);
    $this->invoiceTax       =  $manager->getRepository(SalesInvoiceTax::class);
    $this->orderInvoiceTax  =  $manager->getRepository(SalesOrderInvoiceTax::class);

    $this->myUser      = $security->getUser();
  }

  public function __invoke(OrderNf $orderNf)
  {
    try {

      $order = null;
      $invoiceTax =  $this->invoiceTax->findOneBy(['invoiceKey' =>  $orderNf->key]);
      if ($invoiceTax)
        $order = $invoiceTax->getOrder()->first()->getOrder();


      return new JsonResponse([
        'response' => [
          'data'    => [
            'key'   => $orderNf->key,
            'order' => $order ? $order->getId() : null
          ],
          'error'   => false,
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'error_code' => $e->getCode(),
          'success' => false,
        ],
      ]);
    }
  }
}
