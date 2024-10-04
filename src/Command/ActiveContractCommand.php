<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\SignatureService;
use ControleOnline\Entity\MyContract;
use ControleOnline\Entity\People;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\Status;
use App\Library\Provider\Signature\Contract as SignatureContract;
use ControleOnline\Service\DatabaseSwitchService;

class ActiveContractCommand extends Command
{
  protected static $defaultName = 'app:active-contract';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager;

  /**
   * Signature Service
   *
   * @var SignatureService
   */
  private $signature;

  /**
   * MyContract
   *
   * @var MyContract
   */
  private $data;
  /**
   * Entity manager
   *
   * @var DatabaseSwitchService
   */
  private $databaseSwitchService;

  public function __construct(EntityManagerInterface $entityManager, SignatureService $signature, DatabaseSwitchService $databaseSwitchService)
  {
    $this->manager = $entityManager;
    $this->signature = $signature;
    $this->databaseSwitchService = $databaseSwitchService;

    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Change contract status from waiting approval to active.')
      ->setHelp('This command change contract status.');

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


    $domains = $this->databaseSwitchService->getAllDomains();
    foreach ($domains as $domain) {
      $this->databaseSwitchService->switchDatabaseByDomain($domain);

      $contracts = $this->getWaitingContracts($limit);

      if (empty($contracts)) {
        $output->writeln([
          '',
          '   No contracts.',
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
          $defaultCompany = $this->manager->getRepository(People::class)->find(2);
          $this->signature->setDefaultCompany($defaultCompany);

          $c = new SignatureContract($this->manager, $this->signature);
          $error = false;
          try {
            $data = $c->sign($contract);
          } catch (\Exception $e) {
            $error   = $e->getMessage();
          }

          if ($error) {

            $this->updateContractStatusToAnalysis($contract, $error);


            $output->writeln([
              '',
              '   =========================================',
              sprintf('   Contract: %s', $contract->getId()),
              sprintf('   Message : %s', $error),
              '   =========================================',
              '',
            ]);
          } else {
            $result = $this->updateContractStatusToActive($contract);
            $result['contractId'] = $contract->getId();
            $result['message']  = 'OK';
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

  private function getWaitingContracts(int $limit): array
  {
    return $this->manager->getRepository(MyContract::class)
      ->createQueryBuilder('contract')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'contract.id = O.contract')
      ->where('contract.contractStatus IN (:contract_status)')
      ->andWhere('O.status IN (:status)')
      ->setParameters([
        'contract_status' => ['Waiting approval', 'Analysis'],
        'status' => $this->manager->getRepository(Status::class)
          ->findOneBy(array(
            'status' => 'automatic analysis'
          ))
      ])
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  private function updateContractStatusToAnalysis(MyContract $contract, $error): array
  {

    /**
     * SalesOrder
     *
     * @var SalesOrder
     */
    $order = $this->manager->getRepository(SalesOrder::class)->findOneBy([
      'contract' => $contract
    ]);

    $output = [
      'contractId' => $contract->getId(),
      'message'    => 'To Analysis',
    ];

    try {
      $this->manager->getConnection()->beginTransaction();
      if ($order) {
        $order->setComments($error);
        $order->setStatus($this->manager->getRepository(Status::class)->findOneBy(['status' => 'analysis', 'context' => 'order']));
        $this->manager->persist($order);
      }

      $this->manager->persist($contract->setContractStatus('analysis'));
      $this->manager->flush();
      $this->manager->getConnection()->commit();

      $output['message'] = 'Contract was moved to Analysis!';
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }
      $output['message'] = $e->getMessage();
    }

    return $output;
  }

  private function updateContractStatusToActive(MyContract $contract): array
  {
    $output = [
      'contractId' => $contract->getId(),
      'message'    => 'OK',
    ];

    /**
     * SalesOrder
     *
     * @var SalesOrder
     */
    $order = $this->manager->getRepository(SalesOrder::class)->findOneBy([
      'contract' => $contract
    ]);

    try {

      $this->manager->getConnection()->beginTransaction();

      if ($order) {
        $order->setStatus($this->manager->getRepository(Status::class)->findOneBy(['status' => 'automatic analysis', 'context' => 'order']));
        $this->manager->persist($order);
      }

      $this->manager->persist($contract->setContractStatus('Waiting signatures'));
      $this->manager->flush();
      $this->manager->getConnection()->commit();

      $output['message'] = 'Contract was actived successfully!';
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }
      $output['message'] = $e->getMessage();
    }

    return $output;
  }
}
