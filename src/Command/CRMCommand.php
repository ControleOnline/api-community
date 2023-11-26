<?php

namespace App\Command;

use ControleOnline\Entity\Category;
use App\Entity\PeopleClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use App\Library\Utils\Formatter;
use App\Entity\PeopleEmployee;
use App\Service\MauticService;
use App\Entity\Task;
use App\Entity\People;
use App\Entity\PeopleSalesman;
use App\Entity\TaskInteration;
use ControleOnline\Entity\Status;
use App\Entity\SalesOrder;
use App\Repository\ConfigRepository;

class CRMCommand extends Command
{
  protected static $defaultName = 'app:crm';

  protected $em;

  protected $ma;

  protected $errors = [];

  private $payment = [];

  private $itau_configs = [];

  /**
   * Twig render
   *
   * @var \Twig\Environment
   */
  private $twig;

  /**
   * Config repository
   *
   * @var \App\Repository\ConfigRepository
   */
  private $config;

  public function __construct(EntityManagerInterface $entityManager, MauticService $mauticService, ConfigRepository $config, Environment $twig)
  {
    $this->em     = $entityManager;
    $this->ma     = $mauticService;
    $this->config = $config;
    $this->twig   = $twig;

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
    $targetName = $input->getArgument('target');
    $orderLimit = $input->getArgument('limit') ?: 10;

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

        $output->writeln([sprintf('      ID : #%s', $order->id)]);
        $output->writeln([sprintf('      SalesmanID : #%s', $order->saleamanId)]);
        $output->writeln([sprintf('      Salesman : %s', $order->salesman)]);
        $output->writeln([sprintf('      Company: %s', $order->company)]);
        $output->writeln([sprintf('      Client: %s', $order->client)]);
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

    return 0;
  }



  /**
   * Reabre as oportunidades pendentes na data que ela deve ser 
   */
  protected function getReopenOrders(int $limit): ?array
  {
    $orders = [];
    $oportunities = $this->em->getRepository(Task::class)
      ->createQueryBuilder('T')
      ->select()
      ->where('T.taskStatus = :taskStatus')
      ->andWhere('T.type = :taskType')
      ->andWhere('T.dueDate <= :dueDate')
      ->setParameters(array(
        'dueDate'  => (new \DateTime('today'))->format('Y-m-d'),
        'taskType' => 'relationship',
        'taskStatus' => $this->em->getRepository(Status::class)->findOneBy([
          'status' => 'pending',
          'context' => 'relationship'
        ])
      ))
      ->groupBy('T.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();


    foreach ($oportunities as $task) {

      $orders[] = (object) [
        'id'         => $task->getId(),
        'saleamanId'         => $task->getTaskFor()->getId(),
        'salesman'    => $task->getProvider()->getName(),
        'company'    => $task->getProvider()->getName(),
        'client'    => $task->getClient()->getName(),
        'subject'       => 'Generate oportunities',
        'notifier' => [
          'send' => function () use ($task) {
            try {

              $task->setTaskStatus(
                $this->em->getRepository(Status::class)->findOneBy([
                  'status' => 'open',
                  'context' => 'relationship'
                ])
              );

              $taskInteration = new TaskInteration();
              $taskInteration->setType('comment');
              $taskInteration->setVisibility('private');
              $taskInteration->setBody('Oportunidade pendente reaberta para novo contato');
              $taskInteration->setTask($task);
              $taskInteration->setRegisteredBy($task->getTaskFor());

              $this->em->persist($task);
              $this->em->persist($taskInteration);
              $this->em->flush();

              return true;
            } catch (\Exception $e) {
              echo  $e->getMessage();
              return false;
            }
          },
        ],
        'events'   => [
          'onError' => function () use ($oportunities) {
          },
          'onSuccess' => function () use ($oportunities) {
          },
        ],
      ];
    }


    return $orders;
  }



  /**
   * Reabre as oportunidades para clientes ativos
   */
  protected function getReopenActiveOrders(int $limit): ?array
  {
    $orders = [];
    $oportunities = $this->em->getRepository(Task::class)
      ->createQueryBuilder('T')
      ->select()
      ->innerJoin('\App\Entity\SalesOrder', 'O', 'WITH', 'O.client = T.client')
      ->where('T.taskStatus IN (:taskStatus)')
      ->andWhere('O.status IN(:oStatus)')
      ->andWhere('T.type = :taskType')
      ->andWhere('T.dueDate <= :dueDate')
      ->setParameters(array(
        'dueDate'  => (new \DateTime('today'))->modify('-3 month')->format('Y-m-d'),
        'taskType' => 'relationship',
        'oStatus' => $this->em->getRepository(Status::class)->findBy([
          'realStatus' => ['closed'],
          'context' => 'order'
        ]),
        'taskStatus' => $this->em->getRepository(Status::class)->findOneBy([
          'status' => ['closed'],
          'context' => 'relationship'
        ])
      ))
      ->groupBy('T.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();


    foreach ($oportunities as $task) {

      $orders[] = (object) [
        'id'         => $task->getId(),
        'saleamanId'         => $task->getTaskFor()->getId(),
        'salesman'    => $task->getProvider()->getName(),
        'company'    => $task->getProvider()->getName(),
        'client'    => $task->getClient()->getName(),
        'subject'       => 'Generate oportunities',
        'notifier' => [
          'send' => function () use ($task) {
            try {

              $task->setTaskStatus(
                $this->em->getRepository(Status::class)->findOneBy([
                  'status' => 'open',
                  'context' => 'relationship'
                ])
              );

              $taskInteration = new TaskInteration();
              $taskInteration->setType('comment');
              $taskInteration->setVisibility('private');
              $taskInteration->setBody('Oportunidade reaberta para manter o relacionamento com o cliente ativo');
              $taskInteration->setTask($task);
              $taskInteration->setRegisteredBy($task->getTaskFor());

              $this->em->persist($task);
              $this->em->persist($taskInteration);
              $this->em->flush();
              return true;
            } catch (\Exception $e) {
              echo  $e->getMessage();
              return false;
            }
          },
        ],
        'events'   => [
          'onError' => function () use ($oportunities) {
          },
          'onSuccess' => function () use ($oportunities) {
          },
        ],
      ];
    }


    return $orders;
  }


  /**
   * Cria as oportunidades para clientes e leads novos
   */
  protected function getGenerateOrders(int $limit): ?array
  {

    $orders = [];

    $salesmans = $this->em->getRepository(PeopleSalesman::class)
      ->createQueryBuilder('PS')
      ->select()
      ->leftJoin('\App\Entity\Task', 'T', 'WITH', 'PS.salesman = T.taskFor AND T.provider = PS.company AND T.type = :taskType      
      AND T.taskStatus = :taskStatus
      ')
      ->where('PS.salesman_type = :salesman_type')
      //->having('COUNT(T.id) <= :limit')
      ->setParameters(array(
        'salesman_type' => 'salesman',
        //'limit' => $limit,
        'taskType' => 'relationship',
        'taskStatus' => $this->em->getRepository(Status::class)->findOneBy([
          'status' => 'open',
          'context' => 'relationship'
        ])
      ))
      ->groupBy('PS.id')
      ->getQuery()
      ->getResult();


    foreach ($salesmans as $salesman) {
      $oportunities = $this->em->getRepository(PeopleClient::class)
        ->createQueryBuilder('PC')
        ->select()
        ->leftJoin('\App\Entity\Task', 'T', 'WITH', 'PC.client = T.client AND T.provider = PC.company_id AND T.type = :taskType')
        ->innerJoin('\App\Entity\People', 'P', 'WITH', 'PC.client = P.id')
        ->where('PC.company_id = :company_id')
        ->andWhere('P.peopleType IN(:peopleType)')
        ->having('COUNT(T.id) = 0')
        ->groupBy('PC.id')
        ->setParameters(array(
          'taskType' => 'relationship',
          'company_id' => $salesman->getCompany()->getId(),
          'peopleType' => ['J']
        ))
        ->setMaxResults(1)
        ->getQuery()
        ->getResult();

      $category = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Indentificar', 'context' => 'relationship', 'company' => $salesman->getCompany()]);
      if (!$category) {
        $category = new Category();
        $category->setName('Indentificar');
        $category->setCompany($salesman->getCompany());
        $category->setContext('relationship');
        $this->em->persist($category);
        $this->em->flush();
      }


      $reason = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Novo', 'context' => 'relationship', 'company' => $salesman->getCompany()]);
      if (!$reason) {
        $reason = new Category();
        $reason->setName('Novo');
        $reason->setCompany($salesman->getCompany());
        $reason->setContext('relationship');
        $this->em->persist($reason);
        $this->em->flush();
      }

      foreach ($oportunities as $oportunitie) {

        $orders[] = (object) [
          'id'              => null,
          'saleamanId'      => $salesman->getId(),
          'salesman'        => $salesman->getSalesman()->getName(),
          'company'         => $salesman->getCompany()->getName(),
          'client'          => $oportunitie->getClient()->getName(),
          'subject'         => 'Generate oportunities',
          'notifier' => [
            'send' => function () use ($oportunitie, $salesman, $category, $reason) {
              try {

                $client = $oportunitie->getClient();

                $hasTask = $this->em->getRepository(Task::class)->findOneBy(
                  [
                    'client' => $client,
                    'provider' => $salesman->getCompany(),
                    'type' => 'relationship'
                  ]
                );

                if (!$hasTask) {

                  $task = new Task();
                  $task->setType('relationship');
                  $task->setClient($client);
                  $task->setDueDate(new \DateTime('now'));
                  $task->setProvider($salesman->getCompany());



                  $task->setRegisteredBy($salesman->getSalesman());
                  $task->setTaskFor($salesman->getSalesman());
                  $task->setCategory($category);
                  $task->setReason($reason);
                  $task->setName('[Automático] - Nova oportunidade');
                  $task->setTaskStatus(
                    $this->em->getRepository(Status::class)->findOneBy([
                      'status' => 'open',
                      'context' => 'relationship'
                    ])
                  );

                  $taskInteration = new TaskInteration();
                  $taskInteration->setType('comment');
                  $taskInteration->setVisibility('private');
                  $taskInteration->setBody('Lead ainda não contactado identificado pelo sistema');
                  $taskInteration->setTask($task);
                  $taskInteration->setRegisteredBy($salesman->getSalesman());

                  $this->em->persist($task);
                  $this->em->persist($taskInteration);
                  $this->em->flush();
                }
                return true;
              } catch (\Exception $e) {
                echo  $e->getMessage();
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($salesman) {
            },
            'onSuccess' => function () use ($salesman) {
            },
          ],
        ];
      }
    }
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('SO')
      ->select()
      ->leftJoin('\App\Entity\Task', 'T', 'WITH', 'SO.client = T.client AND T.provider = SO.provider AND T.type = :taskType AND T.client IS NOT NULL')
      ->andWhere('SO.client IS NOT NULL')
      ->andWhere('SO.orderType = :orderType')
      ->andWhere('SO.status IN (:status)')
      ->having('COUNT(T.id) = 0')
      ->orderBy('SO.orderDate', 'DESC')
      ->groupBy('SO.client')
      ->setParameters(array(
        'orderType' => 'sale',
        'taskType' => 'relationship',
        'status' => $this->em->getRepository(Status::class)->findBy([
          'status' => 'delivered',
          'context' => 'order'
        ])
      ))
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    foreach ($salesOrders as $oportunitie) {
      $orders[] = (object) [
        'id'              => null,
        'saleamanId'      => $oportunitie->getProvider()->getId(),
        'salesman'        => $oportunitie->getProvider()->getName(),
        'company'         => $oportunitie->getProvider()->getName(),
        'client'          => $oportunitie->getClient()->getName(),
        'subject'         => 'Generate oportunities',
        'notifier' => [
          'send' => function () use ($oportunitie) {
            try {

              $client = $oportunitie->getClient();

              $hasTask = $this->em->getRepository(Task::class)->findOneBy(
                [
                  'client' => $client,
                  'provider' => $oportunitie->getProvider(),
                  'type' => 'relationship'
                ]
              );

              if (!$hasTask) {

                $task = new Task();
                $task->setType('relationship');
                $task->setClient($client);

                $task->setDueDate($oportunitie->getOrderDate()->modify('+3 month'));

                //$task->setDueDate((new \DateTime('now'))->modify('+3 month'));
                $task->setProvider($oportunitie->getProvider());

                $category = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Indentificar', 'context' => 'relationship', 'company' => $oportunitie->getProvider()]);
                if (!$category) {
                  $category = new Category();
                  $category->setName('Indentificar');
                  $category->setCompany($oportunitie->getProvider());
                  $category->setContext('relationship');
                  $this->em->persist($category);
                  $this->em->flush();
                }

                $reason = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Relacionamento', 'context' => 'relationship', 'company' => $oportunitie->getProvider()]);
                if (!$reason) {
                  $reason = new Category();
                  $reason->setName('Relacionamento');
                  $reason->setCompany($oportunitie->getProvider());
                  $reason->setContext('relationship');
                  $this->em->persist($reason);
                  $this->em->flush();
                }

                $task->setRegisteredBy($oportunitie->getProvider());
                $task->setTaskFor($oportunitie->getProvider());
                $task->setCategory($category);
                $task->setReason($reason);
                $task->setName('[Automático] - Relacionamento');
                $task->setTaskStatus(
                  $this->em->getRepository(Status::class)->findOneBy([
                    'status' => 'pending',
                    'context' => 'relationship'
                  ])
                );

                $taskInteration = new TaskInteration();
                $taskInteration->setType('comment');
                $taskInteration->setVisibility('private');
                $taskInteration->setBody('Cliente ativo. Manter contato periódico.');
                $taskInteration->setTask($task);
                $taskInteration->setRegisteredBy($oportunitie->getProvider());

                $this->em->persist($task);
                $this->em->persist($taskInteration);
                $this->em->flush();
              }
              return true;
            } catch (\Exception $e) {
              echo  $e->getMessage();
              return false;
            }
          },
        ],
        'events'   => [
          'onError' => function () use ($salesman) {
          },
          'onSuccess' => function () use ($salesman) {
          },
        ],
      ];
    }



    $quotes = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('SO')
      ->select()
      ->leftJoin('\App\Entity\Task', 'T', 'WITH', 'SO.client = T.client AND T.provider = SO.provider AND T.type = :taskType AND T.client IS NOT NULL')
      ->leftJoin('\App\Entity\PeopleEmployee', 'PE', 'WITH', 'PE.employee = SO.client')
      ->andWhere('SO.client IS NOT NULL')
      ->andWhere('SO.orderType = :orderType')
      ->andWhere('SO.status IN (:status)')
      ->having('COUNT(T.id) = 0')
      ->andHaving('COUNT(PE.id) = 0')
      ->orderBy('SO.orderDate', 'DESC')
      ->groupBy('SO.client')
      ->setParameters(array(
        'orderType' => 'sale',
        'taskType' => 'relationship',
        'status' => $this->em->getRepository(Status::class)->findBy([
          'status' => 'quote',
          'context' => 'order'
        ])
      ))
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    foreach ($quotes as $oportunitie) {
      $orders[] = (object) [
        'id'              => null,
        'saleamanId'      => $oportunitie->getProvider()->getId(),
        'salesman'        => $oportunitie->getProvider()->getName(),
        'company'         => $oportunitie->getProvider()->getName(),
        'client'          => $oportunitie->getClient()->getName(),
        'subject'         => 'Generate oportunities',
        'notifier' => [
          'send' => function () use ($oportunitie) {
            try {

              $client = $oportunitie->getClient();

              $hasTask = $this->em->getRepository(Task::class)->findOneBy(
                [
                  'client' => $client,
                  'provider' => $oportunitie->getProvider(),
                  'type' => 'relationship'
                ]
              );

              if (!$hasTask) {

                $task = new Task();
                $task->setType('relationship');
                $task->setClient($client);
                $task->setDueDate($oportunitie->getOrderDate());
                $task->setProvider($oportunitie->getProvider());

                $category = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Indentificar', 'context' => 'relationship', 'company' => $oportunitie->getProvider()]);
                if (!$category) {
                  $category = new Category();
                  $category->setCompany($oportunitie->getProvider());
                  $category->setName('Indentificar');
                  $category->setContext('relationship');
                  $this->em->persist($category);
                  $this->em->flush();
                }


                $reason = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Novo', 'context' => 'relationship', 'company' => $oportunitie->getProvider()]);
                if (!$reason) {
                  $reason = new Category();
                  $reason->setName('Novo');
                  $reason->setCompany($oportunitie->getProvider());
                  $reason->setContext('relationship');
                  $this->em->persist($reason);
                  $this->em->flush();
                }

                $task->setRegisteredBy($oportunitie->getProvider());
                $task->setTaskFor($oportunitie->getProvider());
                $task->setCategory($category);
                $task->setReason($reason);
                $task->setName('[Automático] - Cotação realizada por um Prospect');
                $task->setTaskStatus(
                  $this->em->getRepository(Status::class)->findOneBy([
                    'status' => 'open',
                    'context' => 'relationship'
                  ])
                );

                $taskInteration = new TaskInteration();
                $taskInteration->setType('comment');
                $taskInteration->setVisibility('private');
                $taskInteration->setBody('Cliente novo criou uma cotação. Favor entrar em contato para realizar o Welcome Call.');
                $taskInteration->setTask($task);
                $taskInteration->setRegisteredBy($oportunitie->getProvider());

                $this->em->persist($task);
                $this->em->persist($taskInteration);
                $this->em->flush();
              }
              return true;
            } catch (\Exception $e) {
              echo  $e->getMessage();
              return false;
            }
          },
        ],
        'events'   => [
          'onError' => function () use ($salesman) {
          },
          'onSuccess' => function () use ($salesman) {
          },
        ],
      ];
    }

    return $orders;
  }
}
