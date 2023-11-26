<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Contract;

class SchoolCancelContractCommand extends Command
{
  protected static $defaultName = 'app:school-cancel-contract';

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
      ->setDescription('Cancel contracts.')
      ->setHelp       ('This command cancel the contracts.')
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
    $contracts = $this->getCancelDuedateContracts($limit);

    if (empty($contracts)) {
      $output->writeln([
        '',
        '   No contracts.',
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
        $result = $this->updateContractStatusToCanceled($contract);

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

  private function getCancelDuedateContracts(int $limit): array
  {
    return $this->manager->getRepository(Contract::class)
        ->createQueryBuilder('contract')
        ->select()
        ->where   ('contract.contractStatus IN (:contract_status)')
        ->andWhere('contract.endDate < :duedate')

        ->setParameters([
          'contract_status' => ['Draft', 'Active'],
          'duedate'         => (new \DateTime('today'))->format('Y-m-d'),
        ])

        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
  }

  private function updateContractStatusToCanceled(Contract $contract): array
  {
    $output = [
      'contractId' => $contract->getId(),
      'message'    => 'OK',
    ];

    try {
      $this->manager->getConnection()->beginTransaction();

      $this->manager->persist($contract->setContractStatus('Canceled'));

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      $output['message'] = 'Contract was canceled successfully!';
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }
      $output['message'] = $e->getMessage();
    }

    return $output;
  }
}
