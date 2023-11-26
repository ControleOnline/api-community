<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\SchoolClass;
use App\Entity\SchoolClassStatus;

class SchoolClassSetAsMissedCommand extends Command
{
  protected static $defaultName = 'app:school-class-missed';

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
      ->setDescription('Set school class as missed.')
      ->setHelp       ('This command set classes as missed.')
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
    $classes = $this->getMissedClasses($limit);

    if (empty($classes)) {
      $output->writeln([
        '',
        '   No missed classes.',
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
        $result = $this->setSchoolClassAsMissed($schclass);

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

  private function getMissedClasses(int $limit): array
  {
    $todayDate  = (new \DateTime('now'))->format('Y-m-d H:i:s');
    $today30mns = date('Y-m-d H:i', strtotime("$todayDate - 30 minutes"));

    return $this->manager->getRepository(SchoolClass::class)
        ->createQueryBuilder('schclass')
        ->select()
        ->where   ('schclass.schoolClassStatus IN (:statuses)')
        ->andWhere('schclass.lessonStart IS NULL')
        ->andWhere('schclass.startPrevision <= :startDate')

        ->setParameters([
          'statuses'  => $this->manager->getRepository(SchoolClassStatus::class)->findBy(['lessonRealStatus' => 'Pending']),
          'startDate' => $today30mns
        ])

        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
  }

  private function setSchoolClassAsMissed(SchoolClass $schclass): array
  {
    $output = [
      'schclassId' => $schclass->getId(),
      'message'    => 'OK',
    ];

    try {
      $this->manager->getConnection()->beginTransaction();

      // do check-in

      $schclass->setLessonStart(
        new \DateTime('now')
      );

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      $output['message'] = 'Class check-in done successfully!';
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }
      $output['message'] = $e->getMessage();
    }

    return $output;
  }

}
