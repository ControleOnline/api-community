<?php

namespace App\Command;

use ControleOnline\Entity\OrderTracking;
use ControleOnline\Entity\InvoiceTax;
use ControleOnline\Entity\Order;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\DatabaseSwitchService;

class OrderRetrieveStatusCommand extends Command
{
  protected static $defaultName = 'app:order-retrieve-status';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager;

  /**
   * Entity manager
   *
   * @var DatabaseSwitchService
   */
  private $databaseSwitchService;
  private $webservice = [];

  public function __construct(EntityManagerInterface $entityManager, DatabaseSwitchService $databaseSwitchService)
  {
    $this->manager    = $entityManager;
    $this->webservice[] = new \App\Library\SSW\Client;
    //$this->webservice[] = new \App\Library\Movvi\Client;
    $this->databaseSwitchService = $databaseSwitchService;

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

    $domains = $this->databaseSwitchService->getAllDomains();
    foreach ($domains as $domain) {
      $this->databaseSwitchService->switchDatabaseByDomain($domain);

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

        foreach ($orders as $Order) {
          $result = $this->createTrackingStatuses($Order);

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
    }
    return 0;
  }

  private function createTrackingStatuses(Order $order): array
  {
    $output       = [
      'orderId' => $order->getId(),
      'message' => 'OK',
    ];

    $trackings = $this->getOrderTracking($order);

    return $output;
  }

  private function getOrderTracking(Order $order): array
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
     * @var \ControleOnline\Repository\OrderRepository
     */
    $repositorio = $this->manager->getRepository(Order::class);

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
