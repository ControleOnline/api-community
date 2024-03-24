<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\MainDashboard;
use ControleOnline\Entity\People;

class GetMainDashboardHandler implements MessageHandlerInterface
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
   * @var \ControleOnline\Repository\PeopleRepository
   */
  private $people;

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
    $this->myUser   = $security->getUser();
  }

  public function __invoke(MainDashboard $dashboard)
  {
    $company = $this->people->find($dashboard->providerId);

    if ($company === null)
      throw new \Exception('Company not found');

    return new JsonResponse([
      'response' => [
        'data'    => $this->peopleIsMyCompany($company) ? $this->getDashboardData($dashboard) : [],
        'count'   => 1,
        'success' => true,
      ],
    ]);
  }

  private function peopleIsMyCompany(People $company): bool
  {
    $isMyCompany = $this->myUser->getPeople()->getLink()->exists(
      function ($key, $element) use ($company) {
        return $element->getCompany() === $company;
      }
    );

    return $isMyCompany;
  }

  private function getDashboardData(MainDashboard $dashboard): array
  {
    $fromDate  = \DateTime::createFromFormat('Y-m-d', $dashboard->fromDate);
    $toDate    = \DateTime::createFromFormat('Y-m-d', $dashboard->toDate  );
    $companies = $this->getMyPeopleCompanies();

    //$inactive_clients_count   = $this->people->getInactiveClientsCountByDate($fromDate, $toDate, $dashboard->providerId, $companies, false,true);
    $active_clients_count     = $this->people->getActiveClientsCountByDate  ($fromDate, $toDate, $dashboard->providerId, $companies, false,true);
    $new_clients_count        = $this->people->getNewClientsCountByDate     ($fromDate, $toDate, $dashboard->providerId, $companies, false,true);
    $prospect_clients_count   = $this->people->getProspectClientsCountByDate($fromDate, $toDate, $dashboard->providerId, $companies, false,true);
    $quote_orders_totals      = $this->people->getQuoteTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies, false,true);
    $sale_orders_totals       = $this->people->getSalesTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies, false,true);
    $purshasing_orders_totals = $this->people->getPurshasingTotalsByDate    ($fromDate, $toDate, $dashboard->providerId, $companies, false,true);
    $quote_orders_total_date  = $this->people->getQuoteTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies, true, true);
    $sale_orders_total_date             = $this->people->getSalesTotalsByDate         ($fromDate, $toDate, $dashboard->providerId, $companies, true, true);
    $comission_purshasing_orders_totals = $this->people->getComissionTotalsByDate    ($fromDate, $toDate, $dashboard->providerId, $companies, false,true);

    $tax_purshasing_orders_totals['total_price'] = $sale_orders_totals['total_price'] / 100 * 6;
    $administrative_purshasing_orders_totals['total_price'] = 0;
    $average_ticket_total     = 0;

    if ($sale_orders_totals != null && !empty($sale_orders_totals['total_count'])) {
      $average_ticket_total = $sale_orders_totals['total_price'] - $purshasing_orders_totals['total_price'];
      $average_ticket_total = $average_ticket_total / $sale_orders_totals['total_count'];
    }

    return [
      'inactive_clients_count'  => /*$inactive_clients_count*/0,
      'active_clients_count'    => $active_clients_count,
      'new_clients_count'       => $new_clients_count,
      'prospect_clients_count'  => $prospect_clients_count,
      'quote_orders_totals'     => $quote_orders_totals,
      'sale_orders_totals'      => $sale_orders_totals,
      'purshasing_orders_totals'=> $purshasing_orders_totals,
      'quote_orders_total_date' => $quote_orders_total_date,
      'sale_orders_total_date'  => $sale_orders_total_date,
      'comission_purshasing_orders_totals'      => $comission_purshasing_orders_totals,
      'tax_purshasing_orders_totals'            => $tax_purshasing_orders_totals,
      'administrative_purshasing_orders_totals' => $administrative_purshasing_orders_totals,
      'average_ticket_total'    => $average_ticket_total,
    ];
  }

  private function getMyPeopleCompanies(): array
  {
    /**
     * @var \ControleOnline\Repository\PeopleRepository
     */
    $repository = $this->manager->getRepository(People::class);

    return $repository->createQueryBuilder('P')
      ->select()
      ->innerJoin('\ControleOnline\Entity\PeopleLink', 'PE', 'WITH', 'PE.company = P.id')
      ->where('PE.employee = :employee')
      ->setParameters([
          'employee' => $this->myUser->getPeople()
      ])
      ->groupBy('P.id')
      ->getQuery()
      ->getResult();
  }
}
