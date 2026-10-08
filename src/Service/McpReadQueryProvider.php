<?php

declare(strict_types=1);

namespace App\Service;

use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpReadQueryProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * Curated MCP read models. Every collection is tenant-local, company scoped,
 * and projected to a fixed set of fields that excludes document and contact data.
 */
final class McpReadQueryProvider implements McpReadQueryProviderInterface
{
    private const DATASETS = [
        'sales' => [
            'class' => 'ControleOnline\\Entity\\Order',
            'security_service' => 'ControleOnline\\Service\\OrderService',
            'date_field' => 'orderDate',
            'selection' => 'o.id AS id, o.orderDate AS date, o.price AS total, o.orderType AS type',
        ],
        'orders' => [
            'class' => 'ControleOnline\\Entity\\Order',
            'security_service' => 'ControleOnline\\Service\\OrderService',
            'date_field' => 'orderDate',
            'selection' => 'o.id AS id, o.orderDate AS date, o.price AS total, o.orderType AS type',
        ],
        'invoices' => [
            'class' => 'ControleOnline\\Entity\\Invoice',
            'security_service' => 'ControleOnline\\Service\\InvoiceService',
            'date_field' => 'invoice_date',
            'selection' => 'o.id AS id, o.invoice_date AS date, o.price AS total, o.invoiceType AS type',
        ],
        'products' => [
            'class' => 'ControleOnline\\Entity\\Product',
            'security_service' => 'ControleOnline\\Service\\ProductService',
            'date_field' => null,
            'selection' => 'o.id AS id, o.product AS name, o.price AS price, o.type AS type, o.active AS active',
        ],
        'inventory' => [
            'class' => 'ControleOnline\\Entity\\ProductInventory',
            'security_service' => 'ControleOnline\\Service\\ProductService',
            'date_field' => null,
            'selection' => 'mcpProduct.product AS product, mcpInventory.inventory AS inventory, o.available AS available, o.sales AS sales, o.purchases AS purchases, o.transit AS transit, o.minimum AS minimum, o.maximum AS maximum',
        ],
        'wallets' => [
            'class' => 'ControleOnline\\Entity\\Wallet',
            'security_service' => 'ControleOnline\\Service\\WalletService',
            'date_field' => null,
            'selection' => 'o.wallet AS wallet, o.balance AS balance',
        ],
        'employees' => ['class' => 'ControleOnline\\Entity\\PeopleLink'],
        'clients' => ['class' => 'ControleOnline\\Entity\\PeopleLink'],
        'suppliers' => ['class' => 'ControleOnline\\Entity\\PeopleLink'],
        'salespeople' => ['class' => 'ControleOnline\\Entity\\PeopleLink'],
        'commissions' => ['class' => 'ControleOnline\\Entity\\PeopleLink'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly ContainerInterface $container,
        private readonly McpCompanyScopeProviderInterface $companyScopeProvider,
        private readonly string $timezone,
        private readonly ?TokenStorageInterface $tokenStorage = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?McpOperationalReadQueryProvider $operationalQueryProvider = null,
    ) {
    }

    public function getDatasets(): array
    {
        return [
            ['name' => 'sales', 'description' => 'Sales orders with date, total, and order type.'],
            ['name' => 'orders', 'description' => 'Orders of all types with date, total, and order type, including drafts and purchases.'],
            ['name' => 'invoices', 'description' => 'Invoices with date, total, and invoice type.'],
            ['name' => 'products', 'description' => 'Products with name, price, type, and active state.'],
            ['name' => 'inventory', 'description' => 'Inventory quantities and thresholds for products in companies the user can access.'],
            ['name' => 'wallets', 'description' => 'Wallet names and current balances for companies the user can access.'],
            ['name' => 'employees', 'description' => 'Employee names and roles linked to companies the user can access.'],
            ['name' => 'clients', 'description' => 'Client names linked to companies the user can access.'],
            ['name' => 'suppliers', 'description' => 'Supplier names linked to companies the user can access.'],
            ['name' => 'salespeople', 'description' => 'Salesperson names linked to companies the user can access.'],
            ['name' => 'commissions', 'description' => 'Commission rates and minimums for salespeople in companies the user can manage.'],
            ['name' => 'configs', 'description' => 'Metadata for the devices configuration key only; configuration values and secrets are never returned.'],
            ['name' => 'devices', 'description' => 'Device aliases and types configured for companies the user can access; device credentials are excluded.'],
            ['name' => 'displays', 'description' => 'Production display names, types, companies and assigned queues for companies the user can access.'],
            ['name' => 'production_queue', 'description' => 'Preparation items for sale orders, with product, queue, preparation status and timestamps.'],
        ];
    }

    public function query(string $dataset, array $filters): array
    {
        $definition = self::DATASETS[$dataset] ?? null;
        if ($definition === null && !$this->operationalQueryProvider?->supports($dataset)) {
            throw new \InvalidArgumentException('Unsupported dataset');
        }

        $companies = $this->companyScopeProvider->listForCurrentUser();
        $companyIds = array_values(array_map(static fn (array $company): int => (int) $company['id'], $companies));
        if ($companyIds === []) {
            $this->auditQuery($dataset, [], false, 0, 'empty_scope');
            return ($filters['aggregate'] ?? false) ? [['count' => 0, 'total' => 0.0]] : [];
        }

        $requestedCompanyId = $filters['company_id'] ?? null;
        if ($requestedCompanyId !== null && !in_array($requestedCompanyId, $companyIds, true)) {
            $this->auditQuery($dataset, [$requestedCompanyId], false, 0, 'denied_company_scope');
            return ($filters['aggregate'] ?? false) ? [['count' => 0, 'total' => 0.0]] : [];
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new \RuntimeException('MCP query requires an active authenticated request');
        }

        if ($this->operationalQueryProvider?->supports($dataset)) {
            return $this->operationalQueryProvider->query($dataset, $companyIds, $requestedCompanyId, $filters, $request);
        }

        if (in_array($dataset, ['employees', 'clients', 'suppliers', 'salespeople', 'commissions'], true)) {
            return $this->queryPeopleLinks($dataset, $companyIds, $requestedCompanyId, $filters, $request);
        }

        $originalQuery = $request->query->all();
        $securityQuery = [];
        $role = $filters['company_role'] ?? null;
        $aggregate = (bool) ($filters['aggregate'] ?? false);
        if ($aggregate && $dataset !== 'sales') {
            throw new \InvalidArgumentException('Only closed sales can be aggregated');
        }
        if ($role !== null && $requestedCompanyId === null) {
            throw new \InvalidArgumentException('company_id is required when company_role is set');
        }
        if (in_array($dataset, ['products', 'inventory', 'wallets'], true)
            && ($role !== null || ($filters['from'] ?? null) !== null || ($filters['to'] ?? null) !== null)) {
            throw new \InvalidArgumentException('Product and inventory queries do not support company roles or date filters');
        }
        if ($requestedCompanyId !== null && $role !== null) {
            $securityQuery[$this->roleFilter($dataset, $role)] = $requestedCompanyId;
        }
        $request->query->replace($securityQuery);

        try {
            $alias = 'o';
            $queryBuilder = $this->entityManager
                ->getRepository($definition['class'])
                ->createQueryBuilder($alias);
            if ($dataset === 'inventory') {
                $queryBuilder->join('o.product', 'mcpProduct')
                    ->join('o.inventory', 'mcpInventory')
                    ->select($definition['selection']);
            } else {
                $queryBuilder->select($definition['selection']);
            }

            $securityService = $this->container->get($definition['security_service']);
            $securityResourceClass = $dataset === 'inventory'
                ? 'ControleOnline\\Entity\\Product'
                : $definition['class'];
            $securityAlias = $dataset === 'inventory' ? 'mcpProduct' : $alias;
            $securityService->securityFilter($queryBuilder, $securityResourceClass, 'collection', $securityAlias);

            if (in_array($dataset, ['sales', 'orders'], true)) {
                $queryBuilder->andWhere('(IDENTITY(o.client) IN (:mcpCompanies) OR IDENTITY(o.provider) IN (:mcpCompanies))')
                    ->setParameter('mcpCompanies', $companyIds);
                if ($dataset === 'sales') {
                    $queryBuilder->andWhere('o.orderType = :mcpOrderType')
                        ->setParameter('mcpOrderType', 'sale')
                        ->leftJoin('o.status', 'mcpStatus')
                        ->andWhere('mcpStatus.realStatus = :mcpClosedStatus')
                        ->setParameter('mcpClosedStatus', 'closed');
                }
            } elseif ($dataset === 'invoices') {
                $queryBuilder->andWhere('(IDENTITY(o.payer) IN (:mcpCompanies) OR IDENTITY(o.receiver) IN (:mcpCompanies))')
                    ->setParameter('mcpCompanies', $companyIds);
            } elseif ($dataset === 'products') {
                $queryBuilder->andWhere('IDENTITY(o.company) IN (:mcpCompanies)')
                    ->setParameter('mcpCompanies', $companyIds);
            } elseif ($dataset === 'inventory') {
                $queryBuilder->andWhere('IDENTITY(mcpInventory.people) IN (:mcpCompanies)')
                    ->andWhere('IDENTITY(mcpProduct.company) IN (:mcpCompanies)')
                    ->setParameter('mcpCompanies', $companyIds);
            } elseif ($dataset === 'wallets') {
                $queryBuilder->andWhere('IDENTITY(o.people) IN (:mcpCompanies)')
                    ->setParameter('mcpCompanies', $companyIds);
            }

            if ($requestedCompanyId !== null && $role === null) {
                $fields = match ($dataset) {
                    'sales', 'orders' => ['IDENTITY(o.client)', 'IDENTITY(o.provider)'],
                    'invoices' => ['IDENTITY(o.payer)', 'IDENTITY(o.receiver)'],
                    'inventory' => ['IDENTITY(mcpInventory.people)'],
                    'wallets' => ['IDENTITY(o.people)'],
                    default => ['IDENTITY(o.company)'],
                };
                $queryBuilder->andWhere(implode(' OR ', array_map(static fn (string $field): string => $field . ' = :mcpCompany', $fields)))
                    ->setParameter('mcpCompany', $requestedCompanyId);
            }

            if ($definition['date_field'] !== null) {
                $dateField = 'o.' . $definition['date_field'];
                if (is_string($filters['from'] ?? null)) {
                    $queryBuilder->andWhere($dateField . ' >= :mcpFrom')
                        ->setParameter('mcpFrom', new \DateTimeImmutable($filters['from'] . ' 00:00:00', new \DateTimeZone($this->timezone)));
                }
                if (is_string($filters['to'] ?? null)) {
                    $toExclusive = (new \DateTimeImmutable($filters['to'] . ' 00:00:00', new \DateTimeZone($this->timezone)))->modify('+1 day');
                    $queryBuilder->andWhere($dateField . ' < :mcpTo')
                        ->setParameter('mcpTo', $toExclusive);
                }
            }

            if ($aggregate) {
                // Replace the row projection only after all company security filters are applied.
                $queryBuilder
                    ->join('o.orderProducts', 'mcpOrderProduct')
                    ->andWhere('mcpOrderProduct.orderProduct IS NULL')
                    ->select('COUNT(DISTINCT o.id) AS count, COALESCE(SUM(mcpOrderProduct.total), 0) AS total');
            } else {
                $queryBuilder->orderBy(in_array($dataset, ['products', 'inventory', 'wallets'], true) ? 'o.id' : 'o.' . $definition['date_field'], 'DESC')
                    ->setMaxResults((int) $filters['limit']);
            }

            $rows = $queryBuilder->getQuery()->getArrayResult();
            if ($aggregate && isset($rows[0])) {
                $rows[0]['count'] = (int) $rows[0]['count'];
                $rows[0]['total'] = (float) $rows[0]['total'];
            }
            foreach ($rows as &$row) {
                if (($row['date'] ?? null) instanceof \DateTimeInterface) {
                    $row['date'] = $row['date']->format(\DateTimeInterface::ATOM);
                }
            }
            unset($row);

            $this->auditQuery(
                $dataset,
                $requestedCompanyId === null ? $companyIds : [$requestedCompanyId],
                true,
                $aggregate && isset($rows[0]['count']) ? (int) $rows[0]['count'] : count($rows),
                'success',
            );

            return $rows;
        } catch (\Throwable $exception) {
            $this->auditQuery(
                $dataset,
                $requestedCompanyId === null ? $companyIds : [$requestedCompanyId],
                true,
                0,
                'query_error:' . $exception::class,
            );
            throw $exception;
        } finally {
            $request->query->replace($originalQuery);
        }
    }

    private function roleFilter(string $dataset, string $role): string
    {
        $allowed = match ($dataset) {
            'sales', 'orders' => ['customer' => 'client', 'supplier' => 'provider'],
            'invoices' => ['payer' => 'payer', 'receiver' => 'receiver'],
            'products' => [],
            'inventory' => [],
            'wallets' => [],
            default => [],
        };

        if (!isset($allowed[$role])) {
            throw new \InvalidArgumentException('Company role is not supported for this dataset');
        }

        return $allowed[$role];
    }

    /** @param list<int> $companyIds
     *  @param array<string, mixed> $filters
     *  @return list<array<string, mixed>>
     */
    private function queryPeopleLinks(string $dataset, array $companyIds, ?int $requestedCompanyId, array $filters, Request $request): array
    {
        if (($filters['from'] ?? null) !== null || ($filters['to'] ?? null) !== null
            || ($filters['company_role'] ?? null) !== null || ($filters['aggregate'] ?? false)) {
            throw new \InvalidArgumentException('People and commission queries do not support date, role, or aggregate filters');
        }

        $originalQuery = $request->query->all();
        $request->query->replace($dataset === 'commissions' || $requestedCompanyId === null
            ? []
            : ['company' => $requestedCompanyId]);

        try {
            $queryBuilder = $this->entityManager
                ->getRepository('ControleOnline\\Entity\\PeopleLink')
                ->createQueryBuilder('o');
            $peopleLinkService = $this->container->get('ControleOnline\\Service\\PeopleLinkService');
            $peopleLinkService->securityFilter($queryBuilder, 'ControleOnline\\Entity\\PeopleLink', 'collection', 'o');
            $queryBuilder->andWhere('o.enable = :mcpLinkEnabled')
                ->setParameter('mcpLinkEnabled', true)
                ->andWhere('LOWER(o.linkType) LIKE :mcpLinkType');

            $linkTypes = [
                'employees' => 'employee',
                'clients' => 'client',
                'suppliers' => 'provider',
                'salespeople' => 'salesman',
                'commissions' => 'sellers-client',
            ];
            $queryBuilder->setParameter('mcpLinkType', '%' . $linkTypes[$dataset] . '%');

            if ($dataset === 'commissions') {
                $queryBuilder->join(
                    'ControleOnline\\Entity\\PeopleLink',
                    'mcpSalespersonCompany',
                    'WITH',
                    'mcpSalespersonCompany.people = o.company AND LOWER(mcpSalespersonCompany.linkType) LIKE :mcpSalesmanRole'
                )
                    ->andWhere('IDENTITY(mcpSalespersonCompany.company) IN (:mcpCompanies)')
                    ->setParameter('mcpSalesmanRole', '%salesman%')
                    ->setParameter('mcpCompanies', $requestedCompanyId === null ? $companyIds : [$requestedCompanyId])
                    ->join('mcpSalespersonCompany.company', 'mcpEmployer')
                    ->addSelect('mcpEmployer.id AS employer_id, mcpEmployer.name AS employer_name')
                    ->orderBy('o.id', 'DESC')
                    ->setMaxResults((int) $filters['limit']);

                $rows = [];
                foreach ($queryBuilder->getQuery()->getResult() as $result) {
                    $link = is_array($result) ? ($result[0] ?? null) : $result;
                    if (!$link instanceof \ControleOnline\Entity\PeopleLink) {
                        continue;
                    }
                    if (!$peopleLinkService->canViewSalesmanCommissions($link)) {
                        continue;
                    }
                    $rows[] = [
                        'company_id' => (int) (is_array($result) ? ($result['employer_id'] ?? 0) : 0),
                        'company' => (string) (is_array($result) ? ($result['employer_name'] ?? '') : ''),
                        'salesperson' => (string) ($link->getCompany()?->getName() ?? ''),
                        'client' => (string) ($link->getPeople()?->getName() ?? ''),
                        'commission_rate' => $link->getComission(),
                        'minimum_commission' => $link->getMinimumComission(),
                    ];
                }
            } else {
                $queryBuilder->join('o.company', 'mcpLinkCompany')
                    ->join('o.people', 'mcpLinkedPerson')
                    ->andWhere('IDENTITY(o.company) IN (:mcpCompanies)')
                    ->setParameter('mcpCompanies', $requestedCompanyId === null ? $companyIds : [$requestedCompanyId])
                    ->select('mcpLinkCompany.id AS company_id, mcpLinkCompany.name AS company, mcpLinkedPerson.id AS person_id, mcpLinkedPerson.name AS name, mcpLinkedPerson.alias AS alias, o.linkType AS relationship')
                    ->orderBy('mcpLinkedPerson.name', 'ASC')
                    ->setMaxResults((int) $filters['limit']);
                $rows = $queryBuilder->getQuery()->getArrayResult();
            }

            $this->auditQuery($dataset, $requestedCompanyId === null ? $companyIds : [$requestedCompanyId], true, count($rows), 'success');
            return $rows;
        } catch (\Throwable $exception) {
            $this->auditQuery(
                $dataset,
                $requestedCompanyId === null ? $companyIds : [$requestedCompanyId],
                true,
                0,
                'query_error:' . $exception::class,
            );
            throw $exception;
        } finally {
            $request->query->replace($originalQuery);
        }
    }

    /** @param list<int> $companyIds */
    private function auditQuery(string $dataset, array $companyIds, bool $companyScopeAuthorized, int $resultCount, string $outcome): void
    {
        if ($this->logger === null) {
            return;
        }

        $user = $this->tokenStorage?->getToken()?->getUser();
        $userId = is_object($user) && method_exists($user, 'getId') ? $user->getId() : null;

        $this->logger->info('MCP read query', [
            'occurred_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'user_id' => $userId,
            'tool' => 'query_business_data',
            'dataset' => $dataset,
            'company_ids' => $companyIds,
            'company_scope_authorized' => $companyScopeAuthorized,
            'result_count' => $resultCount,
            'outcome' => $outcome,
        ]);
    }
}
