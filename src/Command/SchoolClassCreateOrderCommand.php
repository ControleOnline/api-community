<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Entity\SchoolClass;
use ControleOnline\Entity\SchoolClassStatus;
use ControleOnline\Entity\PurchasingOrder;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\Team;
use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\ParticularsType;

class SchoolClassCreateOrderCommand extends Command
{
  protected static $defaultName = 'app:school-class-create-order';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager;

  public function __construct(EntityManagerInterface $entityManager)
  {
      $this->manager = $entityManager;

      parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Create school class order.')
      ->setHelp       ('This command create an order for class.')
    ;

    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of classes to process');
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

    $limit   = $input->getArgument('limit') ?: 100;
    $classes = $this->getClassesWithoutOrder($limit);

    if (empty($classes)) {
      $output->writeln([
        '',
        '   No classes.',
        '',
      ]);
    }
    else {

      $output->writeln([
        '',
        '   =========================================',
        sprintf('   Classes  : %s', count($classes)),
        '   =========================================',
        '',
      ]);

      foreach ($classes as $schclass) {
        $result = $this->createClassOrder($schclass);

        $output->writeln([
          '',
          '   =========================================',
          sprintf('   Class  : %s', $result['schclassId']),
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

  private function getClassesWithoutOrder(int $limit): array
  {
    return $this->manager->getRepository(SchoolClass::class)
        ->createQueryBuilder('schclass')
        ->select()
        ->where   ('schclass.schoolClassStatus IN (:statuses)')
        ->andWhere('schclass.order IS NULL')

        ->setParameters([
          'statuses' => $this->manager->getRepository(SchoolClassStatus::class)
            ->findBy(['lessonRealStatus' => ['Given', 'Missed']]),
        ])

        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
  }

  private function createClassOrder(SchoolClass $schclass): array
  {
    $output = [
      'schclassId' => $schclass->getId(),
      'message'    => 'OK',
    ];

    try {
      $month = new \DateTime(date('Y-m-').'01 00:00:00');
      
      $this->manager->getConnection()->beginTransaction();

      $price    = $this->getTeamClassPrice($schclass);
      $status   = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'on the way','context' => 'order']);
      $team     = $schclass->getTeam();
      $school   = $team->getCompanyTeam();
      $professionals = $team->getPeopleTeams()
        ->filter(
          function($peopleTeam) {
            return $peopleTeam->getPeopleType() == 'professional';
          }
        );
      if ($professionals->isEmpty()) {
        throw new \Exception('There is no professionals');
      }

      $professional = $professionals->first()->getPeople();

      $contract = $team->getContract();

      $salesOrderRepository = $this->manager->getRepository(SalesOrder::class);

      $mainOrders = $salesOrderRepository->createQueryBuilder('o')
      ->select()
      ->where('o.contract = :contract')
      ->andWhere('o.orderDate >= :month')
      ->setParameters([
        'contract' => $contract,
        'month' => $month
      ])
      ->getQuery()
      ->getResult();

      if (count($mainOrders) === 0) {
        throw new \Exception('There is no main order');
      }

      // create order

      $order = new PurchasingOrder();
      $order->setStatus    ($status);
      $order->setClient    ($school);
      $order->setProvider  ($professional);
      $order->setContract($contract);
      $order->setMainOrder ($mainOrders[0]);
      $order->setPayer     ($school);
      $order->setPrice     ($price);

      $this->manager->persist($order);
      $this->manager->flush();

      // update class with order

      $this->manager->persist($schclass->setOrder($order));
      $this->manager->flush();
      
      $this->manager->getConnection()->commit();

      $output['message'] = 'Orders created successfully!';
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }
      $output['message'] = $e->getMessage();
    }

    return $output;
  }

  private function getTeamClassPrice(SchoolClass $class): float
  {
    $professionals = $class->getTeam()->getPeopleTeams()
      ->filter(
        function($peopleTeam) {
          return $peopleTeam->getPeopleType() == 'professional';
        }
      );
    if ($professionals->isEmpty()) {
      throw new \Exception('There is no professionals');
    }
    $professional = $professionals->first()->getPeople();

    // set class types

    $types = [
      'ead'     => 10,
      'company' => 11,
      'school'  => 12,
    ];

    // get price from particulars

    $ptype = $this->manager->getRepository(ParticularsType::class)->find($types[$class->getTeam()->getType()]);
    if ($ptype === null) {
      throw new \Exception('Particular price type not found');
    }

    $price = $this->manager->getRepository(Particulars::class)
      ->findOneBy([
        'type'   => $ptype,
        'people' => $professional
      ]);
    if ($price === null) {
      throw new \Exception('Particular price not found');
    }

    if (is_numeric($price->getValue()) === false) {
      throw new \Exception('Particular price value is not valid');
    }

    $price = (float) $price->getValue();

    // price x hour

    $start = $class->getStartPrevision();
    $end   = $class->getEndPrevision();
    $diff  = date_diff($end, $start)->format("{\"years\":\"%y\", \"months\":\"%m\", \"days\":\"%d\", \"hours\": \"%h\", \"minutes\": \"%i\", \"seconds\": \"%s\"}");

    $diffObj = json_decode($diff, true);

    $diffSeconds = (int) $diffObj["seconds"];
    $diffSeconds += ((int) $diffObj["minutes"]) * 60;
    $diffSeconds += ((int) $diffObj["hours"]) * 60 * 60;
    $diffSeconds += ((int) $diffObj["days"]) * 24 * 60 * 60;
    $diffSeconds += ((int) $diffObj["months"]) * 30 * 24 * 60 * 60;
    $diffSeconds += ((int) $diffObj["years"]) * 12 * 30 * 24 * 60 * 60;

    $hours = $diffSeconds / (60 * 60);

    return $price * $hours;
  }
}
