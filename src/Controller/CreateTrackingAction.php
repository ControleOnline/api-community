<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use App\Entity\OrderTracking;

use Exception;

class CreateTrackingAction
{


  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;


  /**
   * Current user
   *
   * @var \ControleOnline\Entity\User
   */
  private $currentUser = null;

  public function __construct(
    EntityManagerInterface $entityManager,
    Security $security
  ) {
    $this->manager     = $entityManager;
    $this->security    = $security;
    $this->currentUser = $security->getUser();
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $order = null;
      $params = [
        'order_id' => null,
        'system_type' => null,
        'notified' => 0,
        'tracking_status' => null,
        'data_hora' => null,
        'dominio' => null,
        'filial' => null,
        'cidade' => null,
        'ocorrencia' => null,
        'descricao' => null,
        'tipo' => null,
        'data_hora_efetiva' => null,
        'nome_recebedor' => null,
        'nro_doc_recebedor' => null,
      ];

      if ($content = $request->getContent()) {
        $params = array_merge($params, json_decode($content, true));
      }

      if (!$params['order_id'])
        throw new Exception("Order id is required", 403);

      $orderTracking = new OrderTracking();
      $orderTracking->setOrder($params['order_id']);
      $orderTracking->setSystemType($params['system_type']);
      $orderTracking->setNotified(0);
      $orderTracking->setTrackingStatus($params['tracking_status']);
      $orderTracking->setDataHora($params['data_hora']);
      $orderTracking->setDominio($params['dominio']);
      $orderTracking->setFilial($params['filial']);
      $orderTracking->setCidade($params['cidade']);
      $orderTracking->setOcorrencia($params['ocorrencia']);
      $orderTracking->setDescricao($params['descricao']);
      $orderTracking->setTipo($params['tipo']);
      $orderTracking->setDataHoraEfetiva($params['data_hora_efetiva']);
      $orderTracking->setNomeRecebedor($params['nome_recebedor']);
      $orderTracking->setNroDocRecebedor($params['nro_doc_recebedor']);


      $this->manager->persist($orderTracking);
      $this->manager->flush();

      return new JsonResponse([
        'response' => [
          'data'    => [
            'id' => $orderTracking->getId()
          ],
          'params' => $params,
          'count'   => 0,
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'line'   => $e->getLine(),
          'success' => false,
        ],
      ]);
    }
  }
}
