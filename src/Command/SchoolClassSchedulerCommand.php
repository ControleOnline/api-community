<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Entity\MyContract;
use ControleOnline\Entity\SchoolClass;
use ControleOnline\Entity\Team;
use ControleOnline\Entity\SchoolTeamSchedule;
use ControleOnline\Entity\SchoolClassStatus;

class SchoolClassSchedulerCommand extends Command
{
  protected static $defaultName = 'app:school-class-scheduler';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  private $daysWeek = [
    'monday'    => 1,
    'tuesday'   => 2,
    'wednesday' => 3,
    'thursday'  => 4,
    'friday'    => 5,
    'saturday'  => 6,
    'sunday'    => 7
  ];

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager = $entityManager;

    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Schedule school team classes.')
      ->setHelp('This command schedule classes.');

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
    } else {

      $output->writeln([
        '',
        '   =========================================',
        sprintf('   Contracts  : %s', count($contracts)),
        '   =========================================',
        '',
      ]);

      foreach ($contracts as $contract) {
        $result = $this->createSchoolClassSchedule($contract);

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

  private function createSchoolClassSchedule(MyContract $contract): array
  {
    $output       = [
      'contractId' => $contract->getId(),
      'message'    => 'Scheduled',
    ];

    $teams = $this->manager->getRepository(Team::class)->findBy(['contract' => $contract]);
    if (empty($teams)) {
      $output['message'] = 'Contract has no any team';
      return $output;
    }

    try {
      $this->manager->getConnection()->beginTransaction();

      foreach ($teams as $team) {
        $this->scheduleTeamSchoolClasses($team);
      }

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      $output['message'] = 'All school classes were scheduled successfully!';
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }
      $output['message'] = $e->getMessage();
    }

    return $output;
  }

  private function scheduleTeamSchoolClasses(Team $team)
  {
    $schedule = $this->manager->getRepository(SchoolTeamSchedule::class)->findBy(['team' => $team]);
    if (empty($schedule)) {
      throw new \Exception(
        sprintf('There is no schedule for team ID "%s"', $team->getId())
      );
    }

    $defaultStatus = $this->manager->getRepository(SchoolClassStatus::class)->findOneBy(['lessonStatus' => 'Scheduled']);
    if ($defaultStatus === null) {
      throw new \Exception('Default lesson schedule status is undefined');
    }

    /*$todayDate = (new \DateTime('now'))->format('Y-m-d');*/
    $fromDate  = $team->getContract()->getStartDate()->format('Y-m-d');

    /*if ($fromDate <= $todayDate) {
      $fromDate = $todayDate;
    }*/

    foreach ($schedule as $dayTime) {

      $scheduledDays = $this->getScheduledDaysFromDateAndWeekDay($fromDate, $dayTime->getWeekDay());

      // create class schedule

      foreach ($scheduledDays as $day) {
        $startPrevision = $day . ' ' . $dayTime->getStartTime()->format('H:i:s');
        $endsPrevision  = $day . ' ' . $dayTime->getEndTime()->format('H:i:s');

        $startPrevision = \DateTime::createFromFormat('Y-m-d H:i:s', $startPrevision);
        $endsPrevision  = \DateTime::createFromFormat('Y-m-d H:i:s', $endsPrevision);

        $scheduledClass = $this->manager->getRepository(SchoolClass::class)
          ->findOneBy([
            'team'           => $team,
            'startPrevision' => $startPrevision
          ]);

        if ($scheduledClass === null) {
          $this->manager->persist(
            (new SchoolClass())
              ->setTeam($team)
              ->setSchoolClassStatus($defaultStatus)
              ->setOriginalStartPrevision($startPrevision)
              ->setStartPrevision($startPrevision)
              ->setEndPrevision($endsPrevision)
              ->setLessonStart(null)
              ->setLessonEnd(null)
          );
        }
      }
    }
  }

  private function getScheduledDaysFromDateAndWeekDay(string $fromDate, string $weekDay): array
  {
    $schedule   = [];

    // define start day

    $startDate  = $fromDate;
    $fromDayNum = (int) date('N', strtotime($fromDate));
    $dayWeekNum = $this->daysWeek[$weekDay];

    if ($dayWeekNum != $fromDayNum) {
      $addDays   = $dayWeekNum > $fromDayNum ? $dayWeekNum - $fromDayNum : (7 - $fromDayNum) + $dayWeekNum;
      $startDate = date('Y-m-d', strtotime("$fromDate+$addDays day"));
    }

    $schedule[] = $startDate;

    // define next days

    $nextDate = $startDate;
    $endDate  = (new \DateTime($startDate))->format('Y-m-t');
    $numWeeks = $this->getNumOfWeeksBetweenDates($startDate, $endDate) + 4;

    for ($i = 2; $i <= $numWeeks; $i++) {
      $schedule[] = $nextDate = date('Y-m-d', strtotime("$nextDate+7 day"));
    }

    return $schedule;
  }

  private function getNumOfWeeksBetweenDates(string $strtDate, string $endDate): int
  {
    $startDateWeekCnt = round(floor(date('d', strtotime($strtDate)) / 7));
    $endDateWeekCnt   = round(ceil(date('d', strtotime($endDate)) / 7));

    $datediff         = strtotime(date('Y-m', strtotime($endDate)) . "-01") - strtotime(date('Y-m', strtotime($strtDate)) . "-01");
    $totalnoOfWeek    = round(floor($datediff / (60 * 60 * 24)) / 7) + $endDateWeekCnt - $startDateWeekCnt;

    return $totalnoOfWeek;
  }
}
