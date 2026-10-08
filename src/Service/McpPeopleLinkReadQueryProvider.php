<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class McpPeopleLinkReadQueryProvider
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContainerInterface $container,
        private readonly McpSafeEntityNormalizer $normalizer,
        private readonly ?TokenStorageInterface $tokenStorage = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /** @param list<int> $companyIds
     *  @param array<string, mixed> $filters
     *  @return list<array<string, mixed>>
     */
    public function query(string $dataset, array $companyIds, ?int $requestedCompanyId, array $filters, Request $request): array
    {
        $aggregate = (bool) ($filters['aggregate'] ?? false);
        $groupBy = $filters['group_by'] ?? [];
        if (($filters['from'] ?? null) !== null || ($filters['to'] ?? null) !== null
            || ($filters['company_role'] ?? null) !== null
            || ($aggregate && $dataset === 'commissions')) {
            throw new \InvalidArgumentException('People and commission queries do not support date or role filters; commission aggregates are not available');
        }
        if ($aggregate && array_diff($groupBy, ['company_id']) !== []) {
            throw new \InvalidArgumentException('People aggregates only support grouping by company_id');
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
                    ->setFirstResult((int) ($filters['offset'] ?? 0))
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
                    ->setParameter('mcpCompanies', $requestedCompanyId === null ? $companyIds : [$requestedCompanyId]);
                if ($aggregate) {
                    $queryBuilder->select('COUNT(DISTINCT mcpLinkedPerson.id) AS count');
                    if (in_array('company_id', $groupBy, true)) {
                        $queryBuilder->addSelect('mcpLinkCompany.id AS company_id')
                            ->groupBy('mcpLinkCompany.id')
                            ->setMaxResults(100);
                    }
                } else {
                    $queryBuilder->select('o')
                        ->orderBy('mcpLinkedPerson.name', 'ASC')
                        ->setFirstResult((int) ($filters['offset'] ?? 0))
                        ->setMaxResults((int) $filters['limit']);
                }
                if (!$aggregate) {
                    $rows = [];
                    foreach ($queryBuilder->getQuery()->getResult() as $link) {
                        if (!$link instanceof \ControleOnline\Entity\PeopleLink) {
                            continue;
                        }
                        $row = $this->normalizer->normalize($link, 'people_link:read');
                        $person = $row['people'] ?? [];
                        $company = $row['company'] ?? [];
                        $row['company_id'] = $company['id'] ?? null;
                        $row['company_name'] = $company['name'] ?? $company['alias'] ?? null;
                        $row['person_id'] = $person['id'] ?? null;
                        $row['name'] = $person['name'] ?? null;
                        $row['alias'] = $person['alias'] ?? null;
                        $row['relationship'] = $row['linkType'] ?? null;
                        $rows[] = $row;
                    }
                } else {
                    $rows = $queryBuilder->getQuery()->getArrayResult();
                }
                if ($aggregate) {
                    foreach ($rows as &$row) {
                        $row['count'] = (int) $row['count'];
                    }
                    unset($row);
                }
            }

            $resultCount = $aggregate && !in_array('company_id', $groupBy, true)
                ? (int) ($rows[0]['count'] ?? 0)
                : count($rows);
            $this->auditQuery($dataset, $requestedCompanyId === null ? $companyIds : [$requestedCompanyId], true, $resultCount, 'success');
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
