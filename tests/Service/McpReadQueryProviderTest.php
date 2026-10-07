<?php

declare(strict_types=1);

namespace ControleOnline\Integration\Tests\Service;

use App\Service\McpReadQueryProvider;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpReadQueryProviderInterface;
use ControleOnline\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class McpReadQueryProviderTest extends TestCase
{
    public function testInventoryIsCompanyScopedAndUsesAnAllowlistedProjection(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getArrayResult')->willReturn([[
            'product' => 'Widget',
            'inventory' => 'Main warehouse',
            'available' => 8,
            'sales' => 2,
            'purchases' => 10,
            'transit' => 0,
            'minimum' => 1,
            'maximum' => 25,
        ]]);

        $where = [];
        $parameters = [];
        $selections = [];
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['join', 'select', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults', 'getQuery'])
            ->getMock();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('select')->willReturnCallback(static function (string $selection) use (&$selections, $queryBuilder): QueryBuilder {
            $selections[] = $selection;
            return $queryBuilder;
        });
        $queryBuilder->method('andWhere')->willReturnCallback(static function (string $condition) use (&$where, $queryBuilder): QueryBuilder {
            $where[] = $condition;
            return $queryBuilder;
        });
        $queryBuilder->method('setParameter')->willReturnCallback(static function (string $name, mixed $value) use (&$parameters, $queryBuilder): QueryBuilder {
            $parameters[$name] = $value;
            return $queryBuilder;
        });
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('createQueryBuilder')->with('o')->willReturn($queryBuilder);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with('ControleOnline\\Entity\\ProductInventory')
            ->willReturn($repository);

        $securityService = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['securityFilter'])
            ->getMock();
        $securityService->expects(self::once())
            ->method('securityFilter')
            ->with($queryBuilder, 'ControleOnline\\Entity\\Product', 'collection', 'mcpProduct');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with('ControleOnline\\Service\\ProductService')
            ->willReturn($securityService);

        $scopeProvider = new class implements McpCompanyScopeProviderInterface {
            public function listForCurrentUser(): array
            {
                return [['id' => 12, 'name' => 'Empresa autorizada', 'alias' => 'empresa-12']];
            }
        };
        $requestStack = new RequestStack();
        $request = new Request(['original' => 'query']);
        $requestStack->push($request);

        $provider = new McpReadQueryProvider($entityManager, $requestStack, $container, $scopeProvider, 'America/Sao_Paulo');
        $rows = $provider->query('inventory', [
            'from' => null,
            'to' => null,
            'company_id' => 12,
            'company_role' => null,
            'aggregate' => false,
            'limit' => 20,
        ]);

        self::assertSame(['original' => 'query'], $request->query->all());
        self::assertContains('IDENTITY(mcpInventory.people) IN (:mcpCompanies)', $where);
        self::assertContains('IDENTITY(mcpInventory.people) = :mcpCompany', $where);
        self::assertSame([12], $parameters['mcpCompanies']);
        self::assertSame(12, $parameters['mcpCompany']);
        self::assertStringContainsString('mcpInventory.inventory AS inventory', $selections[0]);
        self::assertStringNotContainsString('document', strtolower($selections[0]));
        self::assertSame('Widget', $rows[0]['product']);
        self::assertArrayNotHasKey('document', $rows[0]);
    }
}
