<?php

declare(strict_types=1);

namespace App\Service;

use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpReadQueryProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly ContainerInterface $container,
        private readonly McpCompanyScopeProviderInterface $companyScopeProvider,
        private readonly string $timezone,
    ) {
    }

    public function getDatasets(): array
    {
        return [
            ['name' => 'sales', 'description' => 'Sales orders with date, total, and order type.'],
            ['name' => 'invoices', 'description' => 'Invoices with date, total, and invoice type.'],
            ['name' => 'products', 'description' => 'Products with name, price, type, and active state.'],
        ];
    }

    public function query(string $dataset, array $filters): array
    {
        $definition = self::DATASETS[$dataset] ?? null;
        if ($definition === null) {
            throw new \InvalidArgumentException('Unsupported dataset');
        }

        $companies = $this->companyScopeProvider->listForCurrentUser();
        $companyIds = array_values(array_map(static fn (array $company): int => (int) $company['id'], $companies));
        if ($companyIds === []) {
            return ($filters['aggregate'] ?? false) ? [['count' => 0, 'total' => 0.0]] : [];
        }

        $requestedCompanyId = $filters['company_id'] ?? null;
        if ($requestedCompanyId !== null && !in_array($requestedCompanyId, $companyIds, true)) {
            return ($filters['aggregate'] ?? false) ? [['count' => 0, 'total' => 0.0]] : [];
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new \RuntimeException('MCP query requires an active authenticated request');
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
        if ($dataset === 'products' && ($role !== null || $filters['from'] !== null || $filters['to'] !== null)) {
            throw new \InvalidArgumentException('Product queries do not support company roles or date filters');
        }
        if ($requestedCompanyId !== null && $role !== null) {
            $securityQuery[$this->roleFilter($dataset, $role)] = $requestedCompanyId;
        }
        $request->query->replace($securityQuery);

        try {
            $alias = 'o';
            $queryBuilder = $this->entityManager
                ->getRepository($definition['class'])
                ->createQueryBuilder($alias)
                ->select($definition['selection']);

            $securityService = $this->container->get($definition['security_service']);
            $securityService->securityFilter($queryBuilder, $definition['class'], 'collection', $alias);

            if ($dataset === 'sales') {
                $queryBuilder->andWhere('o.orderType = :mcpOrderType')
                    ->setParameter('mcpOrderType', 'sale');
                $queryBuilder->leftJoin('o.status', 'mcpStatus')
                    ->andWhere('mcpStatus.realStatus = :mcpClosedStatus')
                    ->setParameter('mcpClosedStatus', 'closed');
            } elseif ($dataset === 'products') {
                $queryBuilder->andWhere('IDENTITY(o.company) IN (:mcpCompanies)')
                    ->setParameter('mcpCompanies', $companyIds);
            }

            if ($requestedCompanyId !== null && $role === null) {
                $fields = match ($dataset) {
                    'sales' => ['IDENTITY(o.client)', 'IDENTITY(o.provider)'],
                    'invoices' => ['IDENTITY(o.payer)', 'IDENTITY(o.receiver)'],
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
                $queryBuilder->orderBy($dataset === 'products' ? 'o.id' : 'o.' . $definition['date_field'], 'DESC')
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

            return $rows;
        } finally {
            $request->query->replace($originalQuery);
        }
    }

    private function roleFilter(string $dataset, string $role): string
    {
        $allowed = match ($dataset) {
            'sales' => ['customer' => 'client', 'supplier' => 'provider'],
            'invoices' => ['payer' => 'payer', 'receiver' => 'receiver'],
            'products' => [],
        };

        if (!isset($allowed[$role])) {
            throw new \InvalidArgumentException('Company role is not supported for this dataset');
        }

        return $allowed[$role];
    }
}
