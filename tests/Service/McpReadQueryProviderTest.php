<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\McpReadQueryProvider;
use App\Service\McpPeopleLinkReadQueryProvider;
use App\Service\McpSafeEntityNormalizer;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpReadQueryProviderInterface;
use ControleOnline\Service\OrderService;
use ControleOnline\Service\ProductService;
use ControleOnline\Service\PeopleLinkService;
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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class McpReadQueryProviderTest extends TestCase
{
    public function testQueryCatalogIncludesOrdersSeparatelyFromClosedSales(): void
    {
        $scopeProvider = new class implements McpCompanyScopeProviderInterface {
            public function listForCurrentUser(): array
            {
                return [];
            }
        };
        $provider = new McpReadQueryProvider(
            $this->createMock(EntityManagerInterface::class),
            new RequestStack(),
            $this->createMock(ContainerInterface::class),
            $scopeProvider,
            'America/Sao_Paulo',
        );

        self::assertSame(
            ['sales', 'orders', 'invoices', 'products', 'inventory', 'wallets', 'employees', 'clients', 'suppliers', 'salespeople', 'commissions', 'configs', 'devices', 'displays', 'production_queue'],
            array_column($provider->getDatasets(), 'name'),
        );
    }

    public function testEmployeeDirectoryUsesPeopleLinkSecurityFilterAndCompanyScope(): void
    {
        $query = $this->createMock(Query::class);
        $link = $this->createMock(\ControleOnline\Entity\PeopleLink::class);
        $query->method('getResult')->willReturn([$link]);
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['join', 'select', 'andWhere', 'setParameter', 'orderBy', 'setFirstResult', 'setMaxResults', 'getQuery'])
            ->getMock();
        foreach (['join', 'select', 'andWhere', 'setParameter', 'orderBy', 'setFirstResult', 'setMaxResults'] as $method) {
            $queryBuilder->method($method)->willReturnSelf();
        }
        $queryBuilder->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('createQueryBuilder')->with('o')->willReturn($queryBuilder);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with('ControleOnline\Entity\PeopleLink')
            ->willReturn($repository);

        $requestStack = new RequestStack();
        $request = new Request(['original' => 'query']);
        $requestStack->push($request);
        $peopleLinkService = $this->getMockBuilder(PeopleLinkService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['securityFilter'])
            ->getMock();
        $peopleLinkService->expects(self::once())
            ->method('securityFilter')
            ->with($queryBuilder, 'ControleOnline\Entity\PeopleLink', 'collection', 'o')
            ->willReturnCallback(static function () use ($request): void {
                self::assertSame(['company' => 12], $request->query->all());
            });
        $serializer = $this->createMock(NormalizerInterface::class);
        $serializer->expects(self::once())->method('normalize')->with(
            $link,
            'json',
            self::callback(static fn (array $context): bool => $context['groups'] === ['people_link:read']),
        )->willReturn([
            'id' => 8,
            'linkType' => 'employee',
            'company' => ['id' => 12, 'name' => 'Empresa A'],
            'people' => ['id' => 44, 'name' => 'Funcionário A', 'email' => 'funcionario@example.com', 'document' => ['number' => '123']],
            'apiKey' => 'must-not-leak',
        ]);
        $normalizer = new McpSafeEntityNormalizer($serializer);
        $linkedProvider = null;
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(2))->method('get')->willReturnCallback(
            static function (string $id) use ($peopleLinkService, &$linkedProvider): mixed {
                return match ($id) {
                    'ControleOnline\Service\PeopleLinkService' => $peopleLinkService,
                    McpPeopleLinkReadQueryProvider::class => $linkedProvider,
                    default => throw new \LogicException('Unexpected service: ' . $id),
                };
            },
        );
        $linkedProvider = new McpPeopleLinkReadQueryProvider($entityManager, $container, $normalizer);
        $scopeProvider = new class implements McpCompanyScopeProviderInterface {
            public function listForCurrentUser(): array
            {
                return [['id' => 12, 'name' => 'Empresa A', 'alias' => 'empresa-a']];
            }
        };

        $provider = new McpReadQueryProvider($entityManager, $requestStack, $container, $scopeProvider, 'America/Sao_Paulo');
        $rows = $provider->query('employees', [
            'from' => null,
            'to' => null,
            'company_id' => 12,
            'company_role' => null,
            'aggregate' => false,
            'limit' => 20,
        ]);

        self::assertSame(['original' => 'query'], $request->query->all());
        self::assertSame('Funcionário A', $rows[0]['name']);
        self::assertSame(['number' => '123'], $rows[0]['people']['document']);
        self::assertSame('funcionario@example.com', $rows[0]['people']['email']);
        self::assertArrayNotHasKey('apiKey', $rows[0]);
    }

    public function testOrderDetailsKeepLinkedPeopleAndItemsWhileFilteringSecretsAndRecordId(): void
    {
        $entity = new \stdClass();
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$entity]);
        $where = [];
        $parameters = [];
        $selections = [];
        $builder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'andWhere', 'setParameter', 'orderBy', 'setFirstResult', 'setMaxResults', 'getQuery'])
            ->getMock();
        $builder->method('select')->willReturnCallback(static function (string $value) use (&$selections, $builder): QueryBuilder {
            $selections[] = $value;
            return $builder;
        });
        $builder->method('andWhere')->willReturnCallback(static function (string $value) use (&$where, $builder): QueryBuilder {
            $where[] = $value;
            return $builder;
        });
        $builder->method('setParameter')->willReturnCallback(static function (string $key, mixed $value) use (&$parameters, $builder): QueryBuilder {
            $parameters[$key] = $value;
            return $builder;
        });
        $builder->method('orderBy')->willReturnSelf();
        $builder->method('setFirstResult')->willReturnSelf();
        $builder->method('setMaxResults')->willReturnSelf();
        $builder->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('createQueryBuilder')->with('o')->willReturn($builder);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('getRepository')->with('ControleOnline\\Entity\\Order')->willReturn($repository);
        $orderService = $this->getMockBuilder(OrderService::class)->disableOriginalConstructor()->onlyMethods(['securityFilter'])->getMock();
        $orderService->expects(self::once())->method('securityFilter')->with($builder, 'ControleOnline\\Entity\\Order', 'collection', 'o');

        $serializer = $this->createMock(NormalizerInterface::class);
        $serializer->expects(self::once())->method('normalize')->with(
            $entity,
            'json',
            self::callback(static fn (array $context): bool => $context['groups'] === ['order_details:read']),
        )->willReturn([
            'id' => 73230,
            'client' => ['id' => 44, 'name' => 'Adelino'],
            'provider' => ['id' => 3, 'name' => 'GYROS'],
            'orderProducts' => [['quantity' => 5, 'product' => ['id' => 9, 'product' => 'Queijo', 'api_key' => 'hidden']]],
            'chargeCapability' => ['secret' => 'hidden'],
        ]);
        $safeNormalizer = new McpSafeEntityNormalizer($serializer);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(2))->method('get')->willReturnMap([
            ['ControleOnline\\Service\\OrderService', $orderService],
            [McpSafeEntityNormalizer::class, $safeNormalizer],
        ]);
        $scope = new class implements McpCompanyScopeProviderInterface {
            public function listForCurrentUser(): array { return [['id' => 3, 'name' => 'GYROS', 'alias' => 'gyros']]; }
        };
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $provider = new McpReadQueryProvider($entityManager, $requestStack, $container, $scope, 'America/Sao_Paulo');

        $rows = $provider->query('orders', [
            'from' => null, 'to' => null, 'company_id' => 3, 'company_role' => null,
            'record_id' => 73230, 'aggregate' => false, 'limit' => 20, 'offset' => 0,
        ]);

        self::assertContains('(IDENTITY(o.client) IN (:mcpCompanies) OR IDENTITY(o.provider) IN (:mcpCompanies))', $where);
        self::assertContains('o.id = :mcpRecordId', $where);
        self::assertSame([3], $parameters['mcpCompanies']);
        self::assertSame(73230, $parameters['mcpRecordId']);
        self::assertContains('o', $selections);
        self::assertSame('Adelino', $rows[0]['client_name']);
        self::assertSame('GYROS', $rows[0]['provider_name']);
        self::assertSame('Queijo', $rows[0]['orderProducts'][0]['product']['product']);
        self::assertArrayNotHasKey('chargeCapability', $rows[0]);
        self::assertArrayNotHasKey('api_key', $rows[0]['orderProducts'][0]['product']);
    }

    public function testUnauthorizedCompanyIdReturnsNoDataWithoutQueryingDatabase(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');

        $scopeProvider = new class implements McpCompanyScopeProviderInterface {
            public function listForCurrentUser(): array
            {
                return [['id' => 12, 'name' => 'Empresa autorizada', 'alias' => 'empresa-12']];
            }
        };

        $requestStack = new RequestStack();
        $container = $this->createMock(ContainerInterface::class);
        $operational = new \App\Service\McpOperationalReadQueryProvider($entityManager, $requestStack, $container);
        $provider = new McpReadQueryProvider(
            $entityManager,
            $requestStack,
            $container,
            $scopeProvider,
            'America/Sao_Paulo',
            null,
            null,
            $operational,
        );

        self::assertSame([], $provider->query('inventory', [
            'from' => null,
            'to' => null,
            'company_id' => 13,
            'company_role' => null,
            'aggregate' => false,
            'limit' => 20,
        ]));
        self::assertSame([], $provider->query('employees', [
            'from' => null,
            'to' => null,
            'company_id' => 13,
            'company_role' => null,
            'aggregate' => false,
            'limit' => 20,
        ]));
        foreach (['configs', 'devices', 'displays', 'production_queue'] as $dataset) {
            self::assertSame([], $provider->query($dataset, [
                'from' => null,
                'to' => null,
                'company_id' => 13,
                'company_role' => null,
                'aggregate' => false,
                'limit' => 20,
            ]), $dataset);
        }
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
        self::assertContains('IDENTITY(mcpProduct.company) IN (:mcpCompanies)', $where);
        self::assertContains('IDENTITY(mcpInventory.people) = :mcpCompany', $where);
        self::assertSame([12], $parameters['mcpCompanies']);
        self::assertSame(12, $parameters['mcpCompany']);
        self::assertStringContainsString('mcpInventory.inventory AS inventory', $selections[0]);
        self::assertStringNotContainsString('document', strtolower($selections[0]));
        self::assertSame('Widget', $rows[0]['product']);
        self::assertArrayNotHasKey('document', $rows[0]);
    }
}
