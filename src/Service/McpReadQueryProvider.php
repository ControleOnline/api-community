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
 * Tenant-local, company-scoped MCP queries. Detailed rows use the domain's
 * read serialization groups and are sanitized before being returned to MCP.
 */
final class McpReadQueryProvider implements McpReadQueryProviderInterface
{
    private const DATASETS = [
        'sales' => [
            'class' => 'ControleOnline\\Entity\\Order',
            'security_service' => 'ControleOnline\\Service\\OrderService',
            'date_field' => 'orderDate',
            'selection' => 'o.id AS id, o.orderDate AS date, o.price AS total, o.orderType AS type',
            'detail_group' => 'order_details:read',
        ],
        'orders' => [
            'class' => 'ControleOnline\\Entity\\Order',
            'security_service' => 'ControleOnline\\Service\\OrderService',
            'date_field' => 'orderDate',
            'selection' => 'o.id AS id, o.orderDate AS date, o.price AS total, o.orderType AS type',
            'detail_group' => 'order_details:read',
        ],
        'invoices' => [
            'class' => 'ControleOnline\\Entity\\Invoice',
            'security_service' => 'ControleOnline\\Service\\InvoiceService',
            'date_field' => 'invoice_date',
            'selection' => 'o.id AS id, o.invoice_date AS date, o.price AS total, o.invoiceType AS type',
            'detail_group' => 'invoice_details:read',
        ],
        'products' => [
            'class' => 'ControleOnline\\Entity\\Product',
            'security_service' => 'ControleOnline\\Service\\ProductService',
            'date_field' => null,
            'selection' => 'o.id AS id, o.product AS name, o.price AS price, o.type AS type, o.active AS active',
            'detail_group' => 'product:read',
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
            ['name' => 'sales', 'description' => 'Detailed sales orders, including linked customer/supplier, status, products, quantities, prices, and preparation details.'],
            ['name' => 'orders', 'description' => 'Detailed orders of all types, including linked people and order lines.'],
            ['name' => 'invoices', 'description' => 'Detailed invoices, including linked payer/receiver, status, dates, payment details, and amounts.'],
            ['name' => 'products', 'description' => 'Detailed catalog products, including the fields exposed by the domain product read model.'],
            ['name' => 'inventory', 'description' => 'Inventory quantities and thresholds for products in companies the user can access.'],
            ['name' => 'wallets', 'description' => 'Wallet names and current balances for companies the user can access.'],
            ['name' => 'employees', 'description' => 'Employee profiles, contacts, and available documents, only through an active people_link to an accessible company.'],
            ['name' => 'clients', 'description' => 'Client profiles, contacts, and available documents, only through an active people_link to an accessible company or an authorized order/invoice.'],
            ['name' => 'suppliers', 'description' => 'Supplier profiles, contacts, and available documents, only through an active people_link to an accessible company or an authorized order/invoice.'],
            ['name' => 'salespeople', 'description' => 'Salesperson profiles, contacts, and available documents, only through an active people_link to an accessible company.'],
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
        $groupBy = $filters['group_by'] ?? [];
        if ($aggregate && !in_array($dataset, ['sales', 'orders', 'invoices', 'products', 'inventory', 'wallets'], true)) {
            throw new \InvalidArgumentException('Aggregates are not available for this dataset');
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

            $detailed = !empty($definition['detail_group']) && !$aggregate;
            if ($detailed) {
                $queryBuilder->select('o');
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

            if (isset($filters['record_id'])) {
                $queryBuilder->andWhere('o.id = :mcpRecordId')->setParameter('mcpRecordId', $filters['record_id']);
            }

            if ($aggregate) {
                // Aggregate only after the tenant, company scope, and securityFilter have constrained the query.
                $this->applyAggregate($queryBuilder, $dataset, $definition, $groupBy, $companyIds);
            } else {
                $queryBuilder->orderBy(in_array($dataset, ['products', 'inventory', 'wallets'], true) ? 'o.id' : 'o.' . ($definition['date_field'] ?? 'id'), 'DESC')
                    ->setFirstResult((int) ($filters['offset'] ?? 0))
                    ->setMaxResults((int) $filters['limit']);
            }

            if ($detailed) {
                $entities = $queryBuilder->getQuery()->getResult();
                $rows = [];
                foreach ($entities as $entity) {
                    $row = $this->container->get(McpSafeEntityNormalizer::class)->normalize($entity, $definition['detail_group']);
                    if ($dataset === 'sales' || $dataset === 'orders') {
                        $row['client_id'] = $row['client']['id'] ?? null;
                        $row['client_name'] = $row['client']['name'] ?? $row['client']['alias'] ?? null;
                        $row['provider_id'] = $row['provider']['id'] ?? null;
                        $row['provider_name'] = $row['provider']['name'] ?? $row['provider']['alias'] ?? null;
                    } elseif ($dataset === 'invoices') {
                        $row['payer_id'] = $row['payer']['id'] ?? null;
                        $row['payer_name'] = $row['payer']['name'] ?? $row['payer']['alias'] ?? null;
                        $row['receiver_id'] = $row['receiver']['id'] ?? null;
                        $row['receiver_name'] = $row['receiver']['name'] ?? $row['receiver']['alias'] ?? null;
                    }
                    $rows[] = $row;
                }
            } else {
                $rows = $queryBuilder->getQuery()->getArrayResult();
            }
            if ($aggregate) {
                foreach ($rows as &$row) {
                    $row['count'] = (int) $row['count'];
                    foreach (['total', 'total_price', 'available', 'sales', 'purchases', 'transit', 'balance'] as $numericField) {
                        if (isset($row[$numericField])) {
                            $row[$numericField] = (float) $row[$numericField];
                        }
                    }
                }
                unset($row);
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

    /** @param list<string> $groupBy @param list<int> $companyIds */
    private function applyAggregate(object $queryBuilder, string $dataset, array $definition, array $groupBy, array $companyIds): void
    {
        $dateField = $definition['date_field'] ?? null;
        $typeField = match ($dataset) {
            'sales', 'orders' => 'o.orderType',
            'invoices' => 'o.invoiceType',
            'products' => 'o.type',
            default => null,
        };
        $companyField = match ($dataset) {
            'sales', 'orders' => 'CASE WHEN IDENTITY(o.client) IN (:mcpCompanies) THEN IDENTITY(o.client) ELSE IDENTITY(o.provider) END',
            'invoices' => 'CASE WHEN IDENTITY(o.payer) IN (:mcpCompanies) THEN IDENTITY(o.payer) ELSE IDENTITY(o.receiver) END',
            'products' => 'IDENTITY(o.company)',
            'inventory' => 'IDENTITY(mcpInventory.people)',
            'wallets' => 'IDENTITY(o.people)',
            default => null,
        };
        $expressions = [];
        foreach ($groupBy as $dimension) {
            $expression = match ($dimension) {
                'day' => $dateField !== null ? 'SUBSTRING(o.' . $dateField . ', 1, 10)' : null,
                'month' => $dateField !== null ? 'SUBSTRING(o.' . $dateField . ', 1, 7)' : null,
                'type' => $typeField,
                'company_id' => $companyField,
                'product' => $dataset === 'inventory' ? 'mcpProduct.product' : null,
                'wallet' => $dataset === 'wallets' ? 'o.wallet' : null,
                'active' => $dataset === 'products' ? 'o.active' : null,
                default => null,
            };
            if ($expression === null) {
                throw new \InvalidArgumentException('Unsupported aggregate grouping for dataset');
            }
            $expressions[$dimension] = $expression;
        }

        $select = ['COUNT(DISTINCT o.id) AS count'];
        if (in_array($dataset, ['sales', 'orders', 'invoices'], true)) {
            if ($dataset === 'sales') {
                $queryBuilder->join('o.orderProducts', 'mcpOrderProduct')
                    ->andWhere('mcpOrderProduct.orderProduct IS NULL');
                $select[] = 'COALESCE(SUM(mcpOrderProduct.total), 0) AS total';
            } else {
                $select[] = 'COALESCE(SUM(o.price), 0) AS total';
            }
        } elseif ($dataset === 'products') {
            $select[] = 'COALESCE(SUM(o.price), 0) AS total_price';
        } elseif ($dataset === 'inventory') {
            $select[] = 'COALESCE(SUM(o.available), 0) AS available';
            $select[] = 'COALESCE(SUM(o.sales), 0) AS sales';
            $select[] = 'COALESCE(SUM(o.purchases), 0) AS purchases';
            $select[] = 'COALESCE(SUM(o.transit), 0) AS transit';
        } elseif ($dataset === 'wallets') {
            $select[] = 'COALESCE(SUM(o.balance), 0) AS balance';
        }

        foreach ($expressions as $dimension => $expression) {
            $select[] = $expression . ' AS ' . $dimension;
            $queryBuilder->addGroupBy($expression);
        }
        $queryBuilder->select(implode(', ', $select));
        if (in_array($dataset, ['sales', 'orders', 'invoices'], true) && in_array('company_id', $groupBy, true)) {
            $queryBuilder->setParameter('mcpCompanies', $companyIds);
        }
        if ($groupBy !== []) {
            $queryBuilder->setMaxResults(100);
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
        return $this->container->get(McpPeopleLinkReadQueryProvider::class)
            ->query($dataset, $companyIds, $requestedCompanyId, $filters, $request);
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
