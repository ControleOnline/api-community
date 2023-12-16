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

class OrderRetrieveStatusCommand extends Command
{
  protected static $defaultName = 'app:order-retrieve-status';

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
    //$this->webservice[] = new \App\Library\Movvi\Client;

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

    $trackings = $this->getOrderTracking($order);

    return $output;
  }

  private function getOrderTracking(SalesOrder $order): array
  {
    $trackings = [];

    foreach ($this->webservice as $webservice) {
      $result = $webservice->putRetrieve($order);
      if (is_array($result) && !empty($result)) {
        $trackings = array_merge($trackings, array_values($result));
      }
    }

    return $trackings;
  }

  private function getOrders(int $limit): array
  {
    /**
     * @var \ControleOnline\Repository\SalesOrderRepository
     */
    $repositorio = $this->manager->getRepository(SalesOrder::class);

    return $repositorio
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('O.status', 'OS')
      ->innerJoin('O.quote', 'Q')
      ->innerJoin('T.carrier', 'C')
      ->innerJoin('C.config', 'CC')
      ->where('OS.status IN (:statuses)')
      ->andWhere('O.notified = 1')
      ->andWhere('CC.config_key LIKE :config_key')      
      ->setParameters([
        'statuses' => ['waiting retrieve'],        
        'config_key' => 'ssw-%'
      ])
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }
}
