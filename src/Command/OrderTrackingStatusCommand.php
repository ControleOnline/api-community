<?php

namespace App\Command;

use ControleOnline\Entity\OrderTracking;
use ControleOnline\Entity\SalesInvoiceTax;
use ControleOnline\Entity\SalesOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

class OrderTrackingStatusCommand extends Command
{
  protected static $defaultName = 'app:order-tracking-status';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager;


  private $webservice = [];

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager    = $entityManager;
    $this->webservice[] = new \App\Library\SSW\Client;
    $this->webservice[] = new \App\Library\Movvi\Client;

    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Retrieve order tracking from webservice.')
      ->setHelp('This command cares of request order tracking.');

    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of orders to process');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln([
      '',
      '=========================================',
      'Starting...',
      '=========================================',
      '',
    ]);

    $limit  = $input->getArgument('limit') ?: 100;
    $orders = $this->getOrders($limit);

    if (empty($orders)) {
      $output->writeln([
        '',
        '   No orders.',
        '',
      ]);
    } else {

      $output->writeln([
        '',
        '   =========================================',
        sprintf('   Orders  : %s', count($orders)),
        '   =========================================',
        '',
      ]);

      foreach ($orders as $salesOrder) {
        $result = $this->createTrackingStatuses($salesOrder);

        $output->writeln([
          '',
          '   =========================================',
          sprintf('   Order  : %s', $result['orderId']),
          sprintf('   Message: %s', $result['message']),
          '   =========================================',
          '',
        ]);
      }
    }

    $output->writeln([
      '',
      '=========================================',
      'End',
      '=========================================',
      '',
    ]);

    return 0;
  }

  private function createTrackingStatuses(SalesOrder $order): array
  {
    $output       = [
      'orderId' => $order->getId(),
      'message' => 'OK',
    ];

    $invoiceTaxes = $order->getInvoiceTax()
      ->filter(
        function ($orderInvoiceTax) {
          return $orderInvoiceTax->getInvoiceType() === 55;
        }
      );

    if (!$invoiceTaxes->isEmpty()) {
      /**
       * @var \ControleOnline\Entity\SalesOrderInvoiceTax $orderInvoiceTax
       */
      foreach ($invoiceTaxes as $orderInvoiceTax) {
        $trackings = $this->getOrderTracking($orderInvoiceTax->getInvoiceTax());

        /**
         * @var \App\Library\SSW\Entity\Tracking $tracking
         */
        foreach ($trackings as $tracking) {
          $orderTracking = $this->manager->getRepository(OrderTracking::class)
            ->findOneBy([
              'order'    => $order,
              'dataHora' => $tracking->getDataHora()
            ]);

          if ($orderTracking === null) {
            $entity = new OrderTracking;
            $entity->setOrder($order);
            $entity->setSystemType($tracking->getSystemType());
            $entity->setNotified(0);
            $entity->setTrackingStatus($tracking->getTrackingNumber());
            $entity->setDataHora($tracking->getDataHora());
            $entity->setDominio($tracking->getDominio());
            $entity->setFilial($tracking->getFilial());
            $entity->setCidade($tracking->getCidade());
            $entity->setOcorrencia($tracking->getOcorrencia());
            $entity->setDescricao($tracking->getDescricao());
            $entity->setTipo($tracking->getTipo());
            $entity->setDataHoraEfetiva($tracking->getDataHoraEfetiva());
            $entity->setNomeRecebedor($tracking->getNomeRecebedor());
            $entity->setNroDocRecebedor($tracking->getNroDocRecebedor());

            $carrier = $order->getQuote()->getCarrier();
            $carrier->addOtherInformations('app', $tracking->getSystemType());
            $this->manager->persist($carrier);
            $this->manager->persist($entity);
          }
        }


        $this->manager->flush();
      }
    } else {
      $output['message'] = 'No invoices';
    }

    return $output;
  }

  private function getOrderTracking(SalesInvoiceTax $invoiceTax): array
  {
    $trackings = [];
    if (!empty($invoiceTax->getInvoice())) {
      $nfKey  = $invoiceTax->getInvoiceKey();
      foreach ($this->webservice as $webservice) {
        if ($nfKey) {
          $result = $webservice->getTracking($nfKey);
          if (is_array($result) && !empty($result)) {
            $trackings = array_merge($trackings, array_values($result));
          }
        }
      }
    }



    return $trackings;
  }

  private function getOrders(int $limit): array
  {
    /**
     * @var \App\Repository\SalesOrderRepository
     */
    $repositorio = $this->manager->getRepository(SalesOrder::class);

    return $repositorio
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('O.status', 'OS')
      ->where('OS.status IN (:statuses)')
      ->setParameters(['statuses' => ['waiting retrieve', 'on the way', 'retrieved'],])
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }
}
