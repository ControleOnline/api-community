<?php

namespace App\Doctrine;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Entity\PurchasingOrder;
use App\Entity\SalesOrder;
use App\Entity\People;
use ControleOnline\Entity\ReceiveInvoice;
use ControleOnline\Entity\PayInvoice;
use App\Entity\Phone;
use ControleOnline\Entity\User;
use App\Entity\Address;
use App\Entity\Client;
use App\Entity\ComissionInvoice;
use App\Entity\ComissionOrder;
use App\Entity\Document;
use ControleOnline\Entity\Status;
use App\Entity\Email;
use App\Entity\PeopleClient;
use App\Entity\PeopleEmployee;
use App\Entity\PeopleSalesman;
use App\Entity\PeopleCarrier;
use App\Entity\MyContract;
use App\Entity\ProductOld as Product;
use App\Entity\PeopleDomain;
use App\Entity\Provider;
use App\Entity\CompanyExpense;
use ControleOnline\Entity\Category;
use App\Entity\Hardware;
use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\PurchasingOrderInvoice;
use App\Service\PeopleRoleService;

final class FilterCollectionByExtension
implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
  private $security;

  private $entities;

  private $request;

  private $roles;

  /*
   * @var EntityManagerInterface
   */
  private $manager;

  public function __construct(Security $security, RequestStack $requestStack, EntityManagerInterface $entityManager, PeopleRoleService $roles)
  {
    $this->security = $security;
    $this->request  = $requestStack->getCurrentRequest();
    $this->manager  = $entityManager;
    $this->roles    = $roles;
    $this->entities = [
      PurchasingOrder::class,
      SalesOrder::class,
      Document::class,
      Email::class,
      User::class,
      Address::class,
      Phone::class,
      People::class,
      Client::class,
      ComissionInvoice::class,
      ComissionOrder::class,
      MyContract::class,
      Product::class,
      Provider::class,
      CompanyExpense::class,
      Category::class,
      PayInvoice::class,
      Hardware::class,
    ];
  }

  public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, $resourceClass, $operationName = null)
  {
    $this->addWhere($queryBuilder, $resourceClass, 'collection');
  }

  public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, $resourceClass, array $identifiers, $operationName = null, array $context = [])
  {
    $this->addWhere($queryBuilder, $resourceClass, 'justoneitem');
  }

  private function addWhere(QueryBuilder $queryBuilder, $resourceClass, $applyTo): void
  {
    if (!in_array($resourceClass, $this->entities)) {
      return;
    }

    if (empty($this->security->getUser()))
      return;

    if ($this->security->isGranted('ROLE_ADMIN')) {
      return;
    }

    $rootAlias = $queryBuilder->getRootAliases()[0];

    switch ($resourceClass) {




      case PayInvoice::class:
        $client   = $this->getMyCompanies();
        $queryBuilder->innerJoin(sprintf('%s.order', $rootAlias), 'I');
        $queryBuilder->innerJoin('I.order', 'O');
        $queryBuilder->andWhere('O.client IN(:client)');
        $queryBuilder->setParameter('client', $client);

        break;
      case PurchasingOrder::class:

        $queryBuilder->andWhere(sprintf('%s.client IN (:client) OR %s.provider IN (:provider)', $rootAlias, $rootAlias));

        $queryBuilder->setParameter('client', $this->getMyCompany());
        $queryBuilder->setParameter('provider', $this->getMyCompany());

        break;

      case ComissionOrder::class:

        $client = $this->getMyProvider();

        $queryBuilder->andWhere(sprintf('%s.client = :client', $rootAlias));
        $queryBuilder->andWhere(sprintf('%s.provider IN (:provider)', $rootAlias));

        $queryBuilder->setParameter('client', $client);
        $queryBuilder->setParameter('provider', $this->getMyCompanies());

        break;


      case Hardware::class:
        $queryBuilder->andWhere(sprintf('%s.company IN(:company)', $rootAlias));
        $queryBuilder->setParameter('company', $this->isFilteringByMyCompany() ?  $this->getMyCompany() : $this->getMyCompanies());
        break;

      case SalesOrder::class:
        $queryBuilder->andWhere(sprintf('%s.provider IN(:provider)', $rootAlias));
        $queryBuilder->setParameter('provider', $this->isFilteringByMyCompany() ?  $this->getMyCompany() : $this->getMyCompanies());
        break;

      case ComissionInvoice::class:

        $provider = $this->getMyCompanies();
        $client   = $this->getMyProvider();

        $queryBuilder->innerJoin(sprintf('%s.order', $rootAlias), 'I');
        $queryBuilder->innerJoin('I.order', 'O');
        $queryBuilder->andWhere('O.provider IN(:provider)');
        $queryBuilder->andWhere('O.orderType = :orderType');
        $queryBuilder->andWhere('O.client = :client');
        $queryBuilder->setParameter('provider', $provider);
        $queryBuilder->setParameter('client', $client);
        $queryBuilder->setParameter('orderType', 'comission');


        break;

      case Document::class:
      case Email::class:
      case User::class:
      case Address::class:
      case Phone::class:

        if ($this->request->query->get('people', null) === null) {
          /**
           * @var User $myUser
           */
          $myUser = $this->security->getUser();
          $people = $this->isFilteringByMyCompany() ? $this->getMyCompany() : $myUser->getPeople();

          $queryBuilder->andWhere(
            sprintf('%s.people = :people', $rootAlias)
          );

          $queryBuilder->setParameter('people', $people);
        }

        break;

      case People::class:

        if ($applyTo == 'collection') {

          // search my employees

          if ($this->isFilteringByMyCompany()) {
            $myUser = $this->security->getUser();
            $people = $this->getMyCompany();

            $queryBuilder->innerJoin(sprintf('%s.peopleCompany', $rootAlias), 'pc');
            $queryBuilder->andWhere('pc.company   = :company');
            // $queryBuilder->andWhere ('pc.employee != :myself' );

            $queryBuilder->setParameter('company', $people);
            // $queryBuilder->setParameter('myself' , $myUser->getPeople());
          } else {
            $queryBuilder->andWhere(
              sprintf('%s = :people', $rootAlias)
            );
            $queryBuilder->setParameter('people', null);
          }
        }

        /*

        if ($applyTo == 'justoneitem') {

          // search one of my employees

          if ($this->isFilteringByMyCompany()) {
            $people = $this->getMyCompany();

            $queryBuilder->innerJoin(sprintf('%s.peopleCompany', $rootAlias), 'pc');
            $queryBuilder->andWhere ('pc.company = :company');

            $queryBuilder->setParameter('company', $people);
          }

          // search myself or one of my companies

          else {
            $myUser = $this->security->getUser();
            $people = [$myUser->getPeople()->getId()];

            // foreach ($myUser->getPeople()->getPeopleCompany() as $peopleCompany) {
            //   $people[] = $peopleCompany->getCompany()->getId();
            // }
            //
            // $queryBuilder->andWhere(
            //   sprintf('%s IN (:people)', $rootAlias)
            // );
            //
            // $queryBuilder->setParameter('people', $people);
          }
        }
        */

        break;

      case Client::class:

        if ($applyTo == 'justoneitem') {
          $subquery = $this->manager->createQueryBuilder()
            ->select('IDENTITY(people_employee.company)')
            ->from(PeopleEmployee::class, 'people_employee')
            ->andWhere('people_employee.employee = :my_people');

          $queryBuilder->innerJoin(PeopleClient::class, 'people_client', 'WITH', sprintf('people_client.client = %s.id', $rootAlias));
          $queryBuilder->andWhere(
            $this->manager->createQueryBuilder()
              ->expr()->in('people_client.company_id', $subquery->getDQL())
          );

          $queryBuilder->setParameter('my_people', $this->getMyPeople());
        }

        break;

      case MyContract::class:

        $subquery = $this->manager->createQueryBuilder()
          ->select('DISTINCT myContract')
          ->from(MyContract::class, 'myContract')
          ->innerJoin('App\Entity\MyContractPeople', 'contractPeople', 'WITH', 'contractPeople.contract = myContract')
          ->where("contractPeople.peopleType = 'Provider'")
          ->andWhere('contractPeople.people  IN (:providerId)');

        $queryBuilder->andWhere(
          $this->manager->createQueryBuilder()
            ->expr()->in(
              sprintf('%s.id', $rootAlias),
              $subquery->getDQL()
            )
        );

        $queryBuilder->setParameter('providerId', $this->getMyCompanies());

        break;

      case Product::class:

        $queryBuilder->innerJoin(
          PeopleDomain::class,
          'people_domain',
          'WITH',
          sprintf('people_domain.people = %s.productProvider', $rootAlias)
        );

        $queryBuilder->andWhere('people_domain.domain = :domain');

        $queryBuilder->setParameter('domain', $_SERVER['HTTP_HOST']);

        break;

      case Provider::class:


        $queryBuilder->innerJoin(
          \App\Entity\PeopleProvider::class,
          'people_provider',
          'WITH',
          sprintf('people_provider.provider = %s', $rootAlias)
        );
        $queryBuilder->andWhere('people_provider.company = :my_company');
        $queryBuilder->setParameter('my_company', $this->getMyCompany());

        $invoice_id = $this->request->query->get('invoiceId', null);
        if (!empty($invoice_id)) {
          $queryBuilder->innerJoin(PurchasingOrder::class, 'PO', 'WITH', sprintf('PO.provider = %s.id', $rootAlias));
          $queryBuilder->innerJoin(PurchasingOrderInvoice::class, 'POI', 'WITH', 'POI.order = PO.id');
          //$queryBuilder->innerJoin(Invoice::class, 'I', 'WITH', 'I.id = POI.invoice');
          $queryBuilder->andWhere('POI.invoice IN(:invoice)');
          $queryBuilder->setParameter('invoice', $invoice_id);
        }



        break;

      case CompanyExpense::class:

        $subquery = $this->manager->createQueryBuilder()
          ->select('IDENTITY(peopleEmployee.company)')
          ->from(PeopleEmployee::class, 'peopleEmployee')
          ->where("peopleEmployee.employee = :my_employee");

        $queryBuilder->andWhere(
          $this->manager->createQueryBuilder()
            ->expr()->in(
              sprintf('%s.company', $rootAlias),
              $subquery->getDQL()
            )
        );

        $queryBuilder->setParameter('my_employee', $this->security->getUser()->getPeople());

        break;

      case Category::class:

        /**
         * Using innerjoin causes duplication.
         * That is why I choosed IN clause
         */

        $subquery = $this->manager->createQueryBuilder()
          ->select('IDENTITY(peopleEmployee.company)')
          ->from(PeopleEmployee::class, 'peopleEmployee')
          ->where("peopleEmployee.employee = :my_employee");

        $queryBuilder->andWhere(
          $this->manager->createQueryBuilder()
            ->expr()->in(
              sprintf('%s.company', $rootAlias),
              $subquery->getDQL()
            )
        );

        $queryBuilder->setParameter('my_employee', $this->security->getUser()->getPeople());

        break;
    }
  }

  private function getMyCompanies(): array
  {
    /**
     * @var \ControleOnline\Entity\User $currentUser
     */
    $currentUser  = $this->security->getUser();
    $companies    = [];

    if (!$currentUser->getPeople()->getPeopleCompany()->isEmpty()) {
      foreach ($currentUser->getPeople()->getPeopleCompany() as $company) {
        $companies[] = $company->getCompany();
      }
    }
    return $companies;
  }

  private function getMyCompany($companyId = null): ?People
  {
    if ($companyId === null) {
      $companyId = $this->request->query->get('myCompany', null);
    }

    if ($companyId === null) {
      $companies = $this->security->getUser()->getPeople() ?
        $this->security->getUser()->getPeople()->getPeopleCompany() : null;

      if (empty($companies) || $companies->first() === false)
        return null;

      return $companies->first()->getCompany();
    }

    $company = $this->manager->find(People::class, $companyId);

    if ($company instanceof People) {

      // verify if client is a company of current user

      $isMyCompany = $this->security->getUser()->getPeople()->getPeopleCompany()->exists(
        function ($key, $element) use ($company) {
          return $element->getCompany() === $company;
        }
      );

      if ($isMyCompany === false) {
        return null;
      }
    }

    return $company;
  }

  private function getMyProvider($providerId = null): ?People
  {
    if ($providerId === null) {
      $providerId = $this->request->query->get('myProvider', null);
    }

    if ($providerId === null)
      return null;

    $provider = $this->manager->getRepository(People::class)->find($providerId);
    if ($provider === null)
      return null;

    return $this->manager->getRepository(PeopleSalesman::class)
      ->companyIsMyProvider($this->getMyPeople(), $provider) ? $provider : null;
  }

  private function getMyPeople(): ?People
  {
    return $this->security->getUser() instanceof User ? $this->security->getUser()->getPeople() : null;
  }

  private function isFilteringByMyCompany(): bool
  {
    return empty($this->request->query->get('myCompany', null)) === false;
  }
}
