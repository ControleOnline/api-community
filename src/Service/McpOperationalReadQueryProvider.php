<?php

declare(strict_types=1);

namespace App\Service;

use ControleOnline\Service\McpCompanyScopeProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

/** Read-only operational projections with the domain security filters applied. */
final class McpOperationalReadQueryProvider
{
    private const DATASETS = ['configs', 'devices', 'displays', 'production_queue'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly ContainerInterface $container,
        private readonly ?TokenStorageInterface $tokenStorage = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly string $timezone = 'UTC',
    ) {
    }

    public function supports(string $dataset): bool
    {
        return in_array($dataset, self::DATASETS, true);
    }

    /** @param list<int> $companyIds @param array<string, mixed> $filters */
    public function query(string $dataset, array $companyIds, ?int $companyId, array $filters, Request $request): array
    {
        if (!$this->supports($dataset)) {
            throw new \InvalidArgumentException('Unsupported operational dataset');
        }
        if (($filters['company_role'] ?? null) !== null) {
            throw new \InvalidArgumentException('Company roles are not supported for this dataset');
        }
        if ($dataset !== 'production_queue' && (($filters['from'] ?? null) !== null || ($filters['to'] ?? null) !== null)) {
            throw new \InvalidArgumentException('Date filters are only supported for production_queue');
        }

        $originalQuery = $request->query->all();
        $request->query->replace($this->requestFilters($dataset, $companyId));
        try {
            $rows = match ($dataset) {
                'configs' => $this->queryDeviceConfigs($companyIds, $companyId, $filters),
                'devices' => $this->queryDevices($companyIds, $companyId, $filters),
                'displays' => $this->queryDisplays($companyIds, $companyId, $filters),
                'production_queue' => $this->queryProductionQueue($companyIds, $companyId, $filters),
            };
            if (($filters['aggregate'] ?? false) === true) {
                foreach ($rows as &$row) {
                    $row['count'] = (int) $row['count'];
                    if (isset($row['quantity'])) {
                        $row['quantity'] = (float) $row['quantity'];
                    }
                }
                unset($row);
            }
            $this->audit($dataset, $companyId === null ? $companyIds : [$companyId], count($rows), 'success');
            return $rows;
        } catch (\Throwable $exception) {
            $this->audit($dataset, $companyId === null ? $companyIds : [$companyId], 0, 'query_error:' . $exception::class);
            throw $exception;
        } finally {
            $request->query->replace($originalQuery);
        }
    }

    private function queryDeviceConfigs(array $companyIds, ?int $companyId, array $filters): array
    {
        $qb = $this->entityManager->getRepository('ControleOnline\\Entity\\Config')->createQueryBuilder('o')
            ->join('o.people', 'mcpCompany')
            ->select(($filters['aggregate'] ?? false) ? 'COUNT(DISTINCT o.id) AS count' : 'o.id AS config_id, IDENTITY(o.people) AS company_id, mcpCompany.name AS company, o.configKey AS config_key, o.visibility AS visibility')
            ->andWhere('o.configKey = :mcpDeviceConfigKey')
            ->setParameter('mcpDeviceConfigKey', 'devices')
            ->andWhere('IDENTITY(o.people) IN (:mcpCompanies)')
            ->setParameter('mcpCompanies', $companyIds);
        $this->container->get('ControleOnline\\Service\\ConfigService')
            ->securityFilter($qb, 'ControleOnline\\Entity\\Config', 'collection', 'o');
        $this->filterCompany($qb, 'IDENTITY(o.people)', $companyId);

        if (($filters['aggregate'] ?? false) === true && in_array('company_id', $filters['group_by'] ?? [], true)) {
            $qb->addSelect('IDENTITY(o.people) AS company_id')->groupBy('IDENTITY(o.people)');
        }

        return ($filters['aggregate'] ?? false) === true
            ? $qb->setMaxResults(100)->getQuery()->getArrayResult()
            : $qb->orderBy('mcpCompany.name', 'ASC')->setFirstResult((int) ($filters['offset'] ?? 0))->setMaxResults((int) ($filters['limit'] ?? 50))->getQuery()->getArrayResult();
    }

    private function queryDevices(array $companyIds, ?int $companyId, array $filters): array
    {
        $qb = $this->entityManager->getRepository('ControleOnline\\Entity\\DeviceConfig')->createQueryBuilder('o')
            ->join('o.device', 'mcpDevice')
            ->join('o.people', 'mcpCompany')
            ->select(($filters['aggregate'] ?? false) ? 'COUNT(DISTINCT o.id) AS count' : 'mcpDevice.id AS device_id, mcpDevice.alias AS alias, o.type AS type, IDENTITY(o.people) AS company_id, mcpCompany.name AS company')
            ->andWhere('IDENTITY(o.people) IN (:mcpCompanies)')
            ->setParameter('mcpCompanies', $companyIds);
        $this->container->get('ControleOnline\\Service\\DeviceConfigService')
            ->securityFilter($qb, 'ControleOnline\\Entity\\DeviceConfig', 'collection', 'o');
        $this->filterCompany($qb, 'IDENTITY(o.people)', $companyId);

        if (($filters['aggregate'] ?? false) === true) {
            foreach ($filters['group_by'] ?? [] as $dimension) {
                $expression = match ($dimension) {
                    'company_id' => 'IDENTITY(o.people)',
                    'type' => 'o.type',
                    default => throw new \InvalidArgumentException('Unsupported devices aggregate grouping'),
                };
                $qb->addSelect($expression . ' AS ' . $dimension)->addGroupBy($expression);
            }
        }

        return ($filters['aggregate'] ?? false) === true
            ? $qb->setMaxResults(100)->getQuery()->getArrayResult()
            : $qb->orderBy('mcpCompany.name', 'ASC')->addOrderBy('mcpDevice.alias', 'ASC')
                ->setFirstResult((int) ($filters['offset'] ?? 0))->setMaxResults((int) ($filters['limit'] ?? 50))->getQuery()->getArrayResult();
    }

    private function queryDisplays(array $companyIds, ?int $companyId, array $filters): array
    {
        $qb = $this->entityManager->getRepository('ControleOnline\\Entity\\Display')->createQueryBuilder('o')
            ->join('o.company', 'mcpCompany')
            ->leftJoin('o.displayQueue', 'mcpDisplayQueue')
            ->leftJoin('mcpDisplayQueue.queue', 'mcpQueue')
            ->select(($filters['aggregate'] ?? false) ? 'COUNT(DISTINCT o.id) AS count' : 'o.id AS display_id, o.display AS display, o.displayType AS type, IDENTITY(o.company) AS company_id, mcpCompany.name AS company, mcpQueue.queue AS queue')
            ->andWhere('IDENTITY(o.company) IN (:mcpCompanies)')
            ->setParameter('mcpCompanies', $companyIds);
        $this->container->get('ControleOnline\\Service\\DisplayService')
            ->securityFilter($qb, 'ControleOnline\\Entity\\Display', 'collection', 'o');
        $this->filterCompany($qb, 'IDENTITY(o.company)', $companyId);

        if (($filters['aggregate'] ?? false) === true) {
            foreach ($filters['group_by'] ?? [] as $dimension) {
                $expression = match ($dimension) {
                    'company_id' => 'IDENTITY(o.company)',
                    'type' => 'o.displayType',
                    default => throw new \InvalidArgumentException('Unsupported displays aggregate grouping'),
                };
                $qb->addSelect($expression . ' AS ' . $dimension)->addGroupBy($expression);
            }
        }

        return ($filters['aggregate'] ?? false) === true
            ? $qb->setMaxResults(100)->getQuery()->getArrayResult()
            : $qb->orderBy('mcpCompany.name', 'ASC')->addOrderBy('o.display', 'ASC')
                ->setFirstResult((int) ($filters['offset'] ?? 0))->setMaxResults((int) ($filters['limit'] ?? 50))->getQuery()->getArrayResult();
    }

    private function queryProductionQueue(array $companyIds, ?int $companyId, array $filters): array
    {
        $aggregate = ($filters['aggregate'] ?? false) === true;
        $qb = $this->entityManager->getRepository('ControleOnline\\Entity\\OrderProductQueue')->createQueryBuilder('o')
            ->join('o.order_product', 'mcpOrderProduct')
            ->join('mcpOrderProduct.order', 'mcpOrder')
            ->join('mcpOrderProduct.product', 'mcpProduct')
            ->join('o.queue', 'mcpQueue')
            ->leftJoin('o.status', 'mcpPreparationStatus')
            ->select($aggregate
                ? 'COUNT(DISTINCT o.id) AS count, COALESCE(SUM(mcpOrderProduct.quantity), 0) AS quantity'
                : 'o.id AS preparation_id, mcpOrder.id AS order_id, mcpProduct.product AS product, mcpOrderProduct.quantity AS quantity, mcpQueue.queue AS queue, mcpQueue.shortLabel AS queue_label, mcpPreparationStatus.status AS preparation_status, mcpPreparationStatus.realStatus AS preparation_real_status, o.registerTime AS registered_at, o.updateTime AS updated_at')
            ->andWhere('mcpOrder.orderType = :mcpSaleOrderType')
            ->setParameter('mcpSaleOrderType', 'sale')
            ->andWhere('(IDENTITY(mcpOrder.client) IN (:mcpCompanies) OR IDENTITY(mcpOrder.provider) IN (:mcpCompanies))')
            ->setParameter('mcpCompanies', $companyIds)
            ->andWhere('(mcpQueue.company IS NULL OR IDENTITY(mcpQueue.company) IN (:mcpCompanies))');
        $this->container->get('ControleOnline\\Service\\OrderService')
            ->securityFilter($qb, 'ControleOnline\\Entity\\Order', 'collection', 'mcpOrder');
        $this->filterCompany($qb, '(IDENTITY(mcpOrder.client) = :mcpCompany OR IDENTITY(mcpOrder.provider) = :mcpCompany)', $companyId, true);

        foreach (['from' => '>=', 'to' => '<'] as $key => $operator) {
            if (!is_string($filters[$key] ?? null)) {
                continue;
            }
            $date = new \DateTimeImmutable($filters[$key] . ' 00:00:00', new \DateTimeZone($this->timezone));
            if ($key === 'to') {
                $date = $date->modify('+1 day');
            }
            $qb->andWhere('o.registerTime ' . $operator . ' :mcp' . ucfirst($key))
                ->setParameter('mcp' . ucfirst($key), $date);
        }

        if ($aggregate) {
            foreach ($filters['group_by'] ?? [] as $dimension) {
                $expression = match ($dimension) {
                    'day' => 'SUBSTRING(o.registerTime, 1, 10)',
                    'company_id' => 'CASE WHEN IDENTITY(mcpOrder.client) IN (:mcpCompanies) THEN IDENTITY(mcpOrder.client) ELSE IDENTITY(mcpOrder.provider) END',
                    'status' => 'mcpPreparationStatus.realStatus',
                    'queue' => 'mcpQueue.queue',
                    default => throw new \InvalidArgumentException('Unsupported production queue aggregate grouping'),
                };
                $qb->addSelect($expression . ' AS ' . $dimension)->addGroupBy($expression);
            }
            if (in_array('company_id', $filters['group_by'] ?? [], true)) {
                $qb->setParameter('mcpCompanies', $companyIds);
            }
            return $qb->setMaxResults(100)->getQuery()->getArrayResult();
        }

        $rows = $qb->orderBy('o.registerTime', 'DESC')->setFirstResult((int) ($filters['offset'] ?? 0))->setMaxResults((int) $filters['limit'])->getQuery()->getArrayResult();
        foreach ($rows as &$row) {
            foreach (['registered_at', 'updated_at'] as $field) {
                if (($row[$field] ?? null) instanceof \DateTimeInterface) {
                    $row[$field] = $row[$field]->format(\DateTimeInterface::ATOM);
                }
            }
        }
        unset($row);

        return $rows;
    }

    private function requestFilters(string $dataset, ?int $companyId): array
    {
        if ($companyId === null) {
            return [];
        }
        return match ($dataset) {
            'devices' => ['people' => $companyId],
            'displays' => ['company' => $companyId],
            default => [],
        };
    }

    private function filterCompany(object $queryBuilder, string $field, ?int $companyId, bool $expression = false): void
    {
        if ($companyId === null) {
            return;
        }
        $queryBuilder->andWhere($expression ? $field : $field . ' = :mcpCompany')
            ->setParameter('mcpCompany', $companyId);
    }

    private function audit(string $dataset, array $companyIds, int $count, string $outcome): void
    {
        if ($this->logger === null) {
            return;
        }
        $user = $this->tokenStorage?->getToken()?->getUser();
        $this->logger->info('MCP read query', [
            'occurred_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'user_id' => is_object($user) && method_exists($user, 'getId') ? $user->getId() : null,
            'tool' => 'query_business_data',
            'dataset' => $dataset,
            'company_ids' => $companyIds,
            'company_scope_authorized' => true,
            'result_count' => $count,
            'outcome' => $outcome,
        ]);
    }
}
