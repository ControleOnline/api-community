<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\Query\ResultSetMapping;

use App\Library\Quote\Exception\ExceptionInterface;
use App\Library\Quote\Exception\EmptyResultsException;
use App\Library\Quote\Exception\EntityNotFoundException;

use App\Entity\Quote;
use App\Entity\PeopleDomain;
use ControleOnline\Entity\User;
use App\Entity\Email;
use App\Entity\People;
use App\Entity\Quotation;
use App\Entity\SalesOrder;
use ControleOnline\Entity\Status;
use App\Repository\TaxesRepository;
use App\Repository\QuoteRepository;
use App\Library\Quote\View\Group as ViewGroup;
use App\Library\Quote\Core\DataBag;
use App\Library\Utils\Address;
use App\Service\PeopleRoleService;

class GetQuoteHandler implements MessageHandlerInterface
{
  private $taxes;
  private $manager;
  private $params = [];
  private $quote;
  private $user;
  private $people;
  private $free_limit = 15;
  private $plan;

  public function __construct(
    EntityManagerInterface $manager,
    TaxesRepository        $taxesRepository,
    QuoteRepository        $quoteRepository,
    Security               $security,
    PeopleRoleService      $roles
  ) {
    $this->manager       = $manager;
    $this->taxes         = $taxesRepository;
    $this->quote         = $quoteRepository;
    $this->user          = $security->getUser();
    $this->people        = $this->user instanceof User ? $this->user->getPeople() : null;
    $this->plan['limit'] = $this->free_limit;
    $this->roles         = $roles;
  }

  public function __invoke(Quote $quote)
  {
    try {


      $this->getParamsFromRequest($quote);

      if ($this->checkLimitExceeded($quote))
        throw new EmptyResultsException(
          sprintf('Quote limit (%s quotations) is exceeded', $this->free_limit),
          402
        );


      $cubages = $this->getAllCubages();

      foreach ($cubages as $cubage) {
        $this->calculateCubage($quote, $cubage);
        //if ($this->params['routeType'] == 'simple') {
        $this->taxes->getAllTaxesByGroup($this->params);
        //} else {
        //$this->taxes->getMultipleTaxesByGroup($this->params);
        //}
      }
      $groups = $this->taxes->getOutput();
      if (empty($groups))
        throw new EmptyResultsException('No results');

      /*
       * Calculation
       */
      $quotations = [];

      foreach ($groups as $groupData) {
        $dtBag = $groupData + ['params' => $this->params];
        $group = new DataBag($dtBag);

        if (!$this->params['noRetrieve'] && $group->retrieveDeadline == 0)
          continue;


        $view  = new ViewGroup($group);
        $vwRes = $view->getResults();

        // Ignore (not included) group if result is null
        if ($vwRes === null)
          continue;

        $taxes = [];
        $deadline = 0;
        foreach ($group->taxes as $tax) {
          if (isset($vwRes[$tax->name])) {
            $deadline += $tax->deadline;
            $taxes[$tax->name] = [
              'id'           => $tax->id,
              'name'         => $tax->name,
              'type'         => $tax->type,
              'subType'      => $tax->subType,
              'description'  => $tax->description,
              'weight'       => $tax->finalWeight,
              'price'        => $tax->price,
              'minimumPrice' => $tax->minimumPrice,
              'subtotal'     => $vwRes[$tax->name],
            ];
          }
        }

        $quotations[] = [
          'id'               => null,
          'group'            => [
            'id'   => $group->id,
            'name' => $group->name,
            'code' => $group->code,
            'enabled' => $group->marketplace ? true : false
          ],
          'carrier'          => [
            'id'            => $group->carrier->id,
            'name'          => $group->carrier->name,
            'alias'         => $group->carrier->alias,
            'image'         => $group->carrier->image,
            'configs'       => $group->carrier->configs,
            'averageRating' => $group->carrier->rating,
            'enabled'       => $group->carrier->enabled,
          ],
          'retrieveDeadline' => $group->retrieveDeadline,
          'deliveryDeadline' => $deadline,
          'total'            => $vwRes['total'],
          'taxes'            => $taxes,
        ];
      }

      usort($quotations, function ($a, $b) {
        return $a['total'] > $b['total'];
      });

      if ($this->params['quoteType'] == 'simple') {
        $quoteFiter[0] = array_shift($quotations);
        usort($quotations, function ($a, $b) {
          return $a['deliveryDeadline'] > $b['deliveryDeadline'];
        });
        $quoteFiter[1] = array_shift($quotations);
        $quotations = $quoteFiter;
      }

      /*
        * Persistence
        */
      $this->quote->setParams($this->params);
      $this->quote->setQuotations($quotations);

      $result = $this->quote->persist();


      if (isset($this->params['mainOrder'])) {

        $mainOrder = $this->manager->getRepository(SalesOrder::class)->find($this->params['mainOrder']);
        $origin = $mainOrder->getAddressOrigin();
        $destination = $mainOrder->getAddressDestination();
        $retrieveContact = $mainOrder->getRetrieveContact();
        $deliveryContact = $mainOrder->getDeliveryContact();
        $deliveryPeople = $mainOrder->getDeliveryPeople();
        $retrievePeople = $mainOrder->getRetrievePeople();
        $provider = $mainOrder->getProvider();


        $order = $this->manager->getRepository(SalesOrder::class)->find($result['orderId']);
        $order->setClient($mainOrder->getClient());
        $order->setMainOrderId($mainOrder->getId());
        $order->setPayer($mainOrder->getPayer());
        $order->setAddressOrigin($origin);
        $order->setAddressDestination($destination);
        $order->setRetrieveContact($retrieveContact);
        $order->setDeliveryContact($deliveryContact);
        $order->setRetrievePeople($retrievePeople);
        $order->setDeliveryPeople($deliveryPeople);
        $order->setProvider($provider);


        if ($this->params['quoteType'] == 'devolution') {
          $order->setAddressOrigin($destination);
          $order->setAddressDestination($origin);
          $order->setRetrieveContact($deliveryContact);
          $order->setDeliveryContact($retrieveContact);
          $order->setRetrievePeople($deliveryPeople);
          $order->setDeliveryPeople($retrievePeople);
          $order->setStatus($this->manager->getRepository(Status::class)->findOneBy(['status' =>  'waiting client invoice tax']));
        }

        if ($this->params['quoteType'] == 're-delivery') {
          $order->setStatus($this->manager->getRepository(Status::class)->findOneBy(['status' =>  'waiting payment']));
          $invoiceTax = $mainOrder->getClientSalesInvoiceTax();
          if ($invoiceTax) {
            $order->addAInvoiceTax($invoiceTax);
            $mainOrder->removeInvoiceTax($invoiceTax);
            $this->manager->persist($mainOrder);
          }
        } else {
          $order->setNotified(false);
        }
        $this->manager->persist($order);
        $this->manager->flush();
      }

      return new JsonResponse([
        'response' => [
          'data'    => [
            'order' => [
              'routeType' => $this->taxes->getRouteType(),
              'id'     => $result['orderId'],
              'quotes' => $result['quotations'],
              'quote' =>   [
                'origin' =>                          [
                  'city' => $this->params['cityOriginName'],
                  'state' => $this->params['stateOriginName'],
                  'country' => $this->params['countryOriginName']
                ],
                'destination' => [
                  'city' => $this->params['cityDestinationName'],
                  'state' => $this->params['stateDestinationName'],
                  'country' => $this->params['countryDestinationName']
                ],
              ]

            ],
          ],
          'params'  => $this->params,
          'plan'    => $this->plan,
          'count'   => count($result['quotations']),
          'error'   => $result['status'] != true ? $result['message'] : '',
          'success' => $result['status'],
          'user'    => [
            'logged' => ($this->user instanceof User),
            'isNew'  => $this->contactHasUser() === false,
          ],
        ],
      ]);
    } catch (\Exception $e) {
      if (!$e instanceof ExceptionInterface) {
        throw new \Exception($e->getMessage());
      }

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'error_code' => $e->getCode(),
          'line' => $e->getLine(),
          'file' => $e->getFile(),
          'success' => false,
        ],
      ]);
    }
  }

  private function getAllCubages()
  {
    return [200, 300];
  }



  private function calculateCubage(Quote $quote, $cubage)
  {
    // calculate measures
    $maxCubage = 0;
    $totWeight = 0;
    $maxHeight = 0;
    $maxWidth  = 0;
    $maxDepth  = 0;

    if (is_numeric($quote->packages)) {
      $maxCubage = $quote->packages + 0; // if packages is a numeric string transform to number      

    } else {
      if (is_array($quote->packages)) {
        foreach ($quote->packages as $package) {
          $maxCubage += $package['qtd'] * $package['height'] * $package['width'] * $package['depth'] * $cubage;
          $totWeight += $package['qtd'] * $package['weight'];
        }

        $maxHeight = max(array_column($quote->packages, 'height'));
        $maxWidth  = max(array_column($quote->packages, 'width'));
        $maxDepth  = max(array_column($quote->packages, 'depth'));
      }
    }

    $this->params['totalWeight']  = $totWeight;
    $this->params['finalWeight']  = $maxCubage > $totWeight ? $maxCubage : $totWeight;
    $this->params['maxCubage']    = $maxCubage;
    $this->params['cubage']       = $cubage;
    $this->params['maxHeight']    = $maxHeight;
    $this->params['maxWidth']     = $maxWidth;
    $this->params['maxDepth']     = $maxDepth;
  }

  private function getParamsFromRequest(Quote $quote)
  {

    $oAddress     = new Address($quote->origin);
    $dAddress     = new Address($quote->destination);
    $domain       = $this->getDomain($quote->domain);
    $companyId    = $this->getCompanyId($domain);
    $quoteType    = $quote->quoteType;
    $denyCarriers = $quote->denyCarriers;
    $routeType    = $quote->routeType ?: 'simple';
    $groupTable    = $quote->groupTable;
    $groupCode    = $quote->groupCode;

    $this->params = [
      'groupCode'              => $groupCode,
      'groupTable'             => $groupTable,
      'app'                    => $quote->app,
      'routeType'              => $routeType,
      'quoteType'              => $quoteType,
      'denyCarriers'           => $denyCarriers,
      'productTotalPrice'      => $quote->productTotalPrice,
      'countryOriginName'      => $oAddress->getCountry(),
      'stateOriginName'        => $oAddress->getState(),
      'cityOriginName'         => $oAddress->getCity(),
      'countryDestinationName' => $dAddress->getCountry(),
      'stateDestinationName'   => $dAddress->getState(),
      'cityDestinationName'    => $dAddress->getCity(),
      'companyId'              => $companyId,
      'myCompany'              => $quote->myCompany,
      'selectedCompany'        => $quote->selectedCompany,
      'hasPackages'            => is_array($quote->packages),
      'packages'               => is_array($quote->packages) ? $quote->packages : [],
      'domainAddress'          => $domain,
      'mainOrder'              => $quote->mainOrder,
      'isMainCompany'          => $companyId == $this->getCompanyId($this->getDomain(null)),
      'productType'            => $quote->productType,
      'contactData'            => empty($quote->contact) ? false : $quote->contact,
      'addressComponents'      => [
        'origin'      => $oAddress,
        'destination' => $dAddress,
      ],
      'noRetrieve'             => $quote->noRetrieve,
      'pickup'                 => $quote->pickup,
      'delivery'               => $quote->delivery,
      'isLoggedUser'           => ($this->user instanceof User),
    ];
    $this->contactHasUser();
  }

  private function getCompanyId(string $domain)
  {
    $company = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      throw new EntityNotFoundException(
        sprintf('Company domain "%s" not found', $domain)
      );

    return $company->getPeople()->getId();
  }

  private function getDomain($domain): string
  {
    return $domain ?: $_SERVER['HTTP_HOST'];
  }

  private function checkLimitExceeded(Quote  $quote)
  {
    $this->plan['exceeded'] =  false;
    $this->free_limit;
    if ($this->people instanceof People) {
      $companies = [];
      foreach ($this->people->getPeopleCompany() as $company) {
        if (!$quote->myCompany)
          $companies[] = $company->getCompany();
        else 
        if ($quote->myCompany == $company->getCompany()->getId()) {
          $companies[] = $company->getCompany();
        }
      }
      if (!$companies)
        $companies = $this->people;

      $this->isBuyer($companies);
      $this->haveActivePlan($companies);

      $salesOrders = $this->manager->getRepository(SalesOrder::class)
        ->createQueryBuilder('O')
        ->select('COUNT(O.id) AS count')
        ->where('O.status IN (:status)')
        ->andWhere('O.client IN (:client_id)')
        ->andWhere('O.orderDate >= :order_date')
        ->groupBy('O.client')
        ->setParameters([
          'order_date' => strtotime("-1 months"),
          'client_id' => $companies,
          'status'   => $this->manager->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]),
        ])
        ->getQuery()->getResult();

      $this->plan['quoted'] = 0;
      foreach ($salesOrders as $order) {
        $this->plan['quoted'] = $order['count'];
        if ($order['count'] > $this->free_limit) {
          $this->plan['exceeded'] = true;
        }
      }
    }

    return $this->plan['exceeded'] && !$this->plan['active'] && !$this->plan['buyer'];
  }

  private function haveActivePlan($companies)
  {
    $this->plan['active'] = false;
    $this->plan['active'] = true;
    return $this->plan['active'];
  }

  private function isBuyer($client)
  {
    $this->plan['buyer'] = false;
    $salesOrders = $this->manager->getRepository(SalesOrder::class)
      ->createQueryBuilder('O')
      ->select('COUNT(O.id) AS count')
      ->where('O.status NOT IN (:status)')
      ->andWhere('O.client IN (:client_id)')
      ->andWhere('O.orderDate >= :order_date')
      ->groupBy('O.client')
      ->setParameters([
        'order_date' => strtotime("-1 months"),
        'client_id' => $client,
        'status'   => $this->manager->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]),
      ])
      ->getQuery()->getResult();

    $this->plan['purchased'] = 0;
    foreach ($salesOrders as $order) {
      $this->plan['purchased'] = $order['count'];
      if ($order['count'] >= 1) {
        $this->plan['buyer'] = true;
      }
    }
    return $this->plan['buyer'];
  }


  private function contactHasUser(): ?bool
  {

    $contact = $this->params['contactData'];
    $email = $contact ? $this->manager->getRepository(Email::class)->findOneBy(['email' => $contact['email']]) : null;

    if (!$this->user instanceof User) {

      if ($contact === false)
        return null;

      if (!$email)
        return false;

      $this->people = $email->getPeople();

      if ($this->people === null)
        return false;

      if ($this->people->getUser()->count() == 0)
        return false;
      else
        throw new EmptyResultsException("This e-mail is in use by a another user. Please login.", 401);
    } elseif (!$email)
      return false;
    elseif ($this->people->getId() != $email->getPeople()->getId() && !$this->roles->isFranchisee($this->people) && !$this->roles->isSuperAdmin($this->people))
      throw new EmptyResultsException("This e-mail is in use by a another user.", 401);


    return true;
  }
}
