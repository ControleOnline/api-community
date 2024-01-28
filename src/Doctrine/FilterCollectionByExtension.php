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

use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Client;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\PeopleClient;
use ControleOnline\Entity\PeopleEmployee;
use ControleOnline\Entity\PeopleSalesman;
use ControleOnline\Entity\MyContract;
use ControleOnline\Entity\ProductOld as Product;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Provider;
use ControleOnline\Entity\Category;
use ControleOnline\Entity\Hardware;
use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\OrderInvoice;
use App\Service\PeopleRoleService;
use ControleOnline\Entity\OrderLogistic;

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


    $rootAlias = $queryBuilder->getRootAliases()[0];

    switch ($resourceClass) {

      case Invoice::class:
        $myCompanies   = $this->getMyCompanies();
        $receiver = $this->request->query->get('receiver', null);
        if ($receiver) {
          $queryBuilder->andWhere(sprintf('%s.receiver ', $rootAlias) . ' IN(:receiver)');
          $queryBuilder->andWhere(sprintf('%s.receiver ', $rootAlias) . ' IN(:myCompanies)');
          $queryBuilder->setParameter('receiver',  preg_replace("/[^0-9]/", "", $receiver));
          $queryBuilder->setParameter('myCompanies', $myCompanies);
        }

        $payer = $this->request->query->get('payer', null);
        if ($payer) {
          $queryBuilder->andWhere(sprintf('%s.payer ', $rootAlias) . ' IN(:payer)');
          $queryBuilder->andWhere(sprintf('%s.payer ', $rootAlias) . ' IN(:myCompanies)');
          $queryBuilder->setParameter('payer',  preg_replace("/[^0-9]/", "", $payer));
          $queryBuilder->setParameter('myCompanies',  $myCompanies);
        }

        break;
      case OrderLogistic::class:
        $queryBuilder->innerJoin(Order::class, 'O', 'WITH', sprintf('O.id = %s.SalesOrder', $rootAlias));
        $queryBuilder->andWhere('O.client IN (:client) OR O.provider IN (:provider)');
        $queryBuilder->setParameter('client', $this->getMyCompanies());
        $queryBuilder->setParameter('provider', $this->getMyCompanies());
        break;

      case  Order::class:
        $queryBuilder->andWhere(sprintf('%s.client IN (:client) OR %s.provider IN (:provider)', $rootAlias, $rootAlias));
        $queryBuilder->setParameter('client', $this->getMyCompanies());
        $queryBuilder->setParameter('provider', $this->getMyCompanies());
        break;

      case Hardware::class:
        $queryBuilder->andWhere(sprintf('%s.company IN(:company)', $rootAlias));
        $queryBuilder->setParameter('company', $this->isFilteringByMyCompany() ?  $this->getMyCompany() : $this->getMyCompanies());
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
          ->innerJoin('ControleOnline\Entity\MyContractPeople', 'contractPeople', 'WITH', 'contractPeople.contract = myContract')
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
          \ControleOnline\Entity\PeopleProvider::class,
          'people_provider',
          'WITH',
          sprintf('people_provider.provider = %s', $rootAlias)
        );
        $queryBuilder->andWhere('people_provider.company = :my_company');
        $queryBuilder->setParameter('my_company', $this->getMyCompany());

        $invoice_id = $this->request->query->get('invoiceId', null);
        if (!empty($invoice_id)) {
          $queryBuilder->innerJoin(Order::class, 'PO', 'WITH', sprintf('PO.provider = %s.id', $rootAlias));
          $queryBuilder->innerJoin(OrderInvoice::class, 'POI', 'WITH', 'POI.order = PO.id');
          //$queryBuilder->innerJoin(Invoice::class, 'I', 'WITH', 'I.id = POI.invoice');
          $queryBuilder->andWhere('POI.invoice IN(:invoice)');
          $queryBuilder->setParameter('invoice', $invoice_id);
        }



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
