<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Contract;
use ControleOnline\Entity\Order;

class ChangeContractPaymentAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager = $entityManager;
  }

  public function __invoke(Contract $data, Request $request): JsonResponse
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      $payload  = json_decode($request->getContent(), true);

      /**
       * @var \ControleOnline\Entity\SalesOrder $order  
       */
      $order = $this->manager->getRepository(Order::class)->findOneBy(
        ['contract' => $data->getId()]
      );

      if ($order) {
        $data->setHtmlContent($payload['htmlContent']); // Adicione o novo conteúdo HTML (Kim, termina isso!)
        $order->addOtherInformations('paymentType', $payload['paymentType']);
        $this->manager->persist($data);
        $this->manager->persist($order);
      } else {
        throw new \Exception("Order not found", 404);
      }

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return new JsonResponse([
        'response' => [
          'data'    => ['contractId' => $data->getId()],
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

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
