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

use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\Orders;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Entity\Display;
use ControleOnline\Entity\OrderProduct;
use ControleOnline\Entity\PaymentType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Product;
use ControleOnline\Entity\ProductGroup;
use ControleOnline\Entity\Wallet;
use Exception;

final class FilterCollectionByExtension
implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
  private $security;

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

    if (empty($this->security->getUser()))
      return;

    if ($this->security->isGranted('ROLE_ADMIN')) {
      return;
    }

    $rootAlias = $queryBuilder->getRootAliases()[0];

    switch ($resourceClass) {
      case Wallet::class:
        $this->checkCompany('people', $queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case PaymentType::class:
        $this->checkCompany('people', $queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case Product::class:
        $this->checkCompany('company', $queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case Invoice::class:
        $this->invoice($queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case People::class:
        $this->checkLink($queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case Display::class:
        $this->checkCompany('company', $queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case Orders::class:
        $this->orders($queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case OrderProduct::class:
        $this->orderProduct($queryBuilder, $resourceClass, $applyTo, $rootAlias);
        break;
      case ProductGroup::class:
        $this->checkProductGroup($queryBuilder, $resourceClass, $applyTo, $rootAlias);
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

    if (!$currentUser->getPeople()->getLink()->isEmpty()) {
      foreach ($currentUser->getPeople()->getLink() as $company) {
        $companies[] = $company->getCompany();
      }
    }
    return $companies;
  }

  private function checkProductGroup(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    if ($product = $this->request->query->get('product', null)) {
      $queryBuilder->join(sprintf('%s.products', $rootAlias), 'productGroupProduct');
      $queryBuilder->join('productGroupProduct.product', 'product');
      $queryBuilder->andWhere('product.id = :product');
      $queryBuilder->setParameter('product', $product);
    }
  }

  private function  orderProduct(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    $queryBuilder->join(sprintf('%s.order', $rootAlias), 'o');
    $queryBuilder->andWhere('o.client IN(:companies) OR o.provider IN(:companies)');
    $companies   = $this->getMyCompanies();
    $queryBuilder->setParameter('companies', $companies);
  }

  private function orders(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    $companies   = $this->getMyCompanies();
    $queryBuilder->andWhere(sprintf('%s.client IN(:companies) OR %s.provider IN(:companies)', $rootAlias, $rootAlias));
    $queryBuilder->setParameter('companies', $companies);

    if ($provider = $this->request->query->get('provider', null)) {
      $queryBuilder->andWhere(sprintf('%s.provider IN(:provider)', $rootAlias));
      $queryBuilder->setParameter('provider', preg_replace("/[^0-9]/", "", $provider));
    }

    if ($client = $this->request->query->get('client', null)) {
      $queryBuilder->andWhere(sprintf('%s.client IN(:client)', $rootAlias));
      $queryBuilder->setParameter('client', preg_replace("/[^0-9]/", "", $client));
    }
  }


  private function invoice(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    $companies   = $this->getMyCompanies();
    $queryBuilder->andWhere(sprintf('%s.payer IN(:companies) OR %s.receiver IN(:companies)', $rootAlias, $rootAlias));
    $queryBuilder->setParameter('companies', $companies);

    if ($payer = $this->request->query->get('payer', null)) {
      $queryBuilder->andWhere(sprintf('%s.payer IN(:payer)', $rootAlias));
      $queryBuilder->setParameter('payer', preg_replace("/[^0-9]/", "", $payer));
    }

    if ($receiver = $this->request->query->get('receiver', null)) {
      $queryBuilder->andWhere(sprintf('%s.receiver IN(:receiver)', $rootAlias));
      $queryBuilder->setParameter('receiver', preg_replace("/[^0-9]/", "", $receiver));
    }
  }
  private function checkLink(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {

    $link   = $this->request->query->get('link',   null);
    $company = $this->request->query->get('company', null);
    $link_type = $this->request->query->get('link_type', null);

    if ($link_type) {
      $queryBuilder->join(sprintf('%s.' . ($link ? 'company' : 'link'), $rootAlias), 'PeopleLink');
      $queryBuilder->andWhere('PeopleLink.link_type IN(:link_type)');
      $queryBuilder->setParameter('link_type', $link_type);
    }

    if ($company || $link) {
      $queryBuilder->andWhere('PeopleLink.' . ($link ? 'people' : 'company') . ' IN(:people)');
      $queryBuilder->setParameter('people', preg_replace("/[^0-9]/", "", ($link ?: $company)));
    }
  }
  private function checkCompany($type, QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
  {
    $companies   = $this->getMyCompanies();
    $queryBuilder->andWhere(sprintf('%s.' . $type . ' IN(:companies)', $rootAlias, $rootAlias));
    $queryBuilder->setParameter('companies', $companies);

    if ($payer = $this->request->query->get('company', null)) {
      $queryBuilder->andWhere(sprintf('%s.' . $type . ' IN(:people)', $rootAlias));
      $queryBuilder->setParameter('people', preg_replace("/[^0-9]/", "", $payer));
    }
  }
}
