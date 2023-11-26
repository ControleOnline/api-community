<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\MyContract;
use App\Entity\SalesOrder;
use App\Entity\People;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\ReceiveInvoice;
use App\Entity\SalesOrderInvoice;
use App\Entity\MyContractProductPayment;
use App\Entity\MyContractOrderInvoice;

class SchoolClassPayerInvoiceCommand extends Command
{
  protected static $defaultName = 'app:school-class-payer-invoice';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  public function __construct(EntityManagerInterface $entityManager)
  {
      $this->manager = $entityManager;

      parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Generate order and invoice for contract payers.')
      ->setHelp       ('This command generate order and invoices.')
    ;

    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of contracts to process');
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

    $limit     = $input->getArgument('limit') ?: 100;
    $contracts = $this->getActiveContracts($limit);

    if (empty($contracts)) {
      $output->writeln([
        '',
        '   No active contracts.',
        '',
      ]);
    }
    else {

      $output->writeln([
        '',
        '   =========================================',
        sprintf('   Contracts  : %s', count($contracts)),
        '   =========================================',
        '',
      ]);

      foreach ($contracts as $contract) {
        $result = $this->createOrderInvoice($contract);

        $output->writeln([
          '',
          '   =========================================',
          sprintf('   Contract: %s', $result['contractId']),
          sprintf('   Message : %s', $result['message']),
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

  private function getActiveContracts(int $limit): array
  {
    $repositorio = $this->manager->getRepository(MyContract::class);

    return $repositorio
        ->createQueryBuilder('contract')
        ->select()
        ->where('contract.contractStatus IN (:statuses)')
        ->setParameters([
          'statuses'  => ['Active']
        ])
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
  }

  private function createOrderInvoice(MyContract $contract): array
  {
    $month = new \DateTime(date('Y-m-').'01 00:00:00');

    $contactId = $contract->getId();

    $output = [
      'contractId' => $contactId,
      'message'    => 'OK',
    ];

    if (!empty($contactId)) {

      try {

        $this->manager->getConnection()->beginTransaction();

        $payers = $this->getContractPeoplePayers($contract);
        if (empty($payers)) {
          throw new \Exception('There is no payers');
        }

        $school = $this->getContractProvider($contract);
        if ($school === null) {
          throw new \Exception('There is no provider');
        }

        $total   = $this->getContractProductsTotalPrice($contract);
        $ostatus = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'delivered','context' => 'order']);
        $istatus = $this->manager->getRepository(Status::class)->findOneBy(['status' => ['open'],'context' => 'invoice']);

        // create orders

        $created = 0;

        foreach ($payers as $payer) {
          $client  = $payer['people'];
          $dueDate = $this->getInvoiceDueDate($client);

          $contractOrderInvoice = $this->manager->getRepository(MyContractOrderInvoice::class)
            ->findOneBy([
              'contract' => $contract,
              'payer'    => $payer['people'],
              'provider' => $school,
              'dueDate'  => $dueDate,
            ]);
          
          $orders = $this->manager->getRepository(SalesOrder::class)
            ->createQueryBuilder('so')
            ->select()
            ->innerJoin('so.invoice', 'soi')
            ->innerJoin('soi.invoice', 'i')
            ->where('so.contract = :contract')
            ->andWhere('so.payer = :payer')
            ->andWhere('so.provider = :provider')
            ->andWhere('i.dueDate >= :month')
            ->setParameters([
              'contract' => $contact,
              'payer'      => $payer['people'],
              'provider'   => $school,
              'month'      => $month
            ])
            ->getQuery()
            ->getResult();

          if (count($orders) === 0 && $contractOrderInvoice === null) {
            $amount = (($total * $payer['percent']) / 100);
            $amount = $amount + $this->getContractRegistrationTotalPrice($contract, $payer['people'], $dueDate);

            $order = new SalesOrder();
            $order->setStatus    ($ostatus);
            $order->setClient    ($client);
            $order->setProvider  ($school);
            $order->setContract  ($contact);
            $order->setPayer     ($client);
            $order->setPrice     ($amount);

            $this->manager->persist($order);
            $this->manager->flush();

            $invoice = new ReceiveInvoice();
            $invoice->setPrice   ($amount);
            $invoice->setDueDate ($dueDate);
            $invoice->setStatus  ($istatus);
            $invoice->setNotified(false);

            $this->manager->persist($invoice);
            $this->manager->flush();

            $orderInvoice = new SalesOrderInvoice();
            $orderInvoice->setInvoice($invoice);
            $orderInvoice->setOrder  ($order);

            $this->manager->persist($orderInvoice);
            $this->manager->flush();

            $contractOrderInvoice = new MyContractOrderInvoice();
            $contractOrderInvoice->setContract($contract);
            $contractOrderInvoice->setPayer   ($payer['people']);
            $contractOrderInvoice->setProvider($school);
            $contractOrderInvoice->setOrder   ($order);
            $contractOrderInvoice->setInvoice ($invoice);
            $contractOrderInvoice->setAmount  ($amount);
            $contractOrderInvoice->setDuedate ($dueDate);

            $this->manager->persist($contractOrderInvoice);
            $this->manager->flush();

            $created++;
          }
        }

        $this->manager->getConnection()->commit();

        $output['message'] = sprintf('Contract orders processed: %d', $created);

      } catch (\Exception $e) {
        if ($this->manager->getConnection()->isTransactionActive()) {
          $this->manager->getConnection()->rollBack();
        }
        $output['message'] = $e->getMessage();
      }
      
    }

    return $output;
  }

  private function getContractPeoplePayers(MyContract $contract): array
  {
    $contractPeople = $contract->getContractPeople()
      ->filter(function($contractPeople) {
        return $contractPeople->getPeopleType() == 'Payer' && $contractPeople->getContractPercentage() > 0;
      });

    $payers = [];

    if (!$contractPeople->isEmpty()) {
      foreach ($contractPeople as $cpeople) {
        $payers[] = [
          'people'  => $cpeople->getPeople(),
          'percent' => $cpeople->getContractPercentage()
        ];
      }
    }

    return $payers;
  }

  private function getContractProductsTotalPrice(MyContract $contract): float
  {
    $contractProduct = $contract->getContractProduct()
      ->filter(function($contractProduct) {
        return $contractProduct->getProduct()->getProductSubtype() == 'Package'
               && $contractProduct->getProduct()->getBillingUnit() == 'Monthly'
               && $contractProduct->getPrice() > 0;
      });

    $total = 0;

    foreach ($contractProduct as $cproduct) {
      $total += $cproduct->getPrice() * $cproduct->getQuantity();
    }

    return (float) $total;
  }

  private function getContractRegistrationTotalPrice(MyContract $contract, People $payer, \DateTime $dueDate): float
  {
    $productPayment = $this->manager->getRepository(MyContractProductPayment::class)
      ->findOneBy(
        [
          'contract'  => $contract,
          'payer'     => $payer,
          'processed' => false,
        ],
        [
          'sequence' => 'ASC'
        ]
      );

    if ($productPayment === null) {
      return 0;
    }

    $this->manager->persist(
      $productPayment
        ->setDuedate  ($dueDate)
        ->setProcessed(true)
    );

    return $productPayment->getAmount();
  }

  private function getContractProvider(MyContract $contract): ?People
  {
    $contractPeople = $contract->getContractPeople()
      ->filter(function($contractPeople) {
        return $contractPeople->getPeopleType() == 'Provider';
      });

    if (($provider = $contractPeople->first()) === false) {
      return null;
    }

    return $provider->getPeople();
  }

  private function getInvoiceDueDate(People $people): \DateTime
  {
    switch ($people->getBillingDays()) {
      case 'weekly':
        $date = new \DateTime('friday');
      break;
      case 'biweekly':
        $hoje = (int) date('d');

        if ($hoje >= 15) {
          $date = new \DateTime('first day of next month');
        }
        else {
          $date = new \DateTime(date('Y-m-15'));
        }

        $date = $date->modify('-1 days');
      break;
      case 'monthly':
        $date = new \DateTime('first day of next month');
        $date = $date->modify('-1 days');
      break;
      default:
        $date = new \DateTime('today');
      break;
    }

    return $date->modify('+'.$people->getPaymentTerm().' days');
  }
}
