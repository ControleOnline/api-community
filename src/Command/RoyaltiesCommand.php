<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use App\Library\Utils\Formatter;

use App\Service\MauticService;
use App\Service\EmailService;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\PurchasingOrder;
use ControleOnline\Entity\SalesOrderInvoice;
use ControleOnline\Entity\InvoiceTax;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\PurchasingOrderInvoiceTax;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\PeopleSalesman;
use ControleOnline\Repository\ConfigRepository;
use App\Library\Itau\ItauClient;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\Quotation;
use ControleOnline\Service\DatabaseSwitchService;

class RoyaltiesCommand extends Command
{
  protected static $defaultName = 'app:royalties';

  protected $em;

  protected $ma;

  protected $errors = [];

  private $payment = [];

  private $itau_configs = [];
  /**
   * Entity manager
   *
   * @var DatabaseSwitchService
   */
  private $databaseSwitchService;
  /**
   * Twig render
   *
   * @var \Twig\Environment
   */
  private $twig;

  /**
   * Config repository
   *
   * @var \ControleOnline\Repository\ConfigRepository
   */
  private $config;

  public function __construct(EntityManagerInterface $entityManager, MauticService $mauticService, ConfigRepository $config, Environment $twig, DatabaseSwitchService $databaseSwitchService)
  {
    $this->em     = $entityManager;
    $this->ma     = $mauticService;
    $this->config = $config;
    $this->twig   = $twig;
    $this->databaseSwitchService = $databaseSwitchService;

    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Sends notifications according to order status.')
      ->setHelp('This command cares of send order notifications.');

    $this->addArgument('target', InputArgument::REQUIRED, 'Notifications target');
    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of orders to process');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $domains = $this->databaseSwitchService->getAllDomains();
    foreach ($domains as $domain) {
      $this->databaseSwitchService->switchDatabaseByDomain($domain);

      $targetName = $input->getArgument('target');
      $orderLimit = $input->getArgument('limit') ?: 100;

      $getOrders  = 'get' . str_replace('_', '', ucwords(strtolower($targetName), '_')) . 'Orders';
      if (method_exists($this, $getOrders) === false)
        throw new \Exception(sprintf('Notification target "%s" is not defined', $targetName));

      $output->writeln([
        '',
        '=========================================',
        sprintf('Notification target: %s', $targetName),
        '=========================================',
        sprintf('Rows to process: %d', $orderLimit),
        '',
      ]);

      // get orders

      $orders = $this->$getOrders($orderLimit);

      if (!empty($orders)) {
        foreach ($orders as $order) {

          // start notifications...

          $output->writeln([sprintf('      OrderID : #%s', $order->order)]);
          $output->writeln([sprintf('      Franchisee : %s', $order->franchisee)]);
          $output->writeln([sprintf('      Franchisor: %s', $order->franchisor)]);
          $output->writeln([sprintf('      Subject : %s', $order->subject)]);

          $result = $order->notifier['send']();

          if (is_bool($result)) {
            $order->events[$result === true ? 'onSuccess' : 'onError']();
          } else {
            if ($result === null) {
              $output->writeln(['      Error   : send method internal error']);
            }
          }

          $output->writeln(['']);
        }
      } else
        $output->writeln('      There is no pending orders.');

      $output->writeln([
        '',
        '=========================================',
        'End of Order Notifier',
        '=========================================',
        '',
      ]);
    }
    return 0;
  }



  /**
   * Cria a comissão após o pedido ser entregue
   */
  protected function getRoyaltiesOrders(int $limit): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->leftJoin('\ControleOnline\Entity\SalesOrder', 'CO', 'WITH', 'CO.mainOrder = O.id AND CO.orderType =:orderType')
      ->innerJoin('\ControleOnline\Entity\PeopleFranchisee', 'PS', 'WITH', 'PS.franchisee = O.provider')
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'SI', 'WITH', 'SI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\Invoice', 'I', 'WITH', 'I.id = SI.invoice')
      ->where('O.status =:status')
      ->andWhere('I.status =:istatus')
      ->andWhere('CO.id IS NULL')
      ->andWhere('O.orderType =:sorderType')
      ->setParameters(array(
        'istatus' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'paid', 'context' => 'invoice']),
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'delivered', 'context' => 'order']),
        'orderType' => 'royalties',
        'sorderType' => 'sale'
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()->getResult();


    if (count($salesOrders) == 0)
      return null;
    else {

      foreach ($salesOrders as $order) {

        $orders[] = (object) [
          'order'         => $order->getId(),
          'franchisee'    => $order->getProvider()->getName(),
          //'franchisor'    => $order->getProvider()->getPeopleFranchisee()[0]->getFranchisor()->getName(),
          'subject'       => 'Generate royalties',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                $peopleFranchisees = $order->getProvider()->getPeopleFranchisee();
                foreach ($peopleFranchisees as $peopleFranchisee) {
                  $price = $order->getPrice() * $peopleFranchisee->getRoyalties() / 100;
                  $commissionOrder = clone $order;
                  $this->em->detach($commissionOrder);
                  $commissionOrder->resetId();
                  $commissionOrder->setOrderType('royalties');
                  $commissionOrder->setMainOrder($order);
                  $commissionOrder->setClient($peopleFranchisee->getFranchisee());
                  $commissionOrder->setPayer($peopleFranchisee->getFranchisee());
                  $commissionOrder->setProvider($peopleFranchisee->getFranchisor());
                  $commissionOrder->setPrice($price);
                  $this->em->persist($commissionOrder);
                  $this->em->flush($commissionOrder);
                }
                return true;
              } catch (\Exception $e) {
                echo  $e->getMessage();
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
            },
          ],
        ];
      }
    }
    return $orders;
  }
}
