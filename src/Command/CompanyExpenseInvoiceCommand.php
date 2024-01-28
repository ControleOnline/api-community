<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\DatabaseSwitchService;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\OrderInvoice;

class CompanyExpenseInvoiceCommand extends Command
{
  protected static $defaultName = 'app:company-expense-invoice';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;
  /**
   * Entity manager
   *
   * @var DatabaseSwitchService
   */
  private $databaseSwitchService;

  public function __construct(EntityManagerInterface $entityManager, DatabaseSwitchService $databaseSwitchService)
  {
    $this->manager = $entityManager;
    $this->databaseSwitchService = $databaseSwitchService;

    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Generate order and invoice for company recurrent expenses.')
      ->setHelp('This command generate order and invoices.');

    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of recurrent expenses to process');
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

      $limit    = $input->getArgument('limit') ?: 100;
      $expenses = $this->getActiveRecurrentExpenses($limit);

      if (empty($expenses)) {
        $output->writeln([
          '',
          '   No active expenses.',
          '',
        ]);
      } else {

        $output->writeln([
          '',
          '   =========================================',
          sprintf('   Expenses  : %s', count($expenses)),
          '   =========================================',
          '',
        ]);

        foreach ($expenses as $expense) {
          $result = $this->createOrderInvoice($expense);

          $output->writeln([
            '',
            '   =========================================',
            sprintf('   Expense: %s', $result['expenseId']),
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

  private function getActiveRecurrentExpenses(int $limit): array
  {
    $repository = $this->manager->getRepository(Order::class);

    $lastDate = new \DateTime(
      date('Y-m', strtotime("-1 months"))
    );

    $todayDate = new \DateTime(
      date('Y-m', strtotime("-0 months"))
    );

    $toDate = new \DateTime(
      date('Y-m', strtotime("+1 months"))
    );


    return $repository
      ->createQueryBuilder('o')
      ->select()
      ->innerJoin('o.invoice', 'oi')
      ->innerJoin('oi.invoice', 'i')
      ->leftJoin('o.invoice', 'oin')
      ->leftJoin('\ControleOnline\Entity\Invoice', 'ni', 'WITH', '(ni.id = oin.invoice AND ni.dueDate >= :todayDate AND ni.dueDate < :toDate)')
      ->where('i.paymentMode = 0')
      ->having('COUNT(ni.id) = 0')
      ->andWhere("o.orderType = 'purchase'")
      ->andWhere("i.dueDate >= :lastDate")
      ->andWhere("i.dueDate < :todayDate")
      ->andWhere("i.status != :status")
      ->setParameters([
        'lastDate' => $lastDate,
        'todayDate' => $todayDate,
        'toDate' => $toDate,
        'status' => $this->manager->getRepository(Status::class)->findBy(['status' => ['canceled'], 'context' => 'invoice'])
      ])
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  private function createOrderInvoice(Order $expense): array
  {
    $output = [
      'expenseId' => $expense->getId(),
      'message'   => 'OK',
    ];

    try {

      $baseInvoice = $expense->getOneInvoice();

      if ($baseInvoice instanceof Invoice) {
        $currentDuedate = new \DateTime(
          date(sprintf('Y-m-%s 00:00:00', $baseInvoice->getDuedate()->format('d')))
        );


        $currentInvoice = $this->manager->getRepository(Order::class)
          ->createQueryBuilder('o')
          ->select()
          ->innerJoin('o.invoice', 'oi')
          ->innerJoin('oi.invoice', 'i')
          ->where('i.paymentMode = 0')
          ->andWhere("o.orderType = 'purchase'")
          ->andWhere("i.dueDate = :duedate")
          ->andWhere("o.client = :client")
          ->andWhere("o.provider = :provider")
          ->andWhere("o.id = :order")
          ->setParameters([
            'order' => $expense->getId(),
            'client' => $expense->getClient(),
            'provider' => $expense->getProvider(),
            'duedate' => $currentDuedate
          ])
          ->setMaxResults(1)
          ->getQuery()
          ->getResult();

        if (!isset($currentInvoice[0])) {
          $this->manager->getConnection()->beginTransaction();

          $invoice = clone $baseInvoice;

          $order_invoice = new OrderInvoice();
          $order_invoice->setOrder($expense);
          $order_invoice->setInvoice($invoice);
          $order_invoice->setRealPrice($invoice->getPrice());

          $invoice->setDueDate($currentDuedate);
          $invoice->addOrder($order_invoice);
          $invoice->setStatus(
            $this->manager->getRepository(Status::class)
              ->findOneBy(['status' => 'waiting payment', 'context' => 'invoice'])
          );
          $invoice->setNotified(false);
          $invoice->setPaymentMode(0);

          $this->manager->persist($invoice);
          $this->manager->persist($order_invoice);
          $this->manager->flush();
          $this->manager->getConnection()->commit();

          $output['message'] = sprintf('created! (%s)', $invoice->getId());
        }
      }
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }
      echo $e->getMessage();
      $output['message'] = $e->getMessage();
    }

    return $output;
  }
}
