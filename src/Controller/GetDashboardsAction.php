<?php

namespace App\Controller;

use App\Controller\AbstractCustomResourceAction;
use App\Entity\People;

class GetDashboardsAction extends AbstractCustomResourceAction
{
  public function index(): ?array
  {
    try {

      if (empty($this->payload()->query)) {
        throw new \Exception('Query name is not defined');
      }

      $parts = explode('-', $this->payload()->query);

      $method = '_get';
      foreach ($parts as $part) {
        $method .= ucfirst($part);
      }
      $method .= '_';

      if (!method_exists($this, $method)) {
        throw new \Exception(sprintf('Query %s not found', $this->payload()->query));
      }

      return $this->$method();
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
  }

  private function _getCanceledContracts_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;

    $connection = $this->manager()->getConnection();

    // build query

    $sql = 'SELECT';

    $sql .= ' COUNT(DISTINCT C.id) AS total_count,';
    $sql .= ' SUM(CP.product_price) AS total_price';

    $sql .= ' FROM contract C';

    $sql .= "
      INNER JOIN contract_people CPE ON
        CPE.contract_id = C.id AND
        CPE.people_id = :provider_id
      INNER JOIN contract_product CP ON
        CP.contract_id = C.id
      INNER JOIN product_old P ON
        CP.product_id = P.id

      WHERE
        (
          (P.product_type = :type AND P.product_subtype IS NOT NULL)
          OR P.product_type != :type
        )
        AND C.contract_status = :status
        AND (C.start_date BETWEEN :from_date AND :to_date)
    ";

    // add params

    $params = [];

    $params['status']      = 'Canceled';
    $params['type']        = 'Registration';
    $params['from_date']   = $fromDate->format('Y-m-d 00:00:00');
    $params['to_date']     = $toDate->format('Y-m-d 23:59:59');
    $params['provider_id'] = $company;

    // execute query

    $statement = $connection->prepare($sql);
    $statement->execute($params);

    $result = $statement->fetchAll();

    return [
      'canceled_contracts_count' => isset($result[0]) ? $result[0] : [
        'total_price' => 0,
        'total_count' => 0
      ]
    ];
  }

  private function _getNewContracts_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;

    $connection = $this->manager()->getConnection();

    // build query

    $sql = 'SELECT';

    $sql .= ' COUNT(DISTINCT C.id) AS total_count,';
    $sql .= ' SUM(CP.product_price) AS total_price';

    $sql .= ' FROM contract C';

    $sql .= "
      INNER JOIN contract_people CPE ON
        CPE.contract_id = C.id AND
        CPE.people_id = :provider_id
      INNER JOIN contract_product CP ON
        CP.contract_id = C.id
      INNER JOIN product_old P ON
        CP.product_id = P.id

      WHERE
        (
          (P.product_type = :type AND P.product_subtype IS NOT NULL)
          OR P.product_type != :type
        )
        AND C.contract_status = :status
        AND (C.start_date BETWEEN :from_date AND :to_date)
    ";

    // add params

    $params = [];

    $params['status']      = 'Active';
    $params['type']        = 'Registration';
    $params['from_date']   = $fromDate->format('Y-m-d 00:00:00');
    $params['to_date']     = $toDate->format('Y-m-d 23:59:59');
    $params['provider_id'] = $company;

    // execute query

    $statement = $connection->prepare($sql);
    $statement->execute($params);

    $result = $statement->fetchAll();

    return [
      'new_contracts_count' => isset($result[0]) ? $result[0] : [
        'total_price' => 0,
        'total_count' => 0
      ]
    ];
  }

  private function _getEnrollment_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;

    $connection = $this->manager()->getConnection();

    // build query

    $sql = 'SELECT';

    $sql .= ' COUNT(DISTINCT C.id) AS total_count,';
    $sql .= ' SUM(CP.product_price) AS total_price';

    $sql .= ' FROM contract C';

    $sql .= "
      INNER JOIN contract_people CPE ON
        CPE.contract_id = C.id AND
        CPE.people_id = :provider_id
      INNER JOIN contract_product CP ON
        CP.contract_id = C.id
      INNER JOIN product_old P ON
        CP.product_id = P.id

      WHERE
        P.product_type = :type
        AND C.contract_status = :status
        AND P.product_subtype IS NULL
        AND (C.start_date BETWEEN :from_date AND :to_date)
    ";

    // add params

    $params = [];

    $params['status']      = 'Active';
    $params['type']        = 'Registration';
    $params['from_date']   = $fromDate->format('Y-m-d 00:00:00');
    $params['to_date']     = $toDate->format('Y-m-d 23:59:59');
    $params['provider_id'] = $company;

    // execute query

    $statement = $connection->prepare($sql);
    $statement->execute($params);

    $result = $statement->fetchAll();

    return [
      'enrollment_count' => isset($result[0]) ? $result[0] : [
        'total_price' => 0,
        'total_count' => 0
      ]
    ];
  }

  private function _getInactiveCustomers_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $inactiveCustomersCount = $repository
        ->getInactiveClientsCountByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $inactiveCustomersCount = $repository
        ->getInactiveClientsCountByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'inactive_customers_count' => $inactiveCustomersCount,
    ];
  }

  private function _getActiveCustomers_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $activeCustomersCount = $repository
        ->getActiveClientsCountByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $activeCustomersCount = $repository
        ->getActiveClientsCountByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'active_customers_count' => $activeCustomersCount,
    ];
  }

  private function _getNewCustomers_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $newCustomersCount = $repository
        ->getNewClientsCountByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $newCustomersCount = $repository
        ->getNewClientsCountByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'new_customers_count' => $newCustomersCount,
    ];
  }

  private function _getProspectiveCustomers_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $prospectiveCustomersCount = $repository
        ->getProspectClientsCountByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $prospectiveCustomersCount = $repository
        ->getProspectClientsCountByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'prospective_customers_count' => $prospectiveCustomersCount,
    ];
  }

  private function _getQuoteTotals_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $quoteOrderTotals = $repository
        ->getQuoteTotalsByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $quoteOrderTotals = $repository
        ->getQuoteTotalsByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'quote_order_totals' => $quoteOrderTotals,
    ];
  }

  private function _getSalesTotals_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $salesOrderTotals = $repository
        ->getSalesTotalsByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $salesOrderTotals = $repository
        ->getSalesTotalsByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'sales_order_totals' => $salesOrderTotals,
    ];
  }

  private function _getPurchasingTotals_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $purchasingOrderTotals = $repository
        ->getPurshasingTotalsByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $purchasingOrderTotals = $repository
        ->getPurshasingTotalsByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'purchasing_order_totals' => $purchasingOrderTotals,
    ];
  }

  private function _getQuoteDateTotals_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $quoteOrderTotals = $repository
        ->getQuoteTotalsByDate($fromDate, $toDate, $company, $companies, true, true);
    } else {
      $quoteOrderTotals = $repository
        ->getQuoteTotalsByDate($fromDate, $toDate, $company, $companies, true);
    }

    return [
      'quote_order_total_date' => $quoteOrderTotals,
    ];
  }

  private function _getSalesDateTotals_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $salesOrderTotals = $repository
        ->getSalesTotalsByDate($fromDate, $toDate, $company, $companies, true, true);
    } else {
      $salesOrderTotals = $repository
        ->getSalesTotalsByDate($fromDate, $toDate, $company, $companies, true);
    }

    return [
      'sales_order_total_date' => $salesOrderTotals,
    ];
  }

  private function _getAverageTicketTotals_(): array
  {
    $averageTicketTotal    = null;
    $salesOrderTotals      = $this->_getSalesTotals_();
    $purchasingOrderTotals = $this->_getAllOperationalExpenses_();

    if (!empty($salesOrderTotals['sales_order_totals']['total_count'])) {
      $avg = $salesOrderTotals['sales_order_totals']['total_price'] / $salesOrderTotals['sales_order_totals']['total_count'];
      $averageTicket =  $salesOrderTotals['sales_order_totals']['total_price'] - $purchasingOrderTotals['operational_expenses']['total_price'];
      $averageTicketTotal = $averageTicket / $salesOrderTotals['sales_order_totals']['total_count'];
    }

    return [
      'average_ticket_total' => $averageTicketTotal,
      'average_ticket' => $avg,
    ];
  }

  private function _getComissionTotals_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;
    $companies  = $this->getMyPeopleCompanies();

    $repository = $this->repository(People::class);

    if ($this->payload()->viewType == 'main') {
      $comissionOrderTotals = $repository
        ->getComissionTotalsByDate($fromDate, $toDate, $company, $companies, false, true);
    } else {
      $comissionOrderTotals = $repository
        ->getComissionTotalsByDate($fromDate, $toDate, $company, $companies);
    }

    return [
      'comission_totals' => $comissionOrderTotals,
    ];
  }

  private function _getOperationalProfit_(): array
  {
    $averageTicketTotal    = null;
    $salesOrderTotals      = $this->_getSalesTotals_();
    $purchasingOrderTotals = $this->_getAllOperationalExpenses_();

    if (!empty($salesOrderTotals['sales_order_totals']['total_count'])) {
      $averageTicket =  $salesOrderTotals['sales_order_totals']['total_price'] - $purchasingOrderTotals['operational_expenses']['total_price'];
      $operational_profit_percent = ($averageTicket / $salesOrderTotals['sales_order_totals']['total_price']) * 100;
    }

    return [
      'operational_profit' => $averageTicket,
      'operational_profit_percent' => round($operational_profit_percent, 2)
    ];
  }

  private function _getNetProfit_(): array
  {
    $averageTicketTotal    = null;
    $salesOrderTotals      = $this->_getSalesTotals_();
    $purchasingOrderTotals = $this->_getAllOperationalExpenses_();
    $administrativeOrderTotals = $this->_getAdministrativeExpenses_();

    if (!empty($salesOrderTotals['sales_order_totals']['total_count'])) {
      $averageTicket =  $salesOrderTotals['sales_order_totals']['total_price'] -
        $purchasingOrderTotals['operational_expenses']['total_price'] -
        $administrativeOrderTotals['administrative_expenses']['total_price'];
      $net_profit_percent = ($averageTicket / $salesOrderTotals['sales_order_totals']['total_price']) * 100;
    }

    return [
      'net_profit' => $averageTicket,
      'net_profit_percent' => round($net_profit_percent, 2)
    ];
  }

  private function _getMarketingFee_(): array
  {
    $salesTotais      = $this->_getSalesTotals_();
    $enrollmentTotais = $this->_getEnrollment_();

    $salesTotais      = $salesTotais['sales_order_totals'];
    $enrollmentTotais = $enrollmentTotais['enrollment_count'];

    $totalPrice = (
      ((float) $salesTotais['total_price']) -
      ((float) $enrollmentTotais['total_price']));

    if ($totalPrice > 0) {
      $totalPrice = $totalPrice * 0.015;
    } else {
      $totalPrice = 0;
    }

    return [
      'marketing_fee_count' => [
        'total_price' => $totalPrice
      ]
    ];
  }

  private function _getRoyalts_(): array
  {
    $salesTotais      = $this->_getSalesTotals_();
    $enrollmentTotais = $this->_getEnrollment_();

    $salesTotais      = $salesTotais['sales_order_totals'];
    $enrollmentTotais = $enrollmentTotais['enrollment_count'];

    $totalPrice = (
      ((float) $salesTotais['total_price']) -
      ((float) $enrollmentTotais['total_price']));

    if ($totalPrice > 0) {
      $totalPrice = $totalPrice * 0.05;
    } else {
      $totalPrice = 0;
    }

    return [
      'royalts_count' => [
        'total_price' => $totalPrice
      ]
    ];
  }
  private function _getOperationalExpenses_(): array
  {
    $averageTicketTotal    = null;
    $salesOrderTotals      = $this->_getSalesTotals_();
    $purchasingOrderTotals = $this->_getAllOperationalExpenses_();

    if (!empty($salesOrderTotals['sales_order_totals']['total_count'])) {
      $averageTicket =  $purchasingOrderTotals['operational_expenses']['total_price'];
      $operational_profit_percent = ($purchasingOrderTotals['operational_expenses']['total_price'] / $salesOrderTotals['sales_order_totals']['total_price']) * 100;
    }

    return [
      'purchasing_order_count' => $purchasingOrderTotals['operational_expenses']['total_count'],
      'purchasing_order_totals' => $averageTicket,
      'purchasing_percent' => round($operational_profit_percent, 2)
    ];
  }
  private function _getAllOperationalExpenses_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;

    $connection = $this->manager()->getConnection();

    // build query

    $sql  = 'SELECT';
    $sql .= ' SUM(O.price) AS total_price,';
    $sql .= ' COUNT(DISTINCT O.id)  AS total_count';
    $sql .= ' FROM orders O';

    if (!$this->payload()->isMainView()) {
      $sql .= "
       INNER JOIN people C ON C.id = O.provider_id
       INNER JOIN people_salesman PS ON PS.company_id = C.id
       INNER JOIN people S ON S.id = PS.salesman_id
       INNER JOIN people_client PC ON PC.client_id = O.client_id AND PC.company_id = S.id
      ";
    }

    $sql .= "
     INNER JOIN orders SO ON
       SO.id = O.main_order_id

     WHERE
      SO.provider_id = :provider_id
      AND O.order_type = :order_type
      AND SO.status_id NOT IN (SELECT id FROM status WHERE real_status IN ('open', 'canceled'))
      AND (SO.order_date BETWEEN :from_date AND :to_date)
    ";

    if (!$this->payload()->isMainView()) {
      $sql .= "AND S.id IN (";
      $sql .= "SELECT people_employee.company_id FROM people_employee";
      $sql .= " INNER JOIN people_salesman ON people_salesman.salesman_id = people_employee.company_id";
      $sql .= " WHERE people_employee.employee_id = :employee_id";
      $sql .= ")";
    } else {
      $sql .= "
       LIMIT 1
      ";
    }


    // add params

    $params = [];

    $params['provider_id']   = $company;
    $params['from_date']   = $fromDate->format('Y-m-d 00:00:00');
    $params['to_date']     = $toDate->format('Y-m-d 23:59:59');
    $params['order_type']  = 'purchase';

    if (!$this->payload()->isMainView()) {
      $params['employee_id'] = $this->security()->getUser()->getPeople()->getId();
    }

    // execute query

    $statement = $connection->prepare($sql);
    $statement->execute($params);

    $result = $statement->fetchAll();

    return [
      'operational_expenses' => isset($result[0]) ? $result[0] : ['total_price' => 0, 'total_count' => 0],
    ];
  }

  private function _getAdministrativeExpenses_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;

    $connection = $this->manager()->getConnection();

    // build query

    $sql  = 'SELECT';
    $sql .= ' SUM(O.price) AS total_price,';
    $sql .= ' COUNT(O.id)  AS total_count';
    $sql .= ' FROM orders O';

    if (!$this->payload()->isMainView()) {
      $sql .= "
       INNER JOIN people C ON C.id = O.provider_id
       INNER JOIN people_salesman PS ON PS.company_id = C.id
       INNER JOIN people S ON S.id = PS.salesman_id
       INNER JOIN people_client PC ON PC.client_id = O.client_id AND PC.company_id = S.id
      ";
    }

    $sql .= "

     WHERE
      O.payer_people_id = :provider_id
      AND O.main_order_id IS NULL
      AND O.status_id NOT IN (SELECT id FROM status WHERE real_status IN ('open', 'canceled'))
      AND (O.order_date BETWEEN :from_date AND :to_date)
      AND O.order_type = :order_type
    ";

    if (!$this->payload()->isMainView()) {
      $sql .= "AND S.id IN (";
      $sql .= "SELECT people_employee.company_id FROM people_employee";
      $sql .= " INNER JOIN people_salesman ON people_salesman.salesman_id = people_employee.company_id";
      $sql .= " WHERE people_employee.employee_id = :employee_id";
      $sql .= ")";
    } else {
      $sql .= "
       LIMIT 1
      ";
    }

    // add params

    $params = [];

    $params['provider_id'] = $company;
    $params['from_date']   = $fromDate->format('Y-m-d 00:00:00');
    $params['to_date']     = $toDate->format('Y-m-d 23:59:59');
    $params['order_type']  = 'purchase';

    if (!$this->payload()->isMainView()) {
      $params['employee_id'] = $this->security()->getUser()->getPeople()->getId();
    }

    // execute query

    $statement = $connection->prepare($sql);
    $statement->execute($params);

    $result = $statement->fetchAll();

    return [
      'administrative_expenses' => isset($result[0]) ? $result[0] : ['total_price' => 0, 'total_count' => 0],
    ];
  }

  private function _getActiveContracts_(): array
  {
    $fromDate   = \DateTime::createFromImmutable($this->payload()->fromDate);
    $toDate     = \DateTime::createFromImmutable($this->payload()->toDate);
    $company    = $this->payload()->company;

    $connection = $this->manager()->getConnection();

    // build query

    $sql = "
      SELECT COUNT(DISTINCT contract.id) AS total_count
      FROM contract
       INNER JOIN contract_people ON
        contract_people.contract_id = contract.id
        AND contract_people.people_type = 'Provider'
      WHERE
       contract_people.people_id = :company_id
       AND contract.contract_status = 'Active'
       AND contract.start_date BETWEEN :from_date AND :to_date
    ";

    // add params

    $params = [];

    $params['company_id'] = $company;
    $params['from_date']  = $fromDate->format('Y-m-d 00:00:00');
    $params['to_date']    = $toDate->format('Y-m-d 23:59:59');

    // execute query

    $statement = $connection->prepare($sql);
    $statement->execute($params);

    $result = $statement->fetchAll();

    return [
      'active_contracts' => isset($result[0]) ?
        ['total_count' => ((int)$result[0]['total_count'])] : ['total_count' => 0],
    ];
  }

  private function getMyPeopleCompanies(): array
  {
    $repository = $this->manager()->getRepository(People::class);

    return $repository->createQueryBuilder('P')
      ->select()
      ->innerJoin('\App\Entity\PeopleEmployee', 'PE', 'WITH', 'PE.company = P.id')
      ->innerJoin('\App\Entity\PeopleSalesman', 'PS', 'WITH', 'PS.salesman = PE.company')
      ->where('PE.employee = :employee')
      ->setParameters([
        'employee' => $this->security()->getUser()->getPeople()
      ])
      ->groupBy('P.id')
      ->getQuery()
      ->getResult();
  }
}
