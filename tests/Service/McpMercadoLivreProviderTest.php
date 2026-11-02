<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\McpMercadoLivreProvider;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class McpMercadoLivreProviderTest extends TestCase
{
    public function testStatusDisconnectedWhenNoToken(): void
    {
        $provider = $this->buildProvider([], [1]);
        $status = $provider->getStatus(1);
        self::assertFalse($status['connected']);
        self::assertSame(1, $status['company_id']);
        self::assertSame('local', $status['data_source']);
    }

    public function testDeniedCompanyScope(): void
    {
        $provider = $this->buildProvider([], [2]);
        $this->expectException(\RuntimeException::class);
        $provider->getStatus(1);
    }

    public function testLegacyConfigKeysStillWork(): void
    {
        $provider = $this->buildProvider([
            'mercadolivre_access_token' => 'legacy-tok',
            'mercadolivre_user_id' => '999',
        ], [1]);
        $status = $provider->getStatus(1);
        self::assertTrue($status['connected']);
        self::assertSame('999', $status['user_id']);
        self::assertArrayHasKey('integration', $status);
    }

    public function testImportProductsIsIntegrationOnly(): void
    {
        $provider = $this->buildProvider(['mercado-livre-access-token' => 'tok'], [1]);
        $result = $provider->importProducts(1, 10, 5);
        self::assertFalse($result['success']);
        self::assertSame('integration_only', $result['error']);
    }

    public function testImportOrdersIsIntegrationOnly(): void
    {
        $provider = $this->buildProvider(['mercado-livre-access-token' => 'tok'], [1]);
        $result = $provider->importOrders(1, 5);
        self::assertFalse($result['success']);
        self::assertSame('integration_only', $result['error']);
    }

    /**
     * @param array<string, string> $configs
     * @param list<int> $accessible
     */
    private function buildProvider(array $configs, array $accessible): McpMercadoLivreProvider
    {
        $people = $this->createMock(People::class);
        $people->method('getId')->willReturn(1);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->willReturnCallback(function (array $criteria) use ($configs) {
            $key = $criteria['configKey'] ?? null;
            if (!is_string($key) || !array_key_exists($key, $configs)) {
                return [];
            }
            $config = $this->createMock(Config::class);
            $config->method('getConfigValue')->willReturn($configs[$key]);
            return [$config];
        });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturnCallback(function (string $class, $id) use ($people) {
            return (int) $id === 1 ? $people : null;
        });
        $em->method('getRepository')->willReturn($repo);

        $scope = $this->createMock(McpCompanyScopeProviderInterface::class);
        $scope->method('listForCurrentUser')->willReturn(array_map(
            static fn (int $id): array => ['id' => $id, 'name' => 'C'.$id],
            $accessible
        ));

        return new McpMercadoLivreProvider($em, $scope, null);
    }
}
