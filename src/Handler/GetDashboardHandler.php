<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\Dashboard;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleSalesman;

class GetDashboardHandler implements MessageHandlerInterface
{
  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager;

  /**
   * People Repository
   *
   * @var \App\Repository\PeopleRepository
   */
  private $people;

  /**
   * PeopleSalesman Repository
   *
   * @var \App\Repository\PeopleSalesmanRepository
   */
  private $salesman;

  /**
   * User entity
   *
   * @var \ControleOnline\Entity\User
   */
  private $myUser;

  public function __construct(EntityManagerInterface $manager, Security $security)
  {
    $this->manager  = $manager;
    $this->people   = $manager->getRepository(People::class);
    $this->salesman = $manager->getRepository(PeopleSalesman::class);
    $this->myUser   = $security->getUser();
  }

  public function __invoke(Dashboard $dashboard)
  {
    $provider = $this->people->find($dashboard->providerId);

    if ($provider === null)
      throw new \Exception('Provider not found');

    if (!$this->salesman->companyIsMyProvider($this->myUser->getPeople(), $provider))
      throw new \Exception('Provider Id is not valid');

    return new JsonResponse([
      'response' => [
        'data'    => $this->getDashboardData($dashboard),
        'count'   => 1,
        'success' => true,
      ],
    ]);
  }

  private function getDashboardData(Dashboard $dashboard): array
  {
    $fromDate  = \DateTime::createFromFormat('Y-m-d', $dashboard->fromDate);
    $toDate    = \DateTime::createFromFormat('Y-m-d', $dashboard->toDate  );
    $companies = $this->getMyPeopleCompanies();

    $inactive_clients_count             = $this->people->getInactiveClientsCountByDate($fromDate, $toDate, $dashboard->providerId, $companies);
    $active_clients_count               = $this->people->getActiveClientsCountByDate  ($fromDate, $toDate, $dashboard->providerId, $companies);
    $new_clients_count                  = $this->people->getNewClientsCountByDate     ($fromDate, $toDate, $dashboard->providerId, $companies);
    $prospect_clients_count             = $this->people->getProspectClientsCountByDate($fromDate, $toDate, $dashboard->providerId, $companies);
    $quote_orders_totals                = $this->people->getQuoteTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies);
    $sale_orders_totals                 = $this->people->getSalesTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies);
    $purshasing_orders_totals           = $this->people->getPurshasingTotalsByDate    ($fromDate, $toDate, $dashboard->providerId, $companies);
    $quote_orders_total_date            = $this->people->getQuoteTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies, true);
    $sale_orders_total_date             = $this->people->getSalesTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies, true);    
    $average_ticket_total     = 0;

    if ($sale_orders_totals != null && !empty($sale_orders_totals['total_count'])) {
      $average_ticket_total = $sale_orders_totals['total_price'] - $purshasing_orders_totals['total_price'];
      $average_ticket_total = $average_ticket_total / $sale_orders_totals['total_count'];
    }

    return [
      'inactive_clients_count'                  => $inactive_clients_count,
      'active_clients_count'                    => $active_clients_count,
      'new_clients_count'                       => $new_clients_count,
      'prospect_clients_count'                  => $prospect_clients_count,
      'quote_orders_totals'                     => $quote_orders_totals,
      'sale_orders_totals'                      => $sale_orders_totals,      
      'quote_orders_total_date'                 => $quote_orders_total_date,
      'sale_orders_total_date'                  => $sale_orders_total_date,
      'average_ticket_total'                    => $average_ticket_total,
    ];
  }

  private function getMyPeopleCompanies(): array
  {
    /**
     * @var \App\Repository\PeopleRepository
     */
    $repository = $this->manager->getRepository(People::class);

    return $repository->createQueryBuilder('P')
      ->select()          
      ->innerJoin('\ControleOnline\Entity\PeopleEmployee', 'PE', 'WITH', 'PE.company = P.id')
      ->innerJoin('\ControleOnline\Entity\PeopleSalesman', 'PS', 'WITH', 'PS.salesman = PE.company')
      ->where('PE.employee = :employee')
      ->setParameters([
          'employee' => $this->myUser->getPeople()
      ])
      ->groupBy('P.id')                      
      ->getQuery()
      ->getResult();
  }
}
