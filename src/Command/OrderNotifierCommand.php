<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use App\Library\Utils\Formatter;
use Doctrine\ORM\Query\ResultSetMapping;
use App\Service\SignatureService;
use App\Service\MauticService;
use App\Service\EmailService;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\ReceiveInvoice;
use ControleOnline\Entity\PayInvoice;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\PurchasingOrder;
use ControleOnline\Entity\SalesOrderInvoice;
use ControleOnline\Entity\PurchasingInvoiceTax;
use ControleOnline\Entity\SalesInvoiceTax;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\PurchasingOrderInvoiceTax;
use ControleOnline\Entity\SalesOrderInvoiceTax;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\PeopleSalesman;
use ControleOnline\Entity\PeopleClient;
use ControleOnline\Repository\ConfigRepository;
use App\Library\Itau\ItauClient;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\Contract;
use ControleOnline\Entity\File;
use ControleOnline\Entity\Import;
use ControleOnline\Entity\Quotation;
use ControleOnline\Entity\Task;
use ControleOnline\Entity\Category;
use ControleOnline\Entity\OrderLogistic;
use ControleOnline\Entity\TaskInteration;
use ControleOnline\Service\DatabaseSwitchService;

use DateTime;

class OrderNotifierCommand extends Command
{
  protected static $defaultName = 'app:order-notifier';

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
   * @var \ControleOnline\Repository\ConfigRepository
   */
  private $config;

  /**
   * Entity manager
   *
   * @var DatabaseSwitchService
   */
  private $databaseSwitchService;

  /**
   * Config repository
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private $output;

  public function __construct(EntityManagerInterface $entityManager, MauticService $mauticService, ConfigRepository $config, Environment $twig, DatabaseSwitchService $databaseSwitchService)
  {
    $this->em     = $entityManager;
    $this->ma     = $mauticService;
    $this->config = $config;
    $this->twig   = $twig;
    $this->errors = [];
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
    $this->addArgument('datelimit', InputArgument::OPTIONAL, 'Limit of date to process');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $domains = $this->databaseSwitchService->getAllDomains();
    foreach ($domains as $domain) {
      $this->databaseSwitchService->switchDatabaseByDomain($domain);

      $this->output = $output;

      $targetName = $input->getArgument('target');
      $orderLimit = $input->getArgument('limit') ?: 100;
      $dateLimit = $input->getArgument('datelimit') ?: 15;

      $getOrders  = 'get' . str_replace('_', '', ucwords(strtolower($targetName), '_')) . 'Orders';
      if (method_exists($this, $getOrders) === false)
        throw new \Exception(sprintf('Notification target "%s" is not defined', $targetName));

      $this->output->writeln([
        '',
        '=========================================',
        sprintf('Notification target: %s', $targetName),
        '=========================================',
        sprintf('Rows to process: %d', $orderLimit),
        '',
      ]);

      // get orders

      $orders = $this->$getOrders($orderLimit, $dateLimit);

      if (!empty($orders)) {
        foreach ($orders as $order) {

          // start notifications...

          $this->output->writeln([sprintf('      OrderID : #%s', $order->order)]);
          $this->output->writeln([sprintf('      Carrier : %s', $order->carrier)]);
          $this->output->writeln([sprintf('      Provider: %s', $order->company)]);
          $this->output->writeln([sprintf('      Receiver: %s', $order->receiver)]);
          $this->output->writeln([sprintf('      Subject : %s', $order->subject)]);

          $result = $order->notifier['send']();

          if (is_bool($result)) {
            $order->events[$result === true ? 'onSuccess' : 'onError']();
          } else {
            if ($result === null) {
              $this->output->writeln(['      Error   : send method internal error']);
            }
          }

          $this->output->writeln(['']);
        }
      } else
        $this->output->writeln('      There is no pending orders.');

      $this->output->writeln([
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
   * Cria a invoice do pedido
   */
  private function getCreateInvoiceOrders(int $limit, int $datelimit = null): ?array
  {
    return null;
  }



  /**
   * Marca o pedido como coletado
   */
  private function getRetrievedOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\OrderTracking', 'T', 'WITH', 'T.order = O.id')
      ->where('O.status IN(:status)')
      ->andWhere('T.ocorrencia LIKE :ocorrencia')
      ->setParameters(array(
        'status'    => $this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting retrieve', 'context' => 'order']),
        'ocorrencia' => '%MERCADORIA%',
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Order delivered',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order = $this->em->find(SalesOrder::class, $order);
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'retrieved', 'context' => 'order']));
              $order->setNotified(0);
              $this->em->persist($order);
              $this->em->flush();
            },
          ],
        ];
      }
    }
    return $orders;
  }
  /**
   * Marca o pedido como entregue
   */

  private function getAutoCloseOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\Quotation', 'Q', 'WITH', 'Q.order = O.id')
      ->where('O.status IN(:status)')
      ->andWhere('O.alterDate < DATE_SUB(CURRENT_DATE(),(Q.deadline+10), \'day\')')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'on the way', 'context' => 'order']),
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Order delivered',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order = $this->em->find(SalesOrder::class, $order);
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'delivered', 'context' => 'order']));
              $order->setNotified(0);
              $this->em->persist($order);
              $this->em->flush();
            },
          ],
        ];
      }
    }
    return $orders;
  }

  private function getCloseOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\OrderTracking', 'T', 'WITH', 'T.order = O.id')
      ->where('O.status IN(:status)')
      ->andWhere('T.ocorrencia LIKE :ocorrencia OR T.ocorrencia LIKE :ocorrencias')
      ->setParameters(array(
        'status'    => $this->em->getRepository(Status::class)->findOneBy(['status' => 'on the way', 'context' => 'order']),
        'ocorrencia' => '%MERCADORIA ENTREGUE%',
        'ocorrencias' => '%ENTREGAS REALIZADAS%',
      ))
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Order delivered',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order = $this->em->find(SalesOrder::class, $order);
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'delivered', 'context' => 'order']));
              $order->setNotified(0);
              $this->em->persist($order);
              $this->em->flush();
            },
          ],
        ];
      }
    }
    return $orders;
  }

  /**
   * Cancela cotações que não foram fechadas em 20 dias
   */
  private function getCancelPendingOrders(int $limit, int $datelimit = 20): ?array
  {

    $qry = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->where('O.status IN(:status)')
      ->andWhere('O.alterDate < :alter_date')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting client invoice tax', 'context' => 'order']),
        'alter_date' => date('Y-m-d', strtotime('-' . $datelimit . ' days'))
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery();
    $salesOrders = $qry->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient() ? $order->getClient()->getName() : null,
          'subject'  => 'Cancel order',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order = $this->em->find(SalesOrder::class, $order);
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'expired', 'context' => 'order']));
              $order->setNotified(0);
              $this->em->persist($order);
              $this->em->flush();
            },
          ],
        ];
      }
    }
    return $orders;
  }




  /**
   * Cria as invoices da logística
   */
  private function getCreateLogisticInvoiceOrders(int $limit = 10, int $datelimit = 20): ?array
  {

    $qry = $this->em->getRepository(OrderLogistic::class)
      ->createQueryBuilder('OL')
      ->select()
      ->where('OL.status IN(:status)')
      ->andWhere('OL.purchasing_order IS NULL')
      ->andWhere('OL.provider IS NOT NULL')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['realStatus' => 'closed', 'context' => 'logistic']),
      ))
      ->groupBy('OL.id')
      ->setMaxResults($limit)
      ->getQuery();


    $OrderLogistic = $qry->getResult();


    if (count($OrderLogistic) == 0)
      return null;
    else {
      foreach ($OrderLogistic as $logistic) {
        $order = $logistic->getOrder();
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient() ? $order->getClient()->getName() : null,
          'subject'  => 'Create logistic order',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order, $logistic) {
            },
            'onSuccess' => function () use ($order, $logistic) {
              try {

                $logisticOrder = clone $order;
                $this->em->detach($logisticOrder);
                $logisticOrder->resetId();
                $logisticOrder->setOrderType('purchase');
                $logisticOrder->setMainOrder($order);
                $logisticOrder->setClient($order->getProvider());
                $logisticOrder->setPayer($order->getProvider());
                $logisticOrder->setProvider($logistic->getProvider());
                $logisticOrder->setPrice($logistic->getAmountPaid());
                $logisticOrder->setParkingDate($order->getParkingDate());
                $this->em->persist($logisticOrder);
                $this->em->flush($logisticOrder);

                $logistic->setPurchasingOrder($logisticOrder);
                $this->em->persist($logistic);
                $this->em->flush($logistic);



                $invoice = new ReceiveInvoice();
                $invoice->setPrice($order->getPrice());
                $invoice->setDueDate($this->getDueDate($order->getClient()));
                $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['waiting payment'], 'context' => 'invoice']));
                $invoice->setNotified(0);
                $invoice->setDescription('Frete');
                $invoice->setCategory(
                  $this->em->getRepository(Category::class)->findOneBy([
                    'context'  => 'expense',
                    'name'    => 'Frete',
                    'company' => [$order->getProvider(), $order->getClient()]
                  ])
                );

                $orderInvoice = new SalesOrderInvoice();
                $orderInvoice->setInvoice($invoice);
                $orderInvoice->setOrder($logisticOrder);
                $orderInvoice->setRealPrice($logisticOrder->getPrice());

                $invoice->addOrder($orderInvoice);

                $this->em->persist($invoice);
                $this->em->flush($invoice);

                $this->em->persist($orderInvoice);
                $this->em->flush($orderInvoice);
              } catch (\Exception $e) {
                echo $e->getMessage();
              }
            },
          ],
        ];
      }
    }
    return $orders;
  }



  private function getPutOnTheWayOrders(int $limit, int $datelimit = 20): ?array
  {

    $qry = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoiceTax', 'SOI', 'WITH', 'SOI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\SalesInvoiceTax', 'RI', 'WITH', 'SOI.invoiceTax = RI.id AND SOI.invoiceType = 57')
      ->where('O.status IN (:status)')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['retrieved', 'waiting retrieve'], 'context' => 'order'])
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery();

    $salesOrders = $qry->getResult();
    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient() ? $order->getClient()->getName() : null,
          'subject'  => 'Cancel Invoice',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order = $this->em->find(SalesOrder::class, $order);
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['on the way'], 'context' => 'order']));
              $this->em->persist($order);
              $this->em->flush();
            },
          ],
        ];
      }
    }


    return $orders;
  }



  private function getRemoveCanceledInvoicesOrders(int $limit, int $datelimit = 20): ?array
  {

    $qry = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'SOI', 'WITH', 'SOI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\ReceiveInvoice', 'RI', 'WITH', 'SOI.invoice = RI.id AND RI.status NOT IN (:istatus)')
      ->where('O.status IN (:status)')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['expired', 'canceled'], 'context' => 'order']),
        'istatus' => $this->em->getRepository(Status::class)->findBy(['status' => ['canceled', 'paid'], 'context' => 'invoice']),
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery();

    $salesOrders = $qry->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient() ? $order->getClient()->getName() : null,
          'subject'  => 'Cancel Invoice',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order = $this->em->find(SalesOrder::class, $order);
              foreach ($order->getInvoice() as $invoice) {
                if (count($invoice->getInvoice()->getOrder()) > 1) {
                  $this->recalculateInvoicePrice($invoice->getInvoice());
                  $this->em->remove($invoice);
                  $this->em->flush();
                } else {
                  $i = $invoice->getInvoice();
                  $i->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'canceled', 'context' => 'invoice']));
                  $this->em->persist($i);
                  $this->em->flush();
                }
              }
            },
          ],
        ];
      }
    }
    return $orders;
  }


  /**
   * Cancela cotações que não foram fechadas em 15 dias
   */
  private function getCancelQuotesOrders(int $limit, int $datelimit = 15): ?array
  {
    $qry = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->where('O.status IN(:status)')
      ->andWhere('O.alterDate < :alter_date')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'quote', 'context' => 'order']),
        'alter_date' => date('Y-m-d', strtotime('-' . $datelimit . ' days'))
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery();
    $salesOrders = $qry->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient() ? $order->getClient()->getName() : null,
          'subject'  => 'Cancel order',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order = $this->em->find(SalesOrder::class, $order);
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'expired', 'context' => 'order']));
              $order->setNotified(0);
              $this->em->persist($order);
              $this->em->flush();
            },
          ],
        ];
      }
    }
    return $orders;
  }


  public function getRuralOrders(int $limit = 10, int $datelimit = null)
  {

    $words = [
      'Estrada',
      'Rodovia',
      'Fazenda',
      'Sítio',
      'Sitio',
      'Zona Rural',
      'Loteamento',
      'Rodovia'
    ];

    $searchO = '';
    $searchD = '';

    foreach ($words as $word) {
      $searchO .= ' OS.street LIKE "%' . $word . '%" OR O.complement LIKE "%' . $word . '%" OR OSD.district LIKE "%' . $word . '%" OR ';
      $searchD .= ' DS.street LIKE "%' . $word . '%" OR D.complement LIKE "%' . $word . '%" OR DSD.district LIKE "%' . $word . '%" OR';
    }

    $sql = 'SELECT orders.id,JSON_EXTRACT(JSON_UNQUOTE(orders.other_informations), "$.rural")  FROM `orders` 
            INNER JOIN address  O   ON (O.id = address_origin_id)
            INNER JOIN street   OS  ON (OS.id = O.street_id)
            INNER JOIN district OSD ON (OSD.id = OS.district_id)            
            INNER JOIN address  D   ON (D.id   = address_destination_id)
            INNER JOIN street   DS  ON (DS.id  = D.street_id)
            INNER JOIN district DSD ON (DSD.id = DS.district_id)
            WHERE 
            (
             ' . $searchO . '                                         
             ' . substr($searchD, 0, strlen($searchD) - 2) . '
            )
            AND 
            JSON_EXTRACT(JSON_UNQUOTE(orders.other_informations), "$.rural")  IS NULL                          
            LIMIT :limit';

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('id', 'id', 'integer');
    $nqu = $this->em->createNativeQuery($sql, $rsm);
    $nqu->setParameter('limit', $limit);
    $result = $nqu->getArrayResult();
    $orders = [];

    foreach ($result as $r) {

      $order = $this->em->getRepository(SalesOrder::class)->find($r['id']);
      $orders[] = (object) [
        'order'    => $order->getId(),
        'carrier'  => '',
        'company'  => $order->getProvider()->getName(),
        'receiver' => '',
        'subject'  => 'Rural area',
        'notifier' => [
          'send' => function () use ($order) {
            try {
              $order->addOtherInformations('rural', 'process');
              $this->em->persist($order);
              $this->em->flush();
              return true;
            } catch (\Exception $e) {
              echo $e->getMessage();
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

    return $orders;
  }



  public function getDificultOrders(int $limit = 10, int $datelimit = null)
  {

    $words = [
      'Petrobras',
      'Supermercado',
      'Mercado',
      'Atacadista',
      'Exercito',
      'Penitenciaria',
      'Distribuidora'
    ];

    $searchO = '';
    $searchD = '';

    foreach ($words as $word) {
      $searchO .= ' PO.name LIKE "%' . $word . '%" OR PO.alias LIKE "%' . $word . '%" OR ';
      $searchD .= ' PD.name LIKE "%' . $word . '%" OR PD.alias LIKE "%' . $word . '%" OR';
    }

    $sql = 'SELECT orders.id,JSON_EXTRACT(JSON_UNQUOTE(orders.other_informations), "$.dificult")  FROM `orders`             
            INNER JOIN people  PO    ON (PO.id = retrieve_people_id)
            INNER JOIN people  PD    ON (PD.id = delivery_people_id)
            WHERE
            (
             ' . $searchO . '                                         
             ' . substr($searchD, 0, strlen($searchD) - 2) . '
            )
            AND
            JSON_EXTRACT(JSON_UNQUOTE(orders.other_informations), "$.dificult")  IS NULL
            LIMIT :limit';

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('id', 'id', 'integer');
    $nqu = $this->em->createNativeQuery($sql, $rsm);
    $nqu->setParameter('limit', $limit);
    $result = $nqu->getArrayResult();
    $orders = [];

    foreach ($result as $r) {

      $order = $this->em->getRepository(SalesOrder::class)->find($r['id']);
      $orders[] = (object) [
        'order'    => $order->getId(),
        'carrier'  => '',
        'company'  => $order->getProvider()->getName(),
        'receiver' => '',
        'subject'  => 'Dificult area',
        'notifier' => [
          'send' => function () use ($order) {
            try {
              $order->addOtherInformations('dificult', 'process');
              $this->em->persist($order);
              $this->em->flush();
              return true;
            } catch (\Exception $e) {
              echo $e->getMessage();
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

    return $orders;
  }


  private function Analysis($order)
  {
    $nf = $this->em->getRepository(PurchasingOrderInvoiceTax::class)
      ->findOneBy([
        'invoiceType' => '55',
        'order' => $order
      ]);
    $xml = simplexml_load_string(
      $nf->getInvoiceTax()->getInvoice()
    );


    if ($xml) {
      $transporte = $xml->NFe->infNFe->transp;
      if ($transporte->modFrete != 2) {
        $errors[] = 'Freight payer field must be 2';
      }

      $cnpj = $this->em->getRepository(Document::class)->findOneBy([
        'documentType' => 3, // 3 = CNPJ
        'people' => $order->getProvider()
      ])->getDocument();

      if (!$cnpj || mb_strpos(number_format(0 + $xml->NFe->infNFe->infAdic->infCpl, 0, '', ''), $cnpj) !== false) {
        $errors[] = 'Additional information is required';
      }
      $valor_frete = number_format(0 + $xml->NFe->infNFe->det->prod->vFrete, 2, '.', '');
      if ($valor_frete > 0 && $valor_frete != number_format(0 + $order->getPrice(), 2, '.', '')) {
        $errors[] = 'Freight value entered on invoice is incorrect';
      }
      if ($xml->NFe->infNFe->total->ICMSTot->vProd < $order->getInvoiceTotal()) {
        $errors[] = 'Invoice tax value entered is incorrect';
      }
      if ($xml->NFe->infNFe->transp->vol->pesoB > $order->getCubage()) {
        $errors[] = 'The weight informed on the invoice is greater than the quotation';
      }
      $emitente = $xml->NFe->infNFe->emit;
      if (!$order->getAddressOrigin() || number_format(0 + $emitente->enderEmit->CEP, 0, '', '') != $order->getAddressOrigin()->getStreet()->getCep()->getCep()) {
        $errors[] = 'CEP origin entered is divergent of quote';
      }
      if (!$order->getAddressOrigin() || $this->normalizeString($emitente->enderEmit->xLgr) != $this->normalizeString($order->getAddressOrigin()->getStreet()->getStreet())) {
        $errors[] = 'Street of address origin entered is divergent of quote';
      }
      if (!$order->getAddressOrigin() || $this->normalizeString($emitente->enderEmit->nro) != $this->normalizeString($order->getAddressOrigin()->getNumber())) {
        $errors[] = 'Number of address origin entered is divergent of quote';
      }
      if (!$order->getAddressOrigin() || $this->normalizeString($emitente->enderEmit->xBairro) != $this->normalizeString($order->getAddressOrigin()->getStreet()->getDistrict()->getDistrict())) {
        $errors[] = 'Number of address origin entered is divergent of quote';
      }
      if (!$order->getAddressOrigin() || $this->normalizeString($emitente->enderEmit->xMun) != $this->normalizeString($order->getAddressOrigin()->getStreet()->getDistrict()->getCity()->getCity())) {
        $errors[] = 'City origin entered is divergent of quote';
      }
      if (!$order->getAddressOrigin() || $this->normalizeString($emitente->enderEmit->UF) != $this->normalizeString($order->getAddressOrigin()->getStreet()->getDistrict()->getCity()->getState()->getUf())) {
        $errors[] = 'UF origin entered is divergent of quote';
      }
      $destinatario = $xml->NFe->infNFe->dest;
      if (!$order->getAddressDestination() || number_format(0 + $destinatario->enderDest->CEP, 0, '', '') != $order->getAddressDestination()->getStreet()->getCep()->getCep()) {
        $errors[] = 'CEP destination entered is divergent of quote';
      }
      if (!$order->getAddressDestination() || $this->normalizeString($destinatario->enderDest->xLgr) != $this->normalizeString($order->getAddressDestination()->getStreet()->getStreet())) {
        $errors[] = 'Street of address destination entered is divergent of quote';
      }
      if (!$order->getAddressDestination() || $this->normalizeString($destinatario->enderDest->nro) != $this->normalizeString($order->getAddressDestination()->getNumber())) {
        $errors[] = 'Number of address destination entered is divergent of quote';
      }
      if (!$order->getAddressDestination() || $this->normalizeString($destinatario->enderDest->xBairro) != $this->normalizeString($order->getAddressDestination()->getStreet()->getDistrict()->getDistrict())) {
        $errors[] = 'Number of address destination entered is divergent of quote';
      }
      if (!$order->getAddressDestination() || $this->normalizeString($destinatario->enderDest->xMun) != $this->normalizeString($order->getAddressDestination()->getStreet()->getDistrict()->getCity()->getCity())) {
        $errors[] = 'City destination entered is divergent of quote';
      }
      if (!$order->getAddressDestination() || $this->normalizeString($destinatario->enderDest->UF) != $this->normalizeString($order->getAddressDestination()->getStreet()->getDistrict()->getCity()->getState()->getUf())) {
        $errors[] = 'UF destination entered is divergent of quote';
      }
    } else {
      $errors[] = 'Error reading invoice XML';
    }

    $transportadora = $xml->NFe->infNFe->transp->transporta;
    $cnpj_transportadora = $this->em->getRepository(Document::class)->findOneBy([
      'documentType' => 3, // 3 = CNPJ
      'people' => $order->getQuote()->getCarrier()
    ])->getDocument();

    if (!$cnpj_transportadora || !preg_match('/' . $cnpj_transportadora . '/', number_format((string)$transportadora->CNPJ, 0, '', ''))) {
      $errors[] = 'Document of carrier is incorrect';
    }
    return $errors;
  }

  /**
   * Fecha a task quando o pedido sai de análise
   */

  private function getCloseAnalysisTasksOrders(int $limit, int $datelimit = null): ?array
  {

    $tasks = $this->em->getRepository(Task::class)
      ->createQueryBuilder('T')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'T.order = O.id')
      ->innerJoin('\ControleOnline\Entity\Category', 'C', 'WITH', 'T.category = C.id')
      ->where('O.status NOT IN (:status)')
      ->andWhere('T.taskStatus NOT IN (:taskStatus)')
      ->andWhere('C.name =:category')
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'analysis', 'context' => 'order']),
        'taskStatus' => $this->em->getRepository(Status::class)->findBy(['realStatus' => ['closed'], 'context' => 'support']),
        'category' => 'Aguardando documentação'
      ))
      ->getQuery()->getResult();


    if (count($tasks) == 0)
      return null;
    else {

      foreach ($tasks as $task) {

        $order  = $task->getOrder();

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Automatic analysis',
          'notifier' => [
            'send' => function () use ($task) {
              try {
                $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['closed'], 'context' => 'support']));

                /**
                 * @todo Ajustar para que este usuário seja pego automaticamente
                 * Por enquanto, adicionado manualmente o usuário da Luiza
                 */
                $defaultPeople = $this->em->getRepository(People::class)->find(24149);

                $taskInteration = new TaskInteration();
                $taskInteration->setTask($task);
                $taskInteration->setType('comment');
                $taskInteration->setRegisteredBy($defaultPeople);
                $taskInteration->setBody('Tarefa resolvida automaticamente pois o status do pedido não aguarda mais a aprovação');
                $taskInteration->setVisibility('private');

                $this->em->persist($task);
                $this->em->persist($taskInteration);
                $this->em->flush();

                return empty($errors);
              } catch (\Exception $e) {
                echo $e->getMessage();
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

  /**
   * Analisa a nota fiscal do pedido e aprova
   */
  private function getAutomaticAnalysisOrders(int $limit, int $datelimit = null): ?array
  {

    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->where('O.status IN(:status)')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'automatic analysis', 'context' => 'order']),
      ))
      ->orderBy('RAND()')
      ->setMaxResults($limit)
      ->getQuery()->getResult();


    if (count($salesOrders) == 0)
      return null;
    else {

      foreach ($salesOrders as $order) {

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Automatic analysis',
          'notifier' => [
            'send' => function () use ($order) {
              try {

                $errors = $this->Analysis($order);
                if ($errors) {
                  /**
                   * @todo Ajustar para que este usuário seja pego automaticamente
                   * Por enquanto, adicionado manualmente o usuário da Luiza
                   */
                  $defaultPeople = $this->em->getRepository(People::class)->find(24149);
                  $category = $this->em->getRepository(Category::class)->findOneBy(['name' => ['Aguardando documentação']]);


                  $task = $this->em->getRepository(Task::class)->findOneBy([
                    'category' => $category,
                    'order' => $order
                  ]);

                  if (!$task) {
                    $msg = '';
                    foreach ($errors as $error) {
                      $msg .= $error . PHP_EOL;
                    }
                    $task = new Task();
                    $task->setType('support');
                    $task->setClient($order->getClient());
                    $task->setDueDate(new \DateTime('now'));
                    $task->setOrder($order);
                    $task->setProvider($order->getProvider());

                    if (!$category) {
                      $category = new Category();
                      $category->setName('Aguardando documentação');
                      $category->setContext('support');
                      $this->em->persist($category);
                      $this->em->flush();
                    }
                    $task->setTaskFor($defaultPeople);
                    $task->setRegisteredBy($defaultPeople);
                    $task->setCategory($category);
                    $task->setName('[Automático] - Aguardando documentação');
                    $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'support']));

                    $taskInteration = new TaskInteration();
                    $taskInteration->setType('comment');
                    $taskInteration->setVisibility('private');
                    $taskInteration->setBody($msg);
                    $taskInteration->setTask($task);
                    $taskInteration->setRegisteredBy($defaultPeople);


                    $this->em->persist($task);
                    $this->em->persist($taskInteration);
                    $this->em->flush();
                  }

                  $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'analysis', 'context' => 'order']));
                  $order->setNotified(0);
                  $this->em->persist($order);
                  $this->em->flush();
                } else {
                  $order = $this->em->find(SalesOrder::class, $order);
                  $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting retrieve', 'context' => 'order']));
                  $order->setNotified(0);
                  $this->em->persist($order);
                  $this->em->flush();
                }


                return empty($errors);
              } catch (\Exception $e) {
                echo $e->getMessage();
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


  protected function normalizeString($string)
  {
    return trim(preg_replace("/[^a-zA-Z0-9\s]+/", "", preg_replace(array('/(á|à|ã|â|ä)/', '/(Á|À|Ã|Â|Ä)/', '/(é|è|ê|ë)/', '/(É|È|Ê|Ë)/', '/(í|ì|î|ï)/', '/(Í|Ì|Î|Ï)/', '/(ó|ò|õ|ô|ö)/', '/(Ó|Ò|Õ|Ô|Ö)/', '/(ú|ù|û|ü)/', '/(Ú|Ù|Û|Ü)/', '/(ñ)/', '/(Ñ)/', '/(ç)/', '/(Ç)/'), array('a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U', 'n', 'N', 'c', 'C'), $string)));
  }

  /**
   * Cria a comissão após o pedido ser entregue
   */
  protected function getGenerateCommissionOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->leftJoin('\ControleOnline\Entity\SalesOrder', 'CO', 'WITH', 'CO.mainOrder = O.id AND CO.orderType =:orderType')
      ->innerJoin('\ControleOnline\Entity\PurchasingOrder', 'PO', 'WITH', 'PO.mainOrder = O.id AND PO.orderType =:pOrderType')
      ->innerJoin('\ControleOnline\Entity\PeopleSalesman', 'PS', 'WITH', 'PS.company = O.provider')
      ->innerJoin('\ControleOnline\Entity\PeopleClient', 'PC', 'WITH', 'PC.company_id = PS.salesman AND PC.client = O.client AND PC.commission > 0')
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'SI', 'WITH', 'SI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\ReceiveInvoice', 'I', 'WITH', 'I.id = SI.invoice')
      ->where('O.status IN(:status)')
      ->andWhere('I.status IN(:istatus)')
      ->andWhere('CO.id IS NULL')
      ->setParameters(array(
        'istatus' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'paid', 'context' => 'invoice']),
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'delivered', 'context' => 'order']),
        'orderType' => 'comission',
        'pOrderType' => 'purchase'
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Generate commision',
          'notifier' => [
            'send' => function () use ($order) {
              try {

                $salesmans = $this->em->getRepository(PeopleSalesman::class)
                  ->createQueryBuilder('PS')
                  ->select()
                  ->innerJoin('\ControleOnline\Entity\PeopleClient', 'PC', 'WITH', 'PC.company_id = PS.salesman')
                  ->where('PS.company =:company')
                  ->andWhere('PC.client =:client')
                  ->setParameters(array(
                    'company' => $order->getProvider(),
                    'client' => $order->getClient()
                  ))
                  ->groupBy('PS.id')
                  ->getQuery()->getResult();


                if (count($salesmans) > 0) {

                  foreach ($salesmans as $salesman) {

                    $client = $this->em->getRepository(PeopleClient::class)
                      ->createQueryBuilder('PC')
                      ->select()
                      ->where('PC.company_id =:company')
                      ->andWhere('PC.client =:client')
                      ->setParameters(array(
                        'company' => $salesman->getSalesman(),
                        'client' => $order->getClient()
                      ))
                      ->getQuery()->getResult()[0];

                    $purchasingOrder = $this->em->getRepository(PurchasingOrder::class)->findOneBy([
                      'mainOrder' => $order,
                      'orderType' => 'purchase',
                    ]);



                    $price = ($order->getPrice() - $purchasingOrder->getPrice()) * $client->getCommission() / 100;
                    $commissionOrder = clone $order;
                    $this->em->detach($commissionOrder);
                    $commissionOrder->resetId();
                    $commissionOrder->setOrderType('comission');
                    $commissionOrder->setMainOrder($order);
                    $commissionOrder->setClient($order->getProvider());
                    $commissionOrder->setPayer($order->getProvider());
                    $commissionOrder->setProvider($salesman->getSalesman());
                    $commissionOrder->setPrice($price);
                    $this->em->persist($commissionOrder);
                    $this->em->flush($commissionOrder);
                  }
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


    /*
    $price = simplexml_load_string($invoice)->CTe->infCte->vPrest->vTPrest;
    if (!$price) {
        throw new \Exception('Impossible get price of DACTE', 103);
        return;
    }
    $purchasingOrder = clone $order;
    $this->em->detach($purchasingOrder);
    $purchasingOrder->resetId();
    $purchasingOrder->setOrderType('purchase');
    $purchasingOrder->setMainOrder($order);
    $purchasingOrder->setClient($order->getQuote()->getProvider());
    $purchasingOrder->setPayer($order->getQuote()->getProvider());
    $purchasingOrder->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'delivered','context' => 'order']));
    $purchasingOrder->setProvider($provider);
    $purchasingOrder->setPrice($price);
    $this->em->persist($purchasingOrder);
    $this->em->flush($purchasingOrder);

    $carrierInvoiceTax = $this->em->getRepository(PurchasingOrderInvoiceTax::class)->findOneBy([
      'order' => $order,
      'invoice_type' => '57',
    ]);

    if (!$carrierInvoiceTax) {

        $carrierInvoiceTax = new \ControleOnline\Entity\InvoiceTax();
        $carrierInvoiceTax->setInvoice($invoice);
        $this->em->persist($carrierInvoiceTax);
        $this->em->flush($carrierInvoiceTax);

        $purchasingOrderTax = new \ControleOnline\Entity\PurchasingOrderInvoiceTax();
        $purchasingOrderTax->setInvoiceTax($carrierInvoiceTax);
        $purchasingOrderTax->setInvoiceType(57);
        $purchasingOrderTax->setIssuer($purchasingOrder->getProvider());
        $purchasingOrderTax->setOrder($purchasingOrder);
        $this->em->persist($purchasingOrderTax);
        $this->em->flush($purchasingOrderTax);

    }

    return $carrierInvoiceTax;
    */
  }
  /**
   * Cria os pedidos de compra com base na nota fiscal de compra
   */
  protected function createPurchasingOrderFromSaleOrder(SalesOrder $order, People $provider, $invoice)
  {
    $cte = simplexml_load_string($invoice);
    $price = $cte->CTe->infCte->vPrest->vTPrest;
    if (!$price) {
      throw new \Exception('Impossible get price of DACTE', 103);
      return;
    }
    $purchasingOrder = clone $order;
    $this->em->detach($purchasingOrder);
    $purchasingOrder->resetId();
    $purchasingOrder->setOrderType('purchase');
    $purchasingOrder->setMainOrder($order);
    $purchasingOrder->setClient($order->getQuote()->getProvider());
    $purchasingOrder->setPayer($order->getQuote()->getProvider());
    $purchasingOrder->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'delivered', 'context' => 'order']));
    $purchasingOrder->setProvider($provider);
    $purchasingOrder->setPrice($price);
    $this->em->persist($purchasingOrder);
    $this->em->flush($purchasingOrder);

    $carrierInvoiceTax = $this->em->getRepository(PurchasingOrderInvoiceTax::class)->findOneBy([
      'order' => $order,
      'invoiceType' => '57',
    ]);

    if (!$carrierInvoiceTax) {

      $carrierInvoiceTax = new PurchasingInvoiceTax();
      $carrierInvoiceTax->setInvoice($invoice);
      $carrierInvoiceTax->setInvoiceNumber($cte->CTe->infCte->ide->nCT);
      $this->em->persist($carrierInvoiceTax);
      $this->em->flush($carrierInvoiceTax);

      $purchasingOrderTax = new PurchasingOrderInvoiceTax();
      $purchasingOrderTax->setInvoiceTax($carrierInvoiceTax);
      $purchasingOrderTax->setInvoiceType(57);
      $purchasingOrderTax->setIssuer($purchasingOrder->getProvider());
      $purchasingOrderTax->setOrder($this->em->getRepository(PurchasingOrder::class)->find($purchasingOrder->getId()));
      $this->em->persist($purchasingOrderTax);
      $this->em->flush($purchasingOrderTax);


      $salesOrderTax = new SalesOrderInvoiceTax();
      $salesOrderTax->setInvoiceTax($this->em->getRepository(SalesInvoiceTax::class)->find($carrierInvoiceTax->getId()));
      $salesOrderTax->setInvoiceType(57);
      $salesOrderTax->setIssuer($purchasingOrder->getProvider());
      $salesOrderTax->setOrder($order);
      $this->em->persist($salesOrderTax);
      $this->em->flush($salesOrderTax);
    }

    return $carrierInvoiceTax;
  }



  protected function getCloseTaskFromDivergenceOrders(int $limit, int $datelimit = null)
  {

    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\ReceiveInvoice', 'I', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\Task', 'T', 'WITH', 'T.order = O.id')
      ->where('I.status IN (:status)')
      ->andWhere('T.taskStatus IN (:taskStatus)')
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['resolved'], 'context' => 'invoice']),
        'taskStatus' => $this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'support'])
      ))
      ->getQuery()->getResult();


    if (count($salesOrders) == 0)
      return null;
    else {
      /**
       * @var \ControleOnline\Repository\SalesOrder $order
       */
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Close Task',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                $invoice = $order->getInvoiceByStatus(['resolved']);
                switch ($invoice->getStatus()->getStatus()) {
                  case 'resolved':
                    $task = $this->em->getRepository(Task::class)->findOneBy([
                      'order' => $order,
                      'taskStatus' => $this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'support'])
                    ]);

                    $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['closed'], 'context' => 'support']));

                    /**
                     * @todo Ajustar para que este usuário seja pego automaticamente
                     * Por enquanto, adicionado manualmente o usuário da Kailaine
                     */
                    $defaultPeople = $this->em->getRepository(People::class)->find(26682);

                    $taskInteration = new TaskInteration();
                    $taskInteration->setTask($task);
                    $taskInteration->setType('comment');
                    $taskInteration->setRegisteredBy($defaultPeople);
                    $taskInteration->setBody('Divergência resolvida automaticamente');


                    $this->em->persist($task);
                    $this->em->persist($taskInteration);
                    $this->em->flush();
                    break;
                  default:
                    # code...
                    break;
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
      return $orders;
    }
  }




  protected function getTaskFromTrackingOrders(int $limit, int $datelimit = null)
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\OrderTracking', 'OT', 'WITH', 'OT.order = O.id')
      ->leftJoin('\ControleOnline\Entity\Task', 'T', 'WITH', 'T.order = O.id')
      ->where('OT.tipo IN (:tipo)')
      ->having('COUNT(T.id) = 0')
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->setParameters(array(
        'tipo' => [
          'Baixa',
          'Cliente',
          'Pendencia',
          'Solucionada'
        ],
      ))
      ->getQuery()->getResult();

    if (count($salesOrders) == 0)
      return null;
    else {
      /**
       * @var \ControleOnline\Repository\SalesOrder $order
       */
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Create Task',
          'notifier' => [
            'send' => function () use ($order) {
              try {

                $task = new Task();
                $task->setType('support');
                $task->setClient($order->getClient());
                $task->setDueDate(new \DateTime('now'));
                $task->setOrder($order);
                $task->setProvider($order->getProvider());
                /**
                 * @todo Ajustar para que este usuário seja pego automaticamente
                 * Por enquanto, adicionado manualmente o usuário da Kailaine
                 */
                $defaultPeople = $this->em->getRepository(People::class)->find(26682);
                $category = $this->em->getRepository(Category::class)->findOneBy(['name' => ['Ocorrência']]);
                if (!$category) {
                  $category = new Category();
                  $category->setName('Ocorrência');
                  $category->setContext('support');
                  $this->em->persist($category);
                  $this->em->flush();
                }
                $task->setTaskFor($defaultPeople);
                $task->setRegisteredBy($defaultPeople);
                $task->setCategory($category);

                $task->setName('[Automático] - Ocorrência Rastreamento');

                $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'support']));

                $taskInteration = new TaskInteration();
                $taskInteration->setType('comment');
                $taskInteration->setVisibility('private');
                $taskInteration->setBody('Encontrada uma ocorrência no Rastreamento');
                $taskInteration->setTask($task);
                $taskInteration->setRegisteredBy($defaultPeople);


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
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
            },
          ],
        ];
      }
      return $orders;
    }
  }

  protected function getTaskFromActiveContractsOrders(int $limit, int $datelimit = null)
  {

    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\Contract', 'C', 'WITH', 'O.contract = C.id')
      ->leftJoin('\ControleOnline\Entity\Task', 'T', 'WITH', 'T.order = O.id')
      ->where('C.contractStatus IN (:contractStatus)')
      ->having('COUNT(T.id) = 0')
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->setParameters(array(
        'contractStatus' => ['Active'],
      ))
      ->getQuery()->getResult();


    if (count($salesOrders) == 0)
      return null;
    else {
      /**
       * @var \ControleOnline\Repository\SalesOrder $order
       */
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Create Task',
          'notifier' => [
            'send' => function () use ($order) {
              try {

                $task = new Task();
                $task->setType('support');
                $task->setClient($order->getClient());
                $task->setDueDate($order->getAlterDate());
                $task->setOrder($order);
                $task->setProvider($order->getProvider());
                /**
                 * @todo Ajustar para que este usuário seja pego automaticamente
                 * Por enquanto, adicionado manualmente o usuário da Cris
                 */
                $defaultPeople = $this->em->getRepository(People::class)->find(357);
                $category = $this->em->getRepository(Category::class)->findOneBy(['name' => ['Operacional']]);
                if (!$category) {
                  $category = new Category();
                  $category->setName('Operacional');
                  $category->setContext('support');
                  $this->em->persist($category);
                  $this->em->flush();
                }
                $task->setTaskFor($defaultPeople);
                $task->setRegisteredBy($defaultPeople);
                $task->setCategory($category);

                $task->setName('[Automático] - Processo operacional');

                $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'support']));
                $this->em->persist($task);
                $this->em->flush();

                $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting payment', 'context' => 'order']));



                $taskInteration = new TaskInteration();
                $taskInteration->setType('comment');
                $taskInteration->setVisibility('private');
                $taskInteration->setBody('O contrato foi assinado. Favor iniciar o processo operacional.');
                $taskInteration->setTask($task);
                $taskInteration->setRegisteredBy($defaultPeople);


                $this->em->persist($order);
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
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
            },
          ],
        ];
      }
      return $orders;
    }
  }

  protected function getTaskFromDivergenceOrders(int $limit, int $datelimit = null)
  {

    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\ReceiveInvoice', 'I', 'WITH', 'OI.invoice = I.id')
      ->leftJoin('\ControleOnline\Entity\Task', 'T', 'WITH', 'T.order = O.id AND T.category IN (:category)')
      ->where('I.status IN (:status)')
      ->having('COUNT(T.id) = 0')
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['divergence of values', 'resolved', 'waiting for discount'], 'context' => 'invoice']),
        'category' => $this->em->getRepository(Category::class)->findBy(['name' => ['Financeiro']])
      ))
      ->getQuery()->getResult();


    if (count($salesOrders) == 0)
      return null;
    else {
      /**
       * @var \ControleOnline\Repository\SalesOrder $order
       */
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Create Task',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                $invoice = $order->getInvoiceByStatus(['divergence of values', 'resolved', 'waiting for discount']);
                $task = new Task();
                $task->setType('support');
                $task->setClient($order->getProvider());
                $task->setDueDate(new \DateTime());
                $task->setOrder($order);
                $task->setProvider($order->getClient());
                /**
                 * @todo Ajustar para que este usuário seja pego automaticamente
                 * Por enquanto, adicionado manualmente o usuário da Kailaine
                 */
                $defaultPeople = $this->em->getRepository(People::class)->find(26682);
                $category = $this->em->getRepository(Category::class)->findOneBy(['name' => ['Financeiro']]);
                if (!$category) {
                  $category = new Category();
                  $category->setName('Financeiro');
                  $category->setContext('support');
                  $this->em->persist($category);
                  $this->em->flush();
                }
                $task->setTaskFor($defaultPeople);
                $task->setRegisteredBy($defaultPeople);
                $task->setCategory($category);


                switch ($invoice->getStatus()->getStatus()) {
                  case 'resolved':
                    $task->setName('[Automático] - Divergência Resolvida');

                    $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['closed'], 'context' => 'support']));

                    $taskInteration = new TaskInteration();
                    $taskInteration->setType('comment');
                    $taskInteration->setVisibility('private');
                    $taskInteration->setBody('Divergência resolvida automaticamente');
                    $taskInteration->setTask($task);
                    $taskInteration->setRegisteredBy($defaultPeople);


                    $this->em->persist($task);
                    $this->em->persist($taskInteration);
                    $this->em->flush();
                    break;
                  case 'waiting for discount':
                    $task->setName('[Automático] - Aguardando desconto');
                    $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['pending'], 'context' => 'support']));

                    $taskInteration = new TaskInteration();
                    $taskInteration->setType('comment');
                    $taskInteration->setVisibility('private');
                    $taskInteration->setBody('Aguardando desconto da transportadora');
                    $taskInteration->setTask($task);
                    $taskInteration->setRegisteredBy($defaultPeople);


                    $this->em->persist($task);
                    $this->em->persist($taskInteration);
                    $this->em->flush();
                    break;
                  case 'divergence of values':
                    $task->setName('[Automático] - Divergência de valores');

                    $task->setTaskStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'support']));


                    $taskInteration = new TaskInteration();
                    $taskInteration->setType('comment');
                    $taskInteration->setVisibility('private');
                    $taskInteration->setBody('[Automático] - Divergência de valores');
                    $taskInteration->setTask($task);
                    $taskInteration->setRegisteredBy($defaultPeople);


                    $this->em->persist($task);
                    $this->em->persist($taskInteration);
                    $this->em->flush();
                    break;
                  default:
                    # code...
                    break;
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
      return $orders;
    }
  }



  /**
   * Lê os DACTES das apis e dá baixa nos pedidos
   */
  private function getReadApiInvoiceTaxOrders(int $limit, int $datelimit = null): ?array
  {
    return [];
  }

  /**
   * Seleciona a primeira cotação no pedido
   */
  private function getFirstQuoteOrders(int $limit, int $datelimit = null): ?array
  {
    $quotes = $this->em->getRepository(Quotation::class)
      ->createQueryBuilder('Q')
      ->select('Q')
      ->innerJoin(SalesOrder::class, 'O', 'WITH', 'O.id = Q.order')
      ->andWhere('O.quote IS NULL')
      ->andWhere('O.orderType = :orderType')
      ->setParameters(['orderType' => 'sale'])
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()->getResult();

    if (count($quotes) == 0)
      return null;
    else {

      foreach ($quotes as $quote) {
        $order = $quote->getOrder();

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $quote->getCarrier()->getName(),
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient() ? $order->getClient()->getName() : '',
          'subject'  => 'Select Quote on Order',
          'notifier' => [
            'send' => function () use ($quote, $order) {
              try {
                $order->setQuote($quote);
                $order->setPrice($quote->getTotal());
                $this->em->persist($order);
                $this->em->flush($order);
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
            },
            'onSuccess' => function () use ($invoice) {
              try {
              } catch (\Exception $e) {
                echo $e->getMessage();
              }
            },
          ],
        ];
      }
    }

    return $orders;
  }

  /**
   * Envia o e-mail com instruções de pagamento
   */
  private function getInvoiceOrders(int $limit, int $datelimit = null): ?array
  {
    /**
     * @var \ControleOnline\Repository\ReceiveInvoiceRepository
     */
    $repository     = $this->em->getRepository(ReceiveInvoice::class);
    $receiveInvoice = $repository->createQueryBuilder('I')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'O.id = OI.order')
      ->innerJoin('\ControleOnline\Entity\Config', 'C', 'WITH', 'C.people = O.provider')
      ->where('I.status IN (:status)')
      ->andWhere('I.notified =:notified')
      ->andWhere('C.config_key LIKE :config_key')
      ->setParameters([
        'notified'      => 0,
        'config_key'    => 'itau-shopline-%',
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['waiting payment', 'outdated billing'], 'context' => 'invoice'])
      ])
      ->groupBy('I.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($receiveInvoice) == 0)
      return null;
    else {

      /**
       * @var \ControleOnline\Entity\ReceiveInvoice $invoice
       */
      foreach ($receiveInvoice as $invoice) {
        $order = $invoice->getOrder()[0]->getOrder();

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Payment invoice instructions',
          'notifier' => [
            'send' => function () use ($order, $invoice) {
              try {
                $company  = $order->getClient();

                $emailTo  = $company->getPeopleEmployee()[0]->getEmployee()->getEmail()[0]->getEmail();
                $twigFile = 'email/invoice.html.twig';

                // if invoice billing is outdated change twig template
                if ($invoice->getStatus()->getStatus() === 'outdated billing')
                  $twigFile = 'email/invoice-outdated.html.twig';

                if ($company === null)
                  throw new \Exception('Company domain not found', 102);

                $config  = $this->config->getMauticConfigByPeople($order->getProvider());
                if ($config === null || $config['mautic-invoice-form-id'] === null)
                  throw new \Exception('Company config not found', 103);

                $params  = $this->_getInvoiceOrdersTemplateParams($order);
                $this->sendMauticForm($config['mautic-invoice-form-id'], $config, $emailTo, $twigFile, $params);

                return true;
              } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
              $invoice->setNotified(1);
              $this->em->persist($invoice);
              $this->em->flush($invoice);
            },
            'onSuccess' => function () use ($invoice) {
              try {
                $invoice->setNotified(1);
                $this->em->persist($invoice);
                $this->em->flush($invoice);
              } catch (\Exception $e) {
                echo $e->getMessage();
              }
            },
          ],
        ];
      }
    }

    return $orders;
  }

  private function _getInvoiceOrdersTemplateParams(Order $order): array
  {
    /**
     * @var \ControleOnline\Entity\SalesOrder
     */
    $salesOrder     = $order;
    /**
     * @var \ControleOnline\Entity\ReceiveInvoice $receiveInvoice
     */
    $receiveInvoice = $salesOrder->getInvoice()->first() ? $salesOrder->getInvoice()->first()->getInvoice() : null;
    $invoiceNumber  = null;
    $invoiceOrders  = [];

    if ($receiveInvoice !== null) {
      if ($receiveInvoice->getServiceInvoiceTax()->first() !== false)
        $invoiceNumber = $receiveInvoice->getServiceInvoiceTax()->first()
          ->getServiceInvoiceTax()->getInvoiceNumber();
    }

    if ($receiveInvoice != null) {
      /**
       * @var \ControleOnline\Entity\SalesOrderInvoice $orderInvoice
       */
      foreach ($receiveInvoice->getOrder() as $orderInvoice) {
        $order = $orderInvoice->getOrder();

        $invoiceOrders[] = [
          'id'      => $order->getId(),
          'carrier' => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'invoice' => $order->getInvoiceTax()->count() > 0 ?
            $order->getInvoiceTax()->first()->getInvoiceTax()->getInvoiceNumber() : '',
          'price'   => 'R$' . number_format($order->getPrice(), 2, ',', '.'),
        ];
      }
    }

    return [
      'api_domain'      => 'https://' . isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.freteclick.com.br',
      'app_domain'      => 'https://cotafacil.freteclick.com.br',
      'order_id'        => $salesOrder->getId(),
      'invoice_id'      => $receiveInvoice != null ? $receiveInvoice->getId() : 0,
      'invoice_number'  => $invoiceNumber,
      'invoice_price'   => $receiveInvoice != null ? 'R$' . number_format($receiveInvoice->getPrice(), 2, ',', '.') : 0,
      'invoice_duedate' => $receiveInvoice != null ? $receiveInvoice->getDueDate()->format('d/m/Y') : '',
      'invoice_orders'  => $invoiceOrders,
    ];
  }

  private function recalculateInvoicePrice($receiveInvoice)
  {
    $price = 0;

    /**
     * @var \ControleOnline\Entity\SalesOrderInvoice $salesOrderInvoice
     */
    foreach ($receiveInvoice->getOrder() as $salesOrderInvoice) {
      if (!in_array($salesOrderInvoice->getOrder()->getStatus()->getStatus(), ['canceled', 'expired'])) {
        $price = $price + ($salesOrderInvoice->getRealPrice() > 0 ? $salesOrderInvoice->getRealPrice() : $salesOrderInvoice->getOrder()->getPrice());
      }
    }

    if ($salesOrderInvoice->getOrder()->getOrderType() == 'royalties') {
      $minimum = $salesOrderInvoice->getOrder()->getClient()->getPeopleFranchisee()[0]->getMinimumRoyalties();
      $price = $minimum > $price ? $minimum : $price;
    }

    if ($price > 0) {
      $receiveInvoice->setPrice($price);
      $this->em->persist($receiveInvoice);
      $this->em->flush($receiveInvoice);
    }
  }

  private function getDueDate(People $people): \DateTime
  {
    switch ($people->getBillingDays()) {
      case 'weekly':
        $date = new \DateTime('friday');
        break;
      case 'biweekly':
        $hoje = (int) date('d');

        if ($hoje >= 15) {
          $date = new \DateTime('first day of next month');
        } else {
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

    return $date->modify('+' . $people->getPaymentTerm() . ' days');
  }




  /**
   * Remove as faturas que não tem mais divergência no lucro
   */
  private function getRemoveDivergenceOfValuesOrders(int $limit, int $datelimit = null): ?array
  {


    $sql = '
            select
            I.id AS invoice
            from orders SO             
            inner join quote Q ON (Q.id = SO.quote_id)
            inner join quote_detail QD ON (QD.quote_id = Q.id)
            inner join orders PO ON (SO.id = PO.main_order_id)
            inner join order_invoice OI ON (OI.order_id = PO.id)
            inner join invoice I ON (I.id = OI.invoice_id)
            inner join status IST ON (IST.id = I.status_id)
            left  join tasks T ON (PO.id = T.order_id) AND T.category_id IN (:category) AND task_status_id IN (:taskStatus)
            WHERE            
            (
              (((SO.price -  PO.price  ) / SO.price) * 100) >= IF(QD.price>0, QD.price, 25)
              OR
              (SO.price - PO.price) >= QD.minimum_price
            )             
            AND QD.tax_name LIKE :tax_name
            AND PO.order_type = :order_type            
            AND IST.status IN (:status)

            GROUP BY PO.id
            having COUNT(T.id) > 0

            LIMIT :limit
            ';

    // execute query

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('invoice', 'invoice', 'integer');

    $nqu = $this->em->createNativeQuery($sql, $rsm);

    $nqu->setParameter('category', $this->em->getRepository(Category::class)->findBy(['name' => ['Financeiro']]));
    $nqu->setParameter('taskStatus', $this->em->getRepository(Status::class)->findBy(['status' => ['Closed'], 'context' => 'support']));
    $nqu->setParameter('status', ["divergence of values", "waiting for discount"]);
    $nqu->setParameter('order_type', "purchase");
    $nqu->setParameter('tax_name', "%CONVENIÊNCIA%");
    $nqu->setParameter('limit', $limit, 'integer');

    $result = $nqu->getResult();


    if (count($result) == 0)
      return null;
    else {
      foreach ($result as $inv) {

        $invoice = $this->em->find(PayInvoice::class,  $inv['invoice']);
        $order = $invoice->getOrder()[0]->getOrder();

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Close  invoice: #' . $invoice->getId(),
          'notifier' => [
            'send' => function () use ($invoice) {
              try {
                $this->recalculateInvoicePrice($invoice);
                $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['waiting payment'], 'context' => 'invoice']));
                $invoice->setNotified(0);
                $this->em->persist($invoice);
                $this->em->flush($invoice);
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
            },
            'onSuccess' => function () use ($invoice) {
            },
          ],
        ];
      }
    }
    return $orders;
  }


  /**
   * Marca as faturas que tem divergência no lucro para futuras conferências
   */
  private function getMarkDivergenceOfValuesOrders(int $limit, int $datelimit = null): ?array
  {

    $sql = '
            SELECT
            DISTINCT I.id AS invoice
            from orders SO             
            inner join quote Q ON (Q.id = SO.quote_id)
            inner join quote_detail QD ON (QD.quote_id = Q.id)
            inner join orders PO ON (SO.id = PO.main_order_id)
            inner join order_invoice OI ON (OI.order_id = PO.id)
            inner join invoice I ON (I.id = OI.invoice_id)
            inner join status IST ON (IST.id = I.status_id)
            left  join tasks T ON (PO.id = T.order_id) AND T.category_id IN (:category) AND task_status_id IN (:taskStatus)    
            WHERE
            (
              (((SO.price -  PO.price  ) / SO.price) * 100) < IF(QD.price>0, QD.price, 25)
              OR
              (SO.price - PO.price) < QD.minimum_price
            )
            AND QD.tax_name LIKE :tax_name
            AND PO.order_type = :order_type
            AND IST.status NOT IN (:status)            
            GROUP BY PO.id
            having COUNT(T.id) = 0     
            LIMIT :limit
            ';

    // execute query

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('invoice', 'invoice', 'integer');

    $nqu = $this->em->createNativeQuery($sql, $rsm);
    $nqu->setParameter('status', ["divergence of values", "waiting for discount", "resolved", "canceled", "paid"]);
    $nqu->setParameter('order_type', "purchase");
    $nqu->setParameter('tax_name', "%CONVENIÊNCIA%");
    $nqu->setParameter('limit', $limit, 'integer');
    $nqu->setParameter('category', $this->em->getRepository(Category::class)->findBy(['name' => ['Financeiro']]));
    $nqu->setParameter('taskStatus', $this->em->getRepository(Status::class)->findBy(['status' => ['Closed'], 'context' => 'support']));



    $result = $nqu->getResult();


    if (count($result) == 0)
      return null;
    else {
      foreach ($result as $inv) {

        $invoice = $this->em->find(PayInvoice::class,  $inv['invoice']);
        $order = $invoice->getOrder()[0]->getOrder();

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Invoice: #' . $invoice->getId(),
          'notifier' => [
            'send' => function () use ($invoice) {
              try {
                $this->recalculateInvoicePrice($invoice);
                $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['divergence of values'], 'context' => 'invoice']));
                $invoice->setNotified(0);
                $this->em->persist($invoice);
                $this->em->flush($invoice);
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
            },
            'onSuccess' => function () use ($invoice) {
            },
          ],
        ];
      }
    }
    return $orders;
  }



  /**
   * Fecha as faturas de acordo com o tipo de faturamento do cliente
   */
  private function getCloseMonthlyBillingInvoiceOrders(int $limit, int $datelimit = null): ?array
  {

    $receiveInvoice =  $this->em->getRepository(ReceiveInvoice::class)
      ->createQueryBuilder('I')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'OI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\People', 'P', 'WITH', 'O.payer = P.id')
      ->where('I.status IN(:status)')
      ->andWhere('OI.id IS NOT NULL')
      ->andWhere('I.invoice_date < :invoice_date')
      ->andWhere('P.billingDays = :billingDays')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['open', 'exceeded billing'], 'context' => 'invoice']),
        'billingDays' => 'monthly',
        'invoice_date' => new \DateTime('Today')
      ))
      ->groupBy('I.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($receiveInvoice) == 0)
      return null;
    else {
      foreach ($receiveInvoice as $invoice) {
        $order = $invoice->getOrder()[0]->getOrder();
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Close  invoice: #' . $invoice->getId(),
          'notifier' => [
            'send' => function () use ($invoice) {
              try {

                $this->recalculateInvoicePrice($invoice);
                $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['waiting payment'], 'context' => 'invoice']));
                $invoice->setNotified(0);
                $this->em->persist($invoice);
                $this->em->flush($invoice);

                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
            },
            'onSuccess' => function () use ($invoice) {
            },
          ],
        ];
      }
    }
    return $orders;
  }

  /**
   * Fecha as faturas de acordo com o tipo de faturamento do cliente
   */
  private function getCloseBiweeklyBillingInvoiceOrders(int $limit, int $datelimit = null): ?array
  {

    $receiveInvoice =  $this->em->getRepository(ReceiveInvoice::class)
      ->createQueryBuilder('I')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'OI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\People', 'P', 'WITH', 'O.payer = P.id')
      ->where('I.status IN(:status)')
      ->andWhere('OI.id IS NOT NULL')
      ->andWhere('I.invoice_date < :invoice_date')
      ->andWhere('P.billingDays = :billingDays')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['open', 'exceeded billing'], 'context' => 'invoice']),
        'billingDays' => 'biweekly',
        'invoice_date' => new \DateTime('Today')
      ))
      ->groupBy('I.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($receiveInvoice) == 0)
      return null;
    else {
      foreach ($receiveInvoice as $invoice) {
        $order = $invoice->getOrder()[0]->getOrder();
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Close  invoice: #' . $invoice->getId(),
          'notifier' => [
            'send' => function () use ($invoice) {
              try {

                $this->recalculateInvoicePrice($invoice);
                $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['waiting payment'], 'context' => 'invoice']));
                $invoice->setNotified(0);
                $this->em->persist($invoice);
                $this->em->flush($invoice);

                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
            },
            'onSuccess' => function () use ($invoice) {
            },
          ],
        ];
      }
    }
    return $orders;
  }

  /**
   * Fecha as faturas de acordo com o tipo de faturamento do cliente
   */
  private function getCloseWeeklyBillingInvoiceOrders(int $limit, int $datelimit = null): ?array
  {

    $receiveInvoice =  $this->em->getRepository(ReceiveInvoice::class)
      ->createQueryBuilder('I')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'OI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\People', 'P', 'WITH', 'O.payer = P.id')
      ->where('I.status IN(:status)')
      ->andWhere('OI.id IS NOT NULL')
      ->andWhere('I.invoice_date < :invoice_date')
      ->andWhere('P.billingDays = :billingDays')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['open', 'exceeded billing'], 'context' => 'invoice']),
        'billingDays' => 'weekly',
        'invoice_date' => new \DateTime('Today')
      ))
      ->groupBy('I.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($receiveInvoice) == 0)
      return null;
    else {
      foreach ($receiveInvoice as $invoice) {
        $order = $invoice->getOrder()[0]->getOrder();
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Close  invoice: #' . $invoice->getId(),
          'notifier' => [
            'send' => function () use ($invoice) {
              try {

                $this->recalculateInvoicePrice($invoice);
                $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['waiting payment'], 'context' => 'invoice']));
                $invoice->setNotified(0);
                $this->em->persist($invoice);
                $this->em->flush($invoice);

                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
            },
            'onSuccess' => function () use ($invoice) {
            },
          ],
        ];
      }
    }
    return $orders;
  }


  /**
   * Fecha as faturas de acordo com o tipo de faturamento do cliente
   */
  private function getCloseDailyBillingInvoiceOrders(int $limit, int $datelimit = null): ?array
  {

    $receiveInvoice =  $this->em->getRepository(ReceiveInvoice::class)
      ->createQueryBuilder('I')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'OI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\People', 'P', 'WITH', 'O.payer = P.id')
      ->where('I.status IN(:status)')
      ->andWhere('OI.id IS NOT NULL')
      ->andWhere('I.invoice_date < :invoice_date')
      ->andWhere('P.billingDays = :billingDays')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['open', 'exceeded billing'], 'context' => 'invoice']),
        'billingDays' => 'daily',
        'invoice_date' => new \DateTime(date('Y-m-d 00:00:00'))
      ))
      ->groupBy('I.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($receiveInvoice) == 0)
      return null;
    else {
      foreach ($receiveInvoice as $invoice) {
        $order = $invoice->getOrder()[0]->getOrder();
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Close  invoice: #' . $invoice->getId(),
          'notifier' => [
            'send' => function () use ($invoice) {
              try {

                $this->recalculateInvoicePrice($invoice);
                $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['waiting payment'], 'context' => 'invoice']));
                $invoice->setNotified(0);
                $this->em->persist($invoice);
                $this->em->flush($invoice);

                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($invoice) {
            },
            'onSuccess' => function () use ($invoice) {
            },
          ],
        ];
      }
    }
    return $orders;
  }


  /**
   * Cria os pedidos de compra quando o pedido contém a nota fiscal de serviço (DACTE)
   */
  private function getMainOrderDiscoveryOrders(int $limit, int $datelimit = null): ?array
  {


    $salesOrder =  $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\PurchasingOrderInvoiceTax', 'OIT', 'WITH', 'OIT.order = O.id')
      ->innerJoin('\ControleOnline\Entity\PurchasingOrderInvoiceTax', 'OITS', 'WITH', 'OITS.order = O.id')
      ->leftJoin('\ControleOnline\Entity\PurchasingOrder', 'PO', 'WITH', 'PO.mainOrder = O.id')
      ->where('O.orderType =:orderType')
      ->andWhere('PO.id IS NULL')
      ->andWhere('OIT.invoiceType =:invoice_type')
      ->andWhere('OITS.invoiceType =:invoice_type_s')
      ->andWhere('O.status IN(:status)')
      ->setParameters([
        'invoice_type_s' => '55',
        'invoice_type' => '57',
        'orderType' => 'sale',
        'status' => $this->em->getRepository(Status::class)->findBy(['realStatus' => ['closed'], 'context' => 'order']),
      ])
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()->getResult();

    if (count($salesOrder) == 0)
      return null;
    else {
      foreach ($salesOrder as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Add purchase order',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                $PurchasingOrderInvoiceTax = $this->em->getRepository(PurchasingOrderInvoiceTax::class)->findOneBy([
                  'order' => $order,
                  'invoiceType' => '57'
                ]);
                if ($PurchasingOrderInvoiceTax) {
                  $purchasingOrders =  $this->em->getRepository(PurchasingOrder::class)
                    ->createQueryBuilder('O')
                    ->select()
                    ->innerJoin('\ControleOnline\Entity\PurchasingOrderInvoiceTax', 'OIT', 'WITH', 'OIT.order = O.id')
                    ->where('OIT.invoiceTax IN(:invoiceTax)')
                    ->andWhere('O.id NOT IN (:order)')
                    ->setParameters([
                      'invoiceTax' => $PurchasingOrderInvoiceTax->getInvoiceTax(),
                      'order' => $order
                    ])
                    ->groupBy('O.id')
                    ->getQuery()->getResult();

                  if (count($purchasingOrders) > 0) {
                    foreach ($purchasingOrders as $purchasingOrder) {
                      $purchasingOrder->setMainOrder($order);
                      $purchasingOrder->setOrderType('purchase');
                      $this->em->persist($purchasingOrder);
                      $this->em->flush($purchasingOrder);
                    }
                  } else {
                    $this->createPurchasingOrderFromSaleOrder($order, $order->getQuote()->getCarrier(), $PurchasingOrderInvoiceTax->getInvoiceTax()->getInvoice());
                  }
                }
                return true;
              } catch (\Exception $e) {
                echo $e->getMessage();
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

  /**
   * Cria as faturas dos pedidos
   */
  private function getGenerateInvoiceOrders(int $limit, int $datelimit = null): ?array
  {

    $salesOrder =  $this->em->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select()
      ->leftJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.order = O.id')
      ->where('O.status NOT IN(:status)')
      ->andWhere('O.status NOT IN(:nstatus)')
      ->andWhere('OI.id IS NULL')
      ->andWhere('O.provider IS NOT NULL')
      ->andWhere('O.client IS NOT NULL')
      ->andWhere('O.payer IS NOT NULL')
      ->setParameters(array(
        'status'   => $this->em->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled'], 'context' => 'order']),
        'nstatus'         =>  $this->em->getRepository(Status::class)->findBy(['status' => ['automatic analysis', 'analysis', 'waiting client invoice tax'], 'context' => 'order'])
      ))
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();


    if (count($salesOrder) == 0)
      return null;
    else {
      foreach ($salesOrder as $order) {

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Generate invoice',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                if ($order->getOrderType() == 'comission') {
                  $qry = $this->em->getRepository(ReceiveInvoice::class)
                    ->createQueryBuilder('I')
                    ->select()
                    ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
                    ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'OI.order = O.id')
                    ->where('I.status IN(:status)')
                    ->andWhere('O.provider = :provider')
                    ->andWhere('O.payer = :payer')
                    ->setParameters(array(
                      'provider' => $order->getProvider(),
                      'payer' => $order->getPayer(),
                      'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['open'], 'context' => 'invoice'])
                    ));

                  $receiveInvoice = $qry->groupBy('I.id')->getQuery()->getResult();

                  if (count($receiveInvoice) > 0) {
                    /**
                     * @var \ControleOnline\Entity\ReceiveInvoice $invoice
                     */
                    $invoice = $receiveInvoice[0];
                  } else {
                    $invoice = new ReceiveInvoice();
                    $invoice->setPrice($order->getPrice());
                    $invoice->setDueDate($this->getDueDate($order->getClient()));
                    $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'invoice']));
                  }


                  $invoice->setDescription('Comissão');
                  $invoice->setCategory($invoice->getCategory() ?:
                    $this->em->getRepository(Category::class)->findOneBy([
                      'context'  => 'expense',
                      'name'    => 'Comissão',
                      'company' => [$order->getProvider(), $order->getClient()]
                    ]));
                } else {

                  $receiveInvoice = [];
                  $hasExceeded = [];
                  $purchasingOrder = $this->em->getRepository(PurchasingOrderInvoiceTax::class)->findBy([
                    'order' => $order,
                    'invoiceType' => '55',
                  ]);

                  if (count($purchasingOrder) < 1) {
                    $hasExceeded = $this->em->getRepository(ReceiveInvoice::class)
                      ->createQueryBuilder('I')
                      ->select()
                      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
                      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'OI.order = O.id')
                      ->where('I.status IN(:status)')->andWhere('O.payer = :payer')
                      ->setParameters(array(
                        'payer' => $order->getPayer(),
                        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['exceeded billing'], 'context' => 'invoice'])
                      ))->getQuery()->getResult();
                  }

                  if (count($hasExceeded) == 0) {
                    $qry = $this->em->getRepository(ReceiveInvoice::class)
                      ->createQueryBuilder('I')
                      ->select()
                      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
                      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'OI.order = O.id')
                      ->where('I.status IN(:status)');

                    if (count($purchasingOrder) > 0) {
                      $qry->andWhere('O.payer = :payer');
                      $qry->setParameters(array(
                        'payer' => $order->getPayer(),
                        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['open'], 'context' => 'invoice'])
                      ));
                    } else {
                      $qry->andWhere('O.provider = :provider');
                      $qry->andWhere('O.payer = :payer');
                      $qry->setParameters(array(
                        'provider' => $order->getProvider(),
                        'payer' => $order->getPayer(),
                        'status' => $this->em->getRepository(Status::class)->findBy(['status' => ['open'], 'context' => 'invoice'])
                      ));
                    }

                    $receiveInvoice = $qry->groupBy('I.id')->getQuery()->getResult();


                    if (count($receiveInvoice) > 0 && $order->getClient()->getBilling() < $receiveInvoice[0]->getPrice() && $order->getOrderType() != 'comission') {
                      $receiveInvoice = $receiveInvoice[0];
                      $receiveInvoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['exceeded billing'], 'context' => 'invoice']));
                      $receiveInvoice->setNotified(0);
                      $this->em->persist($receiveInvoice);
                      $this->em->flush($receiveInvoice);
                      $receiveInvoice = [];
                      $hasExceeded[] = $receiveInvoice;
                    }
                  }

                  if (count($receiveInvoice) > 0 && $order->getOrderType() != 'purchase') {
                    /**
                     * @var \ControleOnline\Entity\ReceiveInvoice $invoice
                     */
                    $invoice = $receiveInvoice[0];
                  } else {
                    $invoice = new ReceiveInvoice();
                    $invoice->setPrice($order->getPrice());
                    $invoice->setDueDate($this->getDueDate($order->getClient()));

                    if ($order->getOrderType() != 'purchase' && $order->getPayer()->getBilling() > 0 && count($hasExceeded) == 0) {
                      $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['open'], 'context' => 'invoice']));
                    } else {
                      $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => ['waiting payment'], 'context' => 'invoice']));
                    }
                  }
                }





                $invoice->setNotified(0);

                $invoice->setDescription('Frete');
                $invoice->setCategory($invoice->getCategory() ?:
                  $this->em->getRepository(Category::class)->findOneBy([
                    'context'  => 'expense',
                    'name'    => 'Frete',
                    'company' => [$order->getProvider(), $order->getClient()]
                  ]));

                // create order invoice relationship

                $orderInvoice = new SalesOrderInvoice();
                $orderInvoice->setInvoice($invoice);
                $orderInvoice->setOrder($order);
                $orderInvoice->setRealPrice($order->getPrice());

                $invoice->addOrder($orderInvoice);

                $this->em->persist($invoice);
                $this->em->flush($invoice);

                // recalculate price

                $this->recalculateInvoicePrice($invoice);

                return true;
              } catch (\Exception $e) {
                echo $e->getMessage();
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

  /**
   * Muda as faturas atrasadas para o status correto
   */
  private function getChangeOutdatedInvoiceOrders(int $limit, int $datelimit = null): ?array
  {

    $date = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d', strtotime(' -2 Weekdays')) . ' 00:00:00');

    $receiveInvoice =  $this->em->getRepository(ReceiveInvoice::class)
      ->createQueryBuilder('I')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'O.id = OI.order')
      ->where('I.status IN(:status)')
      ->andWhere('I.dueDate < :due_date')
      ->setParameters(array(
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting payment', 'context' => 'invoice']),
        'due_date' => $date
      ))
      ->groupBy('I.id')
      ->setMaxResults($limit)
      ->getQuery()->getResult();

    if (count($receiveInvoice) == 0)
      return null;
    else {
      foreach ($receiveInvoice as $invoice) {
        /**
         * @var \ControleOnline\Entity\SalesOrder
         */
        if ($invoice->getOrder()->first()) {
          $order = $invoice->getOrder()->first()->getOrder();
          $orders[] = (object) [
            'order'    => $order->getId(),
            'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
            'company'  => $order->getProvider()->getName(),
            'receiver' => $order->getClient()->getName(),
            'subject'  => 'Change outdated invoice: #' . $invoice->getId(),
            'notifier' => [
              'send' => function () use ($invoice) {
                try {
                  $invoice->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'outdated billing', 'context' => 'invoice']));
                  $invoice->setNotified(0);
                  $this->em->persist($invoice);
                  $this->em->flush();
                  return true;
                } catch (\Exception $e) {
                  echo $e->getMessage();
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
    }
    return $orders;
  }


  /**
   * Envia o e-mail com instruções de pagamento de faturas em atraso
   */
  private function getOutdatedInvoiceOrders(int $limit, int $datelimit = null): ?array
  {

    $this->em->createQueryBuilder()->update(ReceiveOrder::class, 'I')
      ->set('I.notified', 0)
      ->where('I.statusIN(:status)')
      ->andWhere('I.notified=:notified')
      ->setParameters([
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'outdated billing', 'context' => 'invoice']),
        'notified' => 1
      ])
      ->getQuery()->execute();

    if (count($receiveInvoice) == 0)
      return null;
    else {
      foreach ($receiveInvoice as $invoice) {
        /**
         * @var \ControleOnline\Entity\SalesOrder
         */
        $order = $invoice->getOrder()[0]->getOrder();
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Invoice tax instructions',
          'notifier' => [
            'send' => function () use ($invoice) {
              try {
                return true;
              } catch (\Exception $e) {
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

  /**
   * Envia o e-mail com instruções de como criar a nota fiscal
   */
  private function getInvoiceTaxInstructionsOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->findBy([
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting client invoice tax', 'context' => 'order']),
        'notified' => 0
      ], null, $limit);

    if (count($salesOrders) == 0)
      return null;
    else {

      foreach ($salesOrders as $order) {

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Invoice tax instructions',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                $params   = [];
                $company  = $order->getClient();
                $emailTo  = $company->getPeopleEmployee()[0]->getEmployee()->getEmail()[0]->getEmail();
                $twigFile = 'email/invoice-tax-instructions.html.twig';

                if ($company === null)
                  throw new \Exception('Company domain not found', 102);

                $config  = $this->config->getMauticConfigByPeople($order->getProvider());
                if ($config === null)
                  throw new \Exception('Company config not found', 103);

                $params = $this->_getInvoiceTaxInstructionsOrdersTemplateParams($order);
                $this->sendMauticForm($config['mautic-invoice-tax-instructions-form-id'], $config, $emailTo, $twigFile, $params);

                return true;
              } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
              }
            },
          ],
          'events'   => [
            'onError'   => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {

              $salesOrder = $this->em->find(SalesOrder::class, $order->getId());

              $salesOrder->setNotified(1);

              $this->em->persist($salesOrder);
              $this->em->flush();
            },
          ],
        ];
      }
    }

    return $orders;
  }

  private function _getInvoiceTaxInstructionsOrdersTemplateParams(Order $order): array
  {
    /**
     * @var \ControleOnline\Entity\SalesOrder
     */
    $salesOrder = $order;
    $provider   = [
      'name'     => $salesOrder->getProvider()->getName(),
      'document' => '',
    ];
    $carrier    = [
      'name'      => '',
      'cnpj'      => '',
      'inscricao' => '',
      'address'   => [
        'postal_code' => '',
        'street'      => '',
        'number'      => '',
        'complement'  => '',
        'district'    => '',
        'city'        => '',
        'state'       => '',
      ],
    ];

    // carrier

    /**
     * @var \ControleOnline\Entity\People $_carrier
     */
    if ($salesOrder->getQuote() && ($_carrier = $salesOrder->getQuote()->getCarrier())) {
      $carrier['name'] = $_carrier->getName();

      foreach ($_carrier->getDocument() as $document) {
        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
          $carrier['cnpj'] = Formatter::mask('##.###.###/####-##', $document->getDocument());
        }

        if ($document->getDocumentType()->getDocumentType() == 'Inscrição Estadual') {
          $carrier['inscricao'] = Formatter::mask('###.###.###.###', $document->getDocument());
        }
      }

      if (!$_carrier->getAddress()->isEmpty()) {
        $address  = $_carrier->getAddress()->first();
        $street   = $address->getStreet();
        $district = $street->getDistrict();
        $city     = $district->getCity();
        $state    = $city->getState();

        $carrier['address']['state']       = $state->getUF();
        $carrier['address']['city']        = $city->getCity();
        $carrier['address']['district']    = $district->getDistrict();
        $carrier['address']['postal_code'] = strlen($street->getCep()->getCep()) == 7 ? '0' . $street->getCep()->getCep() : $street->getCep()->getCep();
        $carrier['address']['street']      = $street->getStreet();
        $carrier['address']['number']      = $address->getNumber();
        $carrier['address']['complement']  = $address->getComplement();

        if (!empty($carrier['address']['postal_code']))
          $carrier['address']['postal_code'] = Formatter::mask('#####-###', $carrier['address']['postal_code']);
      }
    }

    // provider

    if ($salesOrder->getProvider() && $salesOrder->getProvider()->getDocument()) {
      foreach ($salesOrder->getProvider()->getDocument() as $document) {
        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
          $provider['document'] = Formatter::mask('##.###.###/####-##', $document->getDocument());
        }
      }
    }

    return  [
      'order_id' => $salesOrder->getId(),
      'provider' => $provider,
      'carrier'  => $carrier,
    ];
  }

  /**
   * Solicita a coleta na transportadora
   */
  private function getRetrieveOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)
      ->findBy([
        'status' => $this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting retrieve', 'context' => 'order']),
        'notified' => 0
      ], null, $limit);

    if (count($salesOrders) == 0)
      return null;
    else {

      /**
       * @var SalesOrder $order
       */
      foreach ($salesOrders as $order) {

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Retrieve request',
          'notifier' => [
            'send' => function () use ($order) {
              try {

                /*
                * @todo
                * É preciso criar uma condição para solicitar direto via webservice
                * o e-mail é a última alternativa
                */

                $params   = [];
                $carrier  = $order->getQuote()->getCarrier();

                if (count($carrier->getPeopleEmployee()) == 0) {
                  throw new \Exception('Company employee not found', 102);
                  return false;
                }

                if (count($carrier->getPeopleEmployee()[0]->getEmployee()->getEmail()) == 0) {
                  throw new \Exception('Employee email not found', 102);
                  return false;
                }

                //$emailTo  = $company->getPeopleEmployee()[0]->getEmployee()->getEmail()[0]->getEmail();
                $emailTo = [];

                foreach ($carrier->getEmail() as $email) {
                  if (in_array('retrieve', (array)$email->getTypes()) || empty((array)$email->getTypes())) {
                    $emailTo[] = $email->getEmail();
                  }
                }

                foreach ($carrier->getPeopleEmployee() as $employee) {
                  foreach ($employee->getEmployee()->getEmail() as $email) {
                    if (in_array('retrieve', (array)$email->getTypes()) || empty((array)$email->getTypes())) {
                      $emailTo[] = $email->getEmail();
                    }
                  }
                }

                if (empty($emailTo)) {
                  throw new \Exception('Employee email not found', 102);
                  return false;
                }

                $twigFile = 'email/retrieve-request.html.twig';

                if ($carrier === null)
                  throw new \Exception('Company domain not found', 102);

                $config  = $this->config->getMauticConfigByPeople($order->getProvider());
                if ($config === null)
                  throw new \Exception('Company config not found', 103);

                $params = $this->_getOrdersTemplateParams($order);


                foreach ($emailTo as $sendmail) {
                  $this->sendMauticForm($config['mautic-retrieve-form-id'], $config, $sendmail, $twigFile, $params);
                }

                return true;
              } catch (\Exception $e) {
                echo $e->getLine() . ' - ' . $e->getMessage();
                echo $e->getFile();
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {

              $order->setNotified(1);
              $this->em->persist($order);
              $this->em->flush($order);
            },
          ],
        ];
      }
    }

    return $orders;
  }

  private function _getOrdersTemplateParams(Order $order): array
  {
    /**
     * @var \ControleOnline\Entity\SalesOrder
     */
    $salesOrder   = $order;
    $provider     = $salesOrder->getProvider();
    $providerDoc  = '';
    $retrieveData = [
      'people_type'    => $salesOrder->getRetrievePeople()->getPeopleType(),
      'people_name'    => $salesOrder->getRetrievePeople()->getName(),
      'people_alias'   => $salesOrder->getRetrievePeople()->getAlias(),
      'people_doc'     => '',
      'contact' => [
        'name'   => '',
        'alias'  => '',
        'emails' => [],
        'phones' => [],
      ],
      'address' => [
        'postal_code' => '',
        'street'      => '',
        'number'      => '',
        'complement'  => '',
        'district'    => '',
        'city'        => '',
        'state'       => '',
      ],
    ];
    $deliveryData = [
      'people_type'  => $salesOrder->getDeliveryPeople()->getPeopleType(),
      'people_name'  => $salesOrder->getDeliveryPeople()->getName(),
      'people_alias' => $salesOrder->getDeliveryPeople()->getAlias(),
      'people_doc'   => '',
      'contact'      => [
        'name'   => '',
        'alias'  => '',
        'emails' => [],
        'phones' => [],
      ],
      'address'      => [
        'postal_code' => '',
        'street'      => '',
        'number'      => '',
        'complement'  => '',
        'district'    => '',
        'city'        => '',
        'state'       => '',
      ],
    ];
    $orderProduct  = [
      'cubage' => '',
      'type'   => '',
      'total'  => '',
    ];
    $orderPackages = [];

    // provider

    if ($salesOrder->getProvider() && $salesOrder->getProvider()->getDocument()) {
      foreach ($salesOrder->getProvider()->getDocument() as $document) {
        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
          $providerDoc = Formatter::document($document->getDocument());
        }
      }
    }

    // retrieve

    if ($salesOrder->getRetrievePeople()->getPeopleType() == 'J')
      if ($salesOrder->getRetrievePeople() && $salesOrder->getRetrievePeople()->getDocument()) {
        foreach ($salesOrder->getRetrievePeople()->getDocument() as $document) {
          if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
            $retrieveData['people_doc'] = Formatter::document($document->getDocument());
          }
        }
      }

    if ($salesOrder->getRetrievePeople()->getPeopleType() == 'F')
      if ($salesOrder->getRetrievePeople() && $salesOrder->getRetrievePeople()->getDocument()) {
        foreach ($salesOrder->getRetrievePeople()->getDocument() as $document) {
          if ($document->getDocumentType()->getDocumentType() == 'CPF') {
            $retrieveData['people_doc'] = Formatter::document($document->getDocument());
          }
        }
      }

    if ($salesOrder->getRetrieveContact()) {
      $retrieveData['contact']['name'] = $salesOrder->getRetrieveContact()->getName();
      $retrieveData['contact']['alias'] = $salesOrder->getRetrieveContact()->getAlias();

      /**
       * @var \ControleOnline\Entity\Email $email
       */
      foreach ($salesOrder->getRetrieveContact()->getEmail() as $email) {
        $retrieveData['contact']['emails'][] = $email->getEmail();
      }

      /**
       * @var \ControleOnline\Entity\Phone $phone
       */
      foreach ($salesOrder->getRetrieveContact()->getPhone() as $phone) {
        $retrieveData['contact']['phones'][] = [
          'ddd'   => $phone->getDdd(),
          'phone' => $phone->getPhone(),
        ];
      }
    }

    if ($oaddress = $salesOrder->getAddressOrigin()) {
      $street   = $oaddress->getStreet();
      $district = $street->getDistrict();
      $city     = $district->getCity();
      $state    = $city->getState();

      $retrieveData['address']['state']       = $state->getUF();
      $retrieveData['address']['city']        = $city->getCity();
      $retrieveData['address']['district']    = $district->getDistrict();
      $retrieveData['address']['postal_code'] = strlen($street->getCep()->getCep()) == 7 ? '0' . $street->getCep()->getCep() : $street->getCep()->getCep();
      $retrieveData['address']['street']      = $street->getStreet();
      $retrieveData['address']['number']      = $oaddress->getNumber();
      $retrieveData['address']['complement']  = $oaddress->getComplement();

      if (!empty($retrieveData['address']['postal_code']))
        $retrieveData['address']['postal_code'] = Formatter::mask('#####-###', $retrieveData['address']['postal_code']);
    }

    // delivery

    if ($salesOrder->getDeliveryPeople()->getPeopleType() == 'J')
      if ($salesOrder->getDeliveryPeople() && $salesOrder->getDeliveryPeople()->getDocument()) {
        foreach ($salesOrder->getDeliveryPeople()->getDocument() as $document) {
          if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
            $deliveryData['people_doc'] = Formatter::document($document->getDocument());
          }
        }
      }

    if ($salesOrder->getDeliveryPeople()->getPeopleType() == 'F')
      if ($salesOrder->getDeliveryPeople() && $salesOrder->getDeliveryPeople()->getDocument()) {
        foreach ($salesOrder->getDeliveryPeople()->getDocument() as $document) {
          if ($document->getDocumentType()->getDocumentType() == 'CPF') {
            $deliveryData['people_doc'] = Formatter::document($document->getDocument());
          }
        }
      }

    if ($salesOrder->getDeliveryContact()) {

      $deliveryData['contact']['name'] = $salesOrder->getDeliveryContact()->getName();
      $deliveryData['contact']['alias'] = $salesOrder->getDeliveryContact()->getAlias();

      /**
       * @var \ControleOnline\Entity\Email $email
       */
      foreach ($salesOrder->getDeliveryContact()->getEmail() as $email) {
        $deliveryData['contact']['emails'][] = $email->getEmail();
      }

      /**
       * @var \ControleOnline\Entity\Phone $phone
       */
      foreach ($salesOrder->getDeliveryContact()->getPhone() as $phone) {
        $deliveryData['contact']['phones'][] = [
          'ddd'   => $phone->getDdd(),
          'phone' => $phone->getPhone(),
        ];
      }
    }

    if ($daddress = $salesOrder->getAddressDestination()) {
      $street   = $daddress->getStreet();
      $district = $street->getDistrict();
      $city     = $district->getCity();
      $state    = $city->getState();

      $deliveryData['address']['state']       = $state->getUF();
      $deliveryData['address']['city']        = $city->getCity();
      $deliveryData['address']['district']    = $district->getDistrict();
      $deliveryData['address']['postal_code'] = strlen($street->getCep()->getCep()) == 7 ? '0' . $street->getCep()->getCep() : $street->getCep()->getCep();
      $deliveryData['address']['street']      = $street->getStreet();
      $deliveryData['address']['number']      = $daddress->getNumber();
      $deliveryData['address']['complement']  = $daddress->getComplement();

      if (!empty($deliveryData['address']['postal_code']))
        $deliveryData['address']['postal_code'] = Formatter::mask('#####-###', $deliveryData['address']['postal_code']);
    }

    // order product

    $orderProduct['cubage'] = number_format($salesOrder->getCubage(), 3, ',', '.');
    $orderProduct['type']   = $salesOrder->getProductType();
    $orderProduct['total']  = 'R$' . number_format($salesOrder->getInvoiceTotal(), 2, ',', '.');

    // order package

    /**
     * @var \ControleOnline\Entity\OrderPackage $package
     */
    foreach ($salesOrder->getOrderPackage() as $package) {
      $orderPackages[] = [
        'qtd'    => $package->getQtd(),
        'weight' => str_replace('.', ',', $package->getWeight()) . ' kg',
        'height' => str_replace('.', ',', $package->getHeight() * 100) . ' Centímetros',
        'width'  => str_replace('.', ',', $package->getWidth()  * 100) . ' Centímetros',
        'depth'  => str_replace('.', ',', $package->getDepth()  * 100) . ' Centímetros',
      ];
    }

    // added invoice number

    /**
     * @var \ControleOnline\Entity\SalesInvoiceTax $receiveInvoice
     */
    $receiveInvoice = $salesOrder->getClientInvoiceTax();

    $otherInformations = $salesOrder->getOtherInformations(true);
    $schedule = null;
    if (!empty($otherInformations) && isset($otherInformations->schedule) && isset($otherInformations->schedule->retrieve)) {
      $schedule = new DateTime($otherInformations->schedule->retrieve);
    }



    return [
      'hash'           => md5($salesOrder->getClient()->getId()),
      'secret'         => md5($salesOrder->getPayer()->getId()),
      'api_domain'     => 'https://' . isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.freteclick.com.br',
      'app_domain'     => 'https://cotafacil.freteclick.com.br',
      'sales_order'    => $salesOrder->getId(),
      'provider_name'  => $provider->getName(),
      'provider_doc'   => $providerDoc,
      'retrieve_data'  => $retrieveData,
      'delivery_data'  => $deliveryData,
      'order_product'  => $orderProduct,
      'order_packages' => $orderPackages,
      'schedule'       => $schedule ? $schedule->format('d/m/Y') : null,
      'invoice_id'     => $receiveInvoice ? $receiveInvoice->getId() : null,
      'invoice_number' => $receiveInvoice ? $receiveInvoice->getInvoiceNumber() : null,
    ];
  }

  private function getItauConfig(People $people): ?array
  {
    /**
     * @var \ControleOnline\Repository\ConfigRepository
     */
    if (!isset($this->itau_configs[$people->getId()])) {
      $config = $this->em->getRepository(Config::class)
        ->getItauConfigByPeople($people);
      if (is_array($config)) {
        return $this->itau_configs[$people->getId()] = $config;
      }

      return null;
    }

    return $this->itau_configs[$people->getId()];
  }

  /**
   * Realiza a baixa da fatura quando o pagamento é realizado no Itaú
   */
  private function getVerifyPaymentInvoiceOrders(int $limit, int $datelimit = null): ?array
  {
    /**
     * @var \ControleOnline\Repository\ReceiveInvoiceRepository
     */
    $repository = $this->em->getRepository(ReceiveInvoice::class);
    $invoices   = $repository->createQueryBuilder('I')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.invoice = I.id')
      ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'O.id = OI.order')
      ->innerJoin('\ControleOnline\Entity\Config', 'C', 'WITH', 'C.people = O.provider')
      ->where('I.status IN (:status)')
      ->andWhere('C.config_key LIKE :config_key')
      ->andWhere('O.orderType IN(:order_type)')
      ->setParameters([
        'config_key'    => 'itau-shopline-%',
        'order_type'    => ['sale'], //['sale', 'purchase', 'comission', 'royalties']
        'status' => $this->em->getRepository(Status::class)->findBy([
          'realStatus'    => [
            'pending'
          ], 'context' => 'invoice'
        ])
      ])
      ->groupBy('I.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($invoices) == 0)
      return null;

    else {
      $orders = [];

      foreach ($invoices as $invoice) {

        /**
         * @var \ControleOnline\Entity\SalesOrder
         */

        if (($orderInvoice = $invoice->getOrder()->first()) === false)
          continue;

        $order = $orderInvoice->getOrder();

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Verify payment id: #' . $invoice->getId(),
          'notifier' => [
            'send' => function () use ($invoice, $order) {
              try {
                $configs = $this->getItauConfig($order->getProvider());
                if ($configs === null) {
                  return null;
                }

                $cripto  = new ItauClient($invoice, $configs);
                $this->payment[$invoice->getId()] = $cripto->getPayment();

                // mark order as paid
                if ($this->payment[$invoice->getId()]->isPromissePaid()) {
                  $status = $this->em->getRepository(Status::class)
                    ->findOneBy([
                      'status' => 'waiting retrieve'
                    ]);

                  foreach ($invoice->getOrder() as $orders) {
                    $o = $orders->getOrder();
                    if ($o->getStatus()->getStatus() == 'waiting payment') {
                      $o->setStatus($status);
                      $o->setNotified(0);
                      $this->em->persist($o);
                      $this->em->flush();
                    }
                  }
                }

                return $this->payment[$invoice->getId()]->isPaid();
              } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order, $invoice) {
              if (!isset($this->payment[$invoice->getId()])) {
                return;
              }

              if (
                $this->payment[$invoice->getId()]->getStatus() == 'not_found'
                && $order->getStatus()->getStatus() == 'waiting payment'
              ) {
                /**
                 * Se o cliente ainda não emitiu o boleto
                 * e o pedido ainda não iniciou, simplesmente mudar
                 * a data de vencimento
                 */

                $datetime = new \DateTime('now');
                $datetime->modify('+1 day');
                $invoice->setDueDate($datetime);
                $this->em->persist($invoice);
                $this->em->flush($invoice);
              } else {
                /**
                 * Se o pedido já está em tramitação e o boleto atrasou,
                 * gerar um novo e cobrar multa de 10% e 0,08% ao dia
                 */
              }
            },
            'onSuccess' => function () use ($invoice) {
              $invoice->setStatus(
                $this->em->getRepository(Status::class)
                  ->findOneBy(['status' => 'paid'])
              );
              $this->em->persist($invoice);
              $this->em->flush($invoice);
            },
          ],
        ];
      }
    }
    return $orders;
  }



  /**
   * Gera a NF de serviço
   */
  private function getIssueInvoiceOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)->createQueryBuilder('O')
      ->select()
      ->where('O.status IN (:status)')
      ->setParameters([
        'status'   => $this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting invoice tax', 'context' => 'order'])
      ])
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($salesOrders) == 0)
      return null;

    else {
      $orders = [];
      foreach ($salesOrders as $order) {
        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Change order status',
          'notifier' => [
            'send' => function () use ($order) {
              try {

                /**
                 * @todo Emitir a Nota Fiscal
                 */
                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'delivered', 'context' => 'order']));
              $order->setNotified(0);
              $this->em->persist($order);
              $this->em->flush($order);
            },
          ],
        ];
      }
    }
    return $orders;
  }

  /**
   * Realiza a baixa do pedido quando a fatura é paga
   */
  private function getVerifyPaymentOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\SalesOrderInvoice', 'OI', 'WITH', 'OI.order = O.id')
      ->innerJoin('\ControleOnline\Entity\ReceiveInvoice', 'I', 'WITH', 'I.id = OI.invoice')
      ->where('I.status IN (:istatus)')
      ->andWhere('O.status IN (:status)')
      ->setParameters([
        'istatus' => $this->em->getRepository(Status::class)->findBy(['status' => ['paid', 'open'], 'context' => 'invoice']),
        'status'   => $this->em->getRepository(Status::class)->findBy(['status' => 'waiting payment', 'context' => 'order'])
      ])
      ->groupBy('O.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($salesOrders) == 0)
      return null;

    else {
      $orders = [];


      foreach ($salesOrders as $order) {

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Change order status',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {
              $order->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting retrieve', 'context' => 'order']));
              $order->setNotified(0);
              $this->em->persist($order);
              $this->em->flush($order);
            },
          ],
        ];
      }
    }
    return $orders;
  }


  private function getDocumentOrders(int $limit, int $datelimit = null): ?array
  {
    $salesOrders = $this->em->getRepository(SalesOrder::class)->createQueryBuilder('O')
      ->select()
      ->innerJoin('\ControleOnline\Entity\Contract', 'C', 'WITH', 'C.id = O.contract')
      ->where('C.contractStatus IN (:contractStatus)')
      ->setParameters([
        'contractStatus' => ['Waiting signatures']
      ])
      ->groupBy('C.id')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    if (count($salesOrders) == 0)
      return null;

    else {
      $orders = [];


      foreach ($salesOrders as $order) {

        $orders[] = (object) [
          'order'    => $order->getId(),
          'carrier'  => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
          'company'  => $order->getProvider()->getName(),
          'receiver' => $order->getClient()->getName(),
          'subject'  => 'Change contract status',
          'notifier' => [
            'send' => function () use ($order) {
              try {
                return true;
              } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($order) {
            },
            'onSuccess' => function () use ($order) {


              $people = $this->em->getRepository(People::class)->find(2); //$order->getProvider()

              $signature = new SignatureService($this->em);
              $signature->setDefaultCompany($people);
              $signatureProvider = $signature->getFactory();
              $contract = $order->getContract();
              $status = $signatureProvider->getDocument($contract)->document->status;
              $this->output->writeln([sprintf('      Status : %s', $status)]);

              if ($status == 'closed') {
                $order->setStatus(
                  $this->em->getRepository(Status::class)->findOneBy(['status' => 'waiting payment', 'context' => 'order'])
                );
                $this->em->persist($order);
                $contract->setContractStatus('Active');
                $this->em->persist($contract);
                $this->em->flush($contract);
              } elseif ($status == 'canceled' || !$status) {
                $contract->setContractStatus('Canceled');
                $this->em->persist($contract);
                $this->em->flush($contract);
              }
            },
          ],
        ];
      }
    }
    return $orders;
  }

  /**
   * Avisa o comercial que um usuário novo fez uma cotação
   */
  private function getProspectOrders(int $limit, int $datelimit = null): ?array
  {
    $contacts = $this->em->getRepository(SalesOrder::class)->getByProspectContacts($limit);

    if (count($contacts) == 0)
      return null;

    else {
      $orders = [];

      foreach ($contacts as $contact) {

        $prospect = $this->ma->getProspect(
          [
            'email'    => $contact['client']['email'],
            'name'     => $contact['client']['name'],
            'phone'    => $contact['client']['phone'],
            'ufOrigin' => $contact['ufOrigin'],
          ],
          $contact['provider']['id']
        );

        $orders[] = (object) [
          'order'    => $contact['order'],
          'carrier'  => '-',
          'company'  => $contact['provider']['name'],
          'receiver' => $contact['client']['name'],
          'subject'  => 'New prospective customer',
          'notifier' => [
            'send' => function () use ($prospect) {
              try {

                $prospect->persist();

                return true;
              } catch (\Exception $e) {
                return false;
              }
            },
          ],
          'events'   => [
            'onError' => function () use ($orders) {
            },
            'onSuccess' => function () use ($contact) {
              $order = $this->em->find(SalesOrder::class, $contact['order']);

              $order->setNotified(1);

              $this->em->persist($order);

              $this->em->flush();
            },
          ],
        ];
      }
    }

    return $orders;
  }

  private function sendMauticForm(int $formID, array $config, string $emailTo, string $twigFile, array $params): void
  {

    $params['mauticform[formId]'] = $formID;
    $params['mauticform[f_key]']  = $config['mautic-o-auth2-public-key'];
    $params['mauticform[body]']   = $this->twig->render($twigFile, $params);
    $params['mauticform[email]']  = $emailTo;

    (new \GuzzleHttp\Client())
      ->post($config['mautic-url'] . '/form/submit?formId=' . $formID, array('form_params' => $params));
  }
}
