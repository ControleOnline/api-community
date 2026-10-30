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
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class McpMercadoLivreProviderTest extends TestCase
{
    public function testStatusDisconnectedWhenNoToken(): void
    {
        $provider = $this->buildProvider([], [1]);
        $status = $provider->getStatus(1);
        self::assertFalse($status['connected']);
        self::assertSame(1, $status['company_id']);
    }

    public function testDeniedCompanyScope(): void
    {
        $provider = $this->buildProvider([], [2]);
        $this->expectException(\InvalidArgumentException::class);
        $provider->getStatus(1);
    }

    public function testSearchItemsUsesSellerIdAndSanitizes(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode([
                'results' => ['MLB1', 'MLB2'],
                'paging' => ['total' => 2, 'offset' => 0, 'limit' => 50],
            ], JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);
        $provider = $this->buildProvider([
            'mercadolivre_access_token' => 'tok',
            'mercadolivre_user_id' => '12345',
        ], [1], $client);

        $result = $provider->searchItems(1, 0, 50);
        self::assertSame(['MLB1', 'MLB2'], $result['results']);
        self::assertSame('12345', $result['seller_id']);
    }

    public function testGetItemDoesNotExposeSecrets(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode([
                'id' => 'MLB9',
                'title' => 'Produto',
                'price' => 10.5,
                'status' => 'active',
                'available_quantity' => 3,
                'secret_field' => 'should-not-leak',
            ], JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);
        $provider = $this->buildProvider([
            'mercadolivre_access_token' => 'tok',
        ], [1], $client);

        $item = $provider->getItem(1, 'MLB9');
        self::assertSame('MLB9', $item['id']);
        self::assertSame('Produto', $item['title']);
        self::assertArrayNotHasKey('secret_field', $item);
    }

    /**
     * @param array<string, string> $configs
     * @param list<int> $accessible
     */
    private function buildProvider(array $configs, array $accessible, ?MockHttpClient $client = null): McpMercadoLivreProvider
    {
        $people = $this->createMock(People::class);
        $people->method('getId')->willReturn(1);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->willReturnCallback(function (array $criteria) use ($configs, $people) {
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

        return new McpMercadoLivreProvider(
            $em,
            $client ?? new MockHttpClient(),
            $scope,
            null,
        );
    }
}
