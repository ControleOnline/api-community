<?php

declare(strict_types=1);

namespace App\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Product;
use ControleOnline\Entity\ProductInventory;
use ControleOnline\Service\ExtraDataService;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpMercadoLivreProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * MCP surface for Mercado Livre **local** domain data only.
 *
 * Architecture (owner rule):
 * - MCP must NOT call marketplace APIs (api.mercadolibre.com, etc.).
 * - Integration module syncs remote → local Product / ProductInventory / Order.
 * - MCP reads local products, inventories and orders that already carry marketplace ids
 *   (ExtraData context MercadoLivre, Order.app = MercadoLivre, externalCode).
 *
 * Sync/import from Mercado Livre remains on HTTP integration routes only, e.g.:
 * - POST /marketplace/integrations/mercadolivre/products/import
 * - POST /marketplace/integrations/mercadolivre/orders/import
 * - POST /marketplace/integrations/mercadolivre/stock/push
 */
final class McpMercadoLivreProvider implements McpMercadoLivreProviderInterface
{
    private const APP_CONTEXT = 'MercadoLivre';
    private const CONFIG_ACCESS = 'mercado-livre-access-token';
    private const CONFIG_REFRESH = 'mercado-livre-refresh-token';
    private const CONFIG_USER_ID = 'mercado-livre-user-id';
    private const CONFIG_ACCESS_LEGACY = 'mercadolivre_access_token';
    private const CONFIG_REFRESH_LEGACY = 'mercadolivre_refresh_token';
    private const CONFIG_USER_ID_LEGACY = 'mercadolivre_user_id';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly McpCompanyScopeProviderInterface $companyScopeProvider,
        private readonly ?ExtraDataService $extraDataService = null,
    ) {
    }

    public function getStatus(int $companyId): array
    {
        $this->assertCompanyAccess($companyId);
        $token = $this->getConfigValue($companyId, self::CONFIG_ACCESS, self::CONFIG_ACCESS_LEGACY);
        $userId = $this->getConfigValue($companyId, self::CONFIG_USER_ID, self::CONFIG_USER_ID_LEGACY);

        return [
            'company_id' => $companyId,
            'connected' => $token !== null && $token !== '',
            'user_id' => $userId,
            'has_refresh_token' => $this->getConfigValue($companyId, self::CONFIG_REFRESH, self::CONFIG_REFRESH_LEGACY) !== null,
            'data_source' => 'local',
            'note' => 'MCP reads local products/inventory/orders. Marketplace sync is integration-only.',
            'oauth' => [
                'authorization_endpoint' => '/marketplace/integrations/mercadolivre/authorization-page',
                'callback_endpoint' => '/oauth/mercadolivre/return',
            ],
            'integration' => [
                'products_import' => '/marketplace/integrations/mercadolivre/products/import',
                'orders_import' => '/marketplace/integrations/mercadolivre/orders/import',
                'stock_push' => '/marketplace/integrations/mercadolivre/stock/push',
            ],
        ];
    }

    public function getCurrentUser(int $companyId): array
    {
        $this->assertCompanyAccess($companyId);
        $userId = $this->getConfigValue($companyId, self::CONFIG_USER_ID, self::CONFIG_USER_ID_LEGACY);

        return [
            'company_id' => $companyId,
            'user_id' => $userId,
            'connected' => $this->getConfigValue($companyId, self::CONFIG_ACCESS, self::CONFIG_ACCESS_LEGACY) !== null,
            'data_source' => 'local_config',
        ];
    }

    public function searchItems(int $companyId, int $offset = 0, int $limit = 50): array
    {
        $this->assertCompanyAccess($companyId);
        $offset = max(0, $offset);
        $limit = max(1, min(100, $limit));
        $company = $this->requireCompany($companyId);

        $products = $this->em->getRepository(Product::class)->findBy(
            ['company' => $company],
            ['id' => 'DESC'],
            $limit * 3,
            0
        );

        $results = [];
        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }
            $remoteId = $this->resolveProductRemoteId($product);
            if ($remoteId === '') {
                continue;
            }
            $results[] = $remoteId;
            if (count($results) >= $offset + $limit) {
                break;
            }
        }
        $page = array_slice($results, $offset, $limit);

        return [
            'company_id' => $companyId,
            'data_source' => 'local_products',
            'results' => $page,
            'count' => count($page),
        ];
    }

    public function getItem(int $companyId, string $itemId): array
    {
        $this->assertCompanyAccess($companyId);
        $itemId = trim($itemId);
        if ($itemId === '') {
            throw new \InvalidArgumentException('item_id is required');
        }

        $product = $this->findLocalProductByRemoteId($companyId, $itemId);
        if (!$product instanceof Product) {
            return [
                'success' => false,
                'error' => 'not_found_local',
                'message' => 'Product not found locally for this Mercado Livre id. Run integration products/import first.',
                'item_id' => $itemId,
            ];
        }

        return $this->projectProduct($product, $itemId);
    }

    public function getItemDescription(int $companyId, string $itemId): array
    {
        $item = $this->getItem($companyId, $itemId);
        if (isset($item['error'])) {
            return $item;
        }

        return [
            'item_id' => $itemId,
            'plain_text' => $item['description'] ?? null,
            'text' => $item['description'] ?? null,
            'data_source' => 'local_products',
        ];
    }

    public function getCategory(int $companyId, string $categoryId): array
    {
        $this->assertCompanyAccess($companyId);

        return [
            'success' => false,
            'error' => 'local_only',
            'message' => 'Category metadata is not queried from Mercado Livre via MCP. Use local product categories or integration catalog sync.',
            'category_id' => $categoryId,
        ];
    }

    public function getOrder(int $companyId, string $orderId): array
    {
        $this->assertCompanyAccess($companyId);
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw new \InvalidArgumentException('order_id is required');
        }

        $company = $this->requireCompany($companyId);
        $order = $this->em->getRepository(Order::class)->findOneBy([
            'provider' => $company,
            'externalCode' => $orderId,
        ]);

        if (!$order instanceof Order) {
            return [
                'success' => false,
                'error' => 'not_found_local',
                'message' => 'Order not found locally. Run integration orders/import or wait for webhook.',
                'order_id' => $orderId,
            ];
        }

        return $this->projectOrder($order);
    }

    public function searchOrders(int $companyId, int $offset = 0, int $limit = 50, ?string $status = null): array
    {
        $this->assertCompanyAccess($companyId);
        $offset = max(0, $offset);
        $limit = max(1, min(50, $limit));
        $company = $this->requireCompany($companyId);

        $qb = $this->em->getRepository(Order::class)->createQueryBuilder('o')
            ->andWhere('o.provider = :provider')
            ->setParameter('provider', $company)
            ->andWhere('o.app = :app OR o.externalCode IS NOT NULL')
            ->setParameter('app', Order::APP_MERCADO_LIVRE)
            ->orderBy('o.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($status !== null && trim($status) !== '' && method_exists(Order::class, 'getStatus')) {
            // status filter best-effort via joined status name if mapping exists
        }

        $rows = $qb->getQuery()->getResult();
        $results = [];
        foreach ($rows as $order) {
            if (!$order instanceof Order) {
                continue;
            }
            // Prefer ML orders: app match or external code present
            if ($order->getApp() !== Order::APP_MERCADO_LIVRE && !$order->getExternalCode()) {
                continue;
            }
            $results[] = [
                'id' => $order->getExternalCode() ?: (string) $order->getId(),
                'local_id' => $order->getId(),
                'status' => method_exists($order, 'getStatus') && $order->getStatus()
                    ? (method_exists($order->getStatus(), 'getStatus') ? $order->getStatus()->getStatus() : null)
                    : null,
                'data_source' => 'local_orders',
            ];
        }

        return [
            'success' => true,
            'company_id' => $companyId,
            'data_source' => 'local_orders',
            'results' => $results,
        ];
    }

    public function importOrders(int $companyId, int $limit = 20, ?string $status = null): array
    {
        $this->assertCompanyAccess($companyId);

        return [
            'success' => false,
            'error' => 'integration_only',
            'message' => 'MCP does not call Mercado Livre. Use POST /marketplace/integrations/mercadolivre/orders/import then query local orders.',
            'endpoint' => '/marketplace/integrations/mercadolivre/orders/import',
        ];
    }

    public function importProducts(int $companyId, int $showcaseId, int $limit = 50): array
    {
        $this->assertCompanyAccess($companyId);

        return [
            'success' => false,
            'error' => 'integration_only',
            'message' => 'MCP does not call Mercado Livre. Use POST /marketplace/integrations/mercadolivre/products/import then query local products/inventory.',
            'endpoint' => '/marketplace/integrations/mercadolivre/products/import',
            'showcase_id' => $showcaseId,
        ];
    }

    /**
     * Local stock snapshot for a product linked to Mercado Livre (all product_inventory rows).
     *
     * @return list<array{inventory_id:int|null,inventory_name:?string,available:int}>
     */
    private function projectInventories(Product $product): array
    {
        $rows = $this->em->getRepository(ProductInventory::class)->findBy(['product' => $product]);
        $out = [];
        foreach ($rows as $row) {
            if (!$row instanceof ProductInventory) {
                continue;
            }
            $inv = $row->getInventory();
            $out[] = [
                'inventory_id' => $inv?->getId(),
                'inventory_name' => $inv && method_exists($inv, 'getInventory') ? $inv->getInventory() : null,
                'available' => (int) $row->getAvailable(),
            ];
        }

        return $out;
    }

    private function projectProduct(Product $product, string $remoteId): array
    {
        return [
            'id' => $remoteId,
            'local_product_id' => $product->getId(),
            'title' => method_exists($product, 'getProduct') ? $product->getProduct() : null,
            'description' => method_exists($product, 'getDescription') ? $product->getDescription() : null,
            'price' => method_exists($product, 'getPrice') ? $product->getPrice() : null,
            'sku' => method_exists($product, 'getSku') ? $product->getSku() : null,
            'active' => method_exists($product, 'isActive') ? $product->isActive() : null,
            'available_quantity' => array_sum(array_column($this->projectInventories($product), 'available')),
            'inventories' => $this->projectInventories($product),
            'data_source' => 'local_products',
        ];
    }

    private function projectOrder(Order $order): array
    {
        return [
            'id' => $order->getExternalCode() ?: (string) $order->getId(),
            'local_id' => $order->getId(),
            'app' => $order->getApp(),
            'external_code' => $order->getExternalCode(),
            'data_source' => 'local_orders',
        ];
    }

    private function findLocalProductByRemoteId(int $companyId, string $remoteId): ?Product
    {
        if ($this->extraDataService !== null) {
            $entity = $this->extraDataService->getEntityByExtraData(
                self::APP_CONTEXT,
                'id',
                $remoteId,
                Product::class
            );
            if ($entity instanceof Product) {
                $company = $entity->getCompany();
                if ($company && (int) $company->getId() === $companyId) {
                    return $entity;
                }
            }
            $entity = $this->extraDataService->getEntityByExtraData(
                self::APP_CONTEXT,
                'code',
                $remoteId,
                Product::class
            );
            if ($entity instanceof Product) {
                $company = $entity->getCompany();
                if ($company && (int) $company->getId() === $companyId) {
                    return $entity;
                }
            }
        }

        $company = $this->requireCompany($companyId);
        $candidates = $this->em->getRepository(Product::class)->findBy(
            ['company' => $company],
            ['id' => 'DESC'],
            200
        );
        foreach ($candidates as $product) {
            if ($product instanceof Product && $this->resolveProductRemoteId($product) === $remoteId) {
                return $product;
            }
        }

        return null;
    }

    private function resolveProductRemoteId(Product $product): string
    {
        if ($this->extraDataService !== null) {
            $id = trim((string) ($this->extraDataService->getExtraDataValue(
                self::APP_CONTEXT,
                'Product',
                (int) $product->getId(),
                'id',
                'mercadolivre'
            ) ?? ''));
            if ($id !== '') {
                return $id;
            }
            $code = trim((string) ($this->extraDataService->getExtraDataValue(
                self::APP_CONTEXT,
                'Product',
                (int) $product->getId(),
                'code',
                'mercadolivre'
            ) ?? ''));
            if ($code !== '') {
                return $code;
            }
        }

        $sku = method_exists($product, 'getSku') ? trim((string) $product->getSku()) : '';
        if ($sku !== '' && str_starts_with(strtoupper($sku), 'MLB')) {
            return $sku;
        }

        return '';
    }

    private function requireCompany(int $companyId): People
    {
        $people = $this->em->find(People::class, $companyId);
        if (!$people instanceof People) {
            throw new \InvalidArgumentException('company not found');
        }

        return $people;
    }

    private function assertCompanyAccess(int $companyId): void
    {
        if ($companyId < 1) {
            throw new \InvalidArgumentException('company_id is required');
        }
        foreach ($this->companyScopeProvider->listForCurrentUser() as $row) {
            if ((int) ($row['id'] ?? 0) === $companyId) {
                return;
            }
        }
        throw new \RuntimeException('Access denied to company_id ' . $companyId);
    }

    private function getConfigValue(int $companyId, string $key, ?string $fallbackKey = null): ?string
    {
        $people = $this->requireCompany($companyId);
        foreach (array_filter([$key, $fallbackKey]) as $candidate) {
            $configs = $this->em->getRepository(Config::class)->findBy([
                'people' => $people,
                'configKey' => $candidate,
            ]);
            if ($configs === []) {
                continue;
            }
            $value = current($configs)->getConfigValue();
            if ($value === null || $value === '') {
                continue;
            }
            if (is_string($value)) {
                return trim($value);
            }
            if (is_scalar($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }
}
