<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\McpReadQueryProvider;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpReadQueryProviderInterface;
use ControleOnline\Service\ProductService;
use ControleOnline\Service\WalletService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class McpReadQueryProviderTest extends TestCase
{
    public function testUnauthorizedCompanyIdReturnsNoInventoryWithoutQueryingDatabase(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');

        $scopeProvider = new class implements McpCompanyScopeProviderInterface {
            public function listForCurrentUser(): array
            {
                return [['id' => 12, 'name' => 'Empresa autorizada', 'alias' => 'empresa-12']];
            }
        };

        $provider = new McpReadQueryProvider(
            $entityManager,
            new RequestStack(),
            $this->createMock(ContainerInterface::class),
            $scopeProvider,
            'America/Sao_Paulo',
        );

        self::assertSame([], $provider->query('inventory', [
            'from' => null,
            'to' => null,
            'company_id' => 13,
            'company_role' => null,
            'aggregate' => false,
            'limit' => 20,
        ]));
    }

    public function testWalletBalancesAreCompanyScopedAndUseAnAllowlistedProjection(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getArrayResult')->willReturn([['wallet' => 'Operacional', 'balance' => 23000]]);
        $where = [];
        $parameters = [];
        $selections = [];
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults', 'getQuery'])
            ->getMock();
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
            ->with('ControleOnline\\Entity\\Wallet')
            ->willReturn($repository);

        $securityService = $this->getMockBuilder(WalletService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['securityFilter'])
            ->getMock();
        $securityService->expects(self::once())
            ->method('securityFilter')
            ->with($queryBuilder, 'ControleOnline\\Entity\\Wallet', 'collection', 'o');
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with('ControleOnline\\Service\\WalletService')
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
        $user = new class implements UserInterface {
            public function getId(): int { return 42; }
            public function getRoles(): array { return []; }
            public function eraseCredentials(): void {}
            public function getUserIdentifier(): string { return 'mcp-test-user'; }
        };
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('MCP read query', self::callback(static function (array $context): bool {
                self::assertSame(42, $context['user_id']);
                self::assertSame('query_business_data', $context['tool']);
                self::assertSame('wallets', $context['dataset']);
                self::assertSame([12], $context['company_ids']);
                self::assertTrue($context['company_scope_authorized']);
                self::assertSame(1, $context['result_count']);
                self::assertSame('success', $context['outcome']);
                self::assertArrayNotHasKey('token', $context);
                self::assertArrayNotHasKey('filters', $context);
                return true;
            }));
        $provider = new McpReadQueryProvider($entityManager, $requestStack, $container, $scopeProvider, 'America/Sao_Paulo', $tokenStorage, $logger);

        $rows = $provider->query('wallets', [
            'from' => null,
            'to' => null,
            'company_id' => 12,
            'company_role' => null,
            'aggregate' => false,
            'limit' => 20,
        ]);

        self::assertSame(['original' => 'query'], $request->query->all());
        self::assertContains('IDENTITY(o.people) IN (:mcpCompanies)', $where);
        self::assertContains('IDENTITY(o.people) = :mcpCompany', $where);
        self::assertSame([12], $parameters['mcpCompanies']);
        self::assertSame(12, $parameters['mcpCompany']);
        self::assertSame('o.wallet AS wallet, o.balance AS balance', $selections[0]);
        self::assertSame([['wallet' => 'Operacional', 'balance' => 23000]], $rows);
    }

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
