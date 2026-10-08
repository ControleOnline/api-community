<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\McpOperationalReadQueryProvider;
use App\Service\McpReadQueryProvider;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class McpOperationalReadQueryProviderTest extends TestCase
{
    public function testOperationalDatasetsUseCompanyFiltersAndSafeProjections(): void
    {
        $cases = [
            ['configs', 'ControleOnline\\Entity\\Config', 'ControleOnline\\Service\\ConfigService', 'o.configKey = :mcpDeviceConfigKey', 'config_value'],
            ['devices', 'ControleOnline\\Entity\\DeviceConfig', 'ControleOnline\\Service\\DeviceConfigService', 'IDENTITY(o.people) IN (:mcpCompanies)', 'configs'],
            ['displays', 'ControleOnline\\Entity\\Display', 'ControleOnline\\Service\\DisplayService', 'IDENTITY(o.company) IN (:mcpCompanies)', 'device'],
            ['production_queue', 'ControleOnline\\Entity\\OrderProductQueue', 'ControleOnline\\Service\\OrderService', 'mcpOrder.orderType = :mcpSaleOrderType', 'comment'],
        ];

        foreach ($cases as [$dataset, $entityClass, $serviceId, $scopeCondition, $forbiddenField]) {
            $requestStack = new RequestStack();
            $request = new Request(['before' => 'preserved']);
            $requestStack->push($request);
            $query = $this->createMock(Query::class);
            $query->method('getArrayResult')->willReturn([['id' => 1]]);
            $where = [];
            $selections = [];
            $parameters = [];
            $builder = $this->createQueryBuilder($query, $where, $selections, $parameters);
            $repository = $this->createMock(EntityRepository::class);
            $repository->expects(self::once())->method('createQueryBuilder')->with('o')->willReturn($builder);
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager->expects(self::once())->method('getRepository')->with($entityClass)->willReturn($repository);

            $securityFilter = $this->createMockSecurityFilter($serviceId, $builder);
            $container = $this->createMock(ContainerInterface::class);
            $container->expects(self::once())->method('get')->with($serviceId)->willReturn($securityFilter);
            $scope = new class implements McpCompanyScopeProviderInterface {
                public function listForCurrentUser(): array
                {
                    return [['id' => 12, 'name' => 'Empresa permitida', 'alias' => 'empresa-12']];
                }
            };
            $operational = new McpOperationalReadQueryProvider($entityManager, $requestStack, $container);
            $provider = new McpReadQueryProvider($entityManager, $requestStack, $container, $scope, 'America/Sao_Paulo', null, null, $operational);

            $rows = $provider->query($dataset, [
                'from' => $dataset === 'production_queue' ? '2026-10-01' : null,
                'to' => $dataset === 'production_queue' ? '2026-10-02' : null,
                'company_id' => 12,
                'company_role' => null,
                'aggregate' => false,
                'limit' => 20,
            ]);

            self::assertSame([['id' => 1]], $rows, $dataset);
            self::assertContains($scopeCondition, $where, $dataset);
            self::assertSame([12], $parameters['mcpCompanies'], $dataset);
            self::assertStringNotContainsString($forbiddenField, implode(' ', $selections), $dataset);
            self::assertSame(['before' => 'preserved'], $request->query->all(), $dataset);
        }
    }

    public function testRequestedCompanyOutsideScopeDoesNotQueryOperationalDatasets(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');
        $scope = new class implements McpCompanyScopeProviderInterface {
            public function listForCurrentUser(): array
            {
                return [['id' => 12, 'name' => 'Empresa permitida', 'alias' => 'empresa-12']];
            }
        };
        $requestStack = new RequestStack();
        $container = $this->createMock(ContainerInterface::class);
        $operational = new McpOperationalReadQueryProvider($entityManager, $requestStack, $container);
        $provider = new McpReadQueryProvider($entityManager, $requestStack, $container, $scope, 'UTC', null, null, $operational);

        self::assertSame([], $provider->query('devices', [
            'from' => null, 'to' => null, 'company_id' => 999, 'company_role' => null,
            'aggregate' => false, 'limit' => 20,
        ]));
    }

    private function createQueryBuilder(Query $query, array &$where, array &$selections, array &$parameters): QueryBuilder
    {
        $builder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['join', 'leftJoin', 'select', 'andWhere', 'setParameter', 'orderBy', 'addOrderBy', 'setMaxResults', 'getQuery'])
            ->getMock();
        foreach (['join', 'leftJoin', 'orderBy', 'addOrderBy', 'setMaxResults'] as $method) {
            $builder->method($method)->willReturnSelf();
        }
        $builder->method('andWhere')->willReturnCallback(static function (string $condition) use (&$where, $builder): QueryBuilder {
            $where[] = $condition;
            return $builder;
        });
        $builder->method('select')->willReturnCallback(static function (string $selection) use (&$selections, $builder): QueryBuilder {
            $selections[] = $selection;
            return $builder;
        });
        $builder->method('setParameter')->willReturnCallback(static function (string $name, mixed $value) use (&$parameters, $builder): QueryBuilder {
            $parameters[$name] = $value;
            return $builder;
        });
        $builder->method('getQuery')->willReturn($query);
        return $builder;
    }

    private function createMockSecurityFilter(string $serviceId, QueryBuilder $builder): object
    {
        $class = match ($serviceId) {
            'ControleOnline\\Service\\ConfigService' => \ControleOnline\Service\ConfigService::class,
            'ControleOnline\\Service\\DeviceConfigService' => \ControleOnline\Service\DeviceConfigService::class,
            'ControleOnline\\Service\\DisplayService' => \ControleOnline\Service\DisplayService::class,
            default => \ControleOnline\Service\OrderService::class,
        };
        $service = $this->getMockBuilder($class)->disableOriginalConstructor()->onlyMethods(['securityFilter'])->getMock();
        $service->expects(self::once())->method('securityFilter')->with(
            $builder,
            $serviceId === 'ControleOnline\\Service\\OrderService' ? 'ControleOnline\\Entity\\Order' : self::anything(),
            'collection',
            $serviceId === 'ControleOnline\\Service\\OrderService' ? 'mcpOrder' : 'o',
        );
        return $service;
    }
}
