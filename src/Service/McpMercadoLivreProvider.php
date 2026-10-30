<?php

declare(strict_types=1);

namespace App\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpMercadoLivreProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Mercado Livre seller tools for MCP.
 *
 * Tokens are stored per company in Config by the integration OAuth flow
 * (api-platform-integration MercadoLivreService) and never returned to the client.
 *
 * Canonical keys (must match MercadoLivreService):
 * - mercado-livre-access-token
 * - mercado-livre-refresh-token (optional)
 * - mercado-livre-user-id (optional)
 *
 * Legacy keys still accepted as fallback:
 * - mercadolivre_access_token / mercadolivre_refresh_token / mercadolivre_user_id
 *
 * OAuth endpoints (ROLE_HUMAN, company-scoped):
 * - POST /marketplace/integrations/mercadolivre/authorization-page
 * - GET  /oauth/mercadolivre/return (callback, PKCE + state bound to provider)
 *
 * All MCP calls are scoped to companies the current user can access.
 */
final class McpMercadoLivreProvider implements McpMercadoLivreProviderInterface
{
    private const API_BASE = 'https://api.mercadolibre.com';
    /** Canonical keys from ControleOnline\Service\MercadoLivreService */
    private const CONFIG_ACCESS = 'mercado-livre-access-token';
    private const CONFIG_REFRESH = 'mercado-livre-refresh-token';
    private const CONFIG_USER_ID = 'mercado-livre-user-id';

    /** Legacy keys from the first MCP provider slice (kept as read fallback). */
    private const CONFIG_ACCESS_LEGACY = 'mercadolivre_access_token';
    private const CONFIG_REFRESH_LEGACY = 'mercadolivre_refresh_token';
    private const CONFIG_USER_ID_LEGACY = 'mercadolivre_user_id';
    private const CONFIG_EXPIRES_LEGACY = 'mercadolivre_token_expires_at';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
        private readonly McpCompanyScopeProviderInterface $companyScopeProvider,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function getStatus(int $companyId): array
    {
        $this->assertCompanyAccess($companyId);
        $token = $this->getAccessToken($companyId);
        $userId = $this->getConfigValue($companyId, self::CONFIG_USER_ID, self::CONFIG_USER_ID_LEGACY);
        $expires = $this->getConfigValue($companyId, self::CONFIG_EXPIRES_LEGACY);

        return [
            'company_id' => $companyId,
            'connected' => $token !== null && $token !== '',
            'user_id' => $userId,
            'token_expires_at' => $expires !== null ? (int) $expires : null,
            'has_refresh_token' => $this->getConfigValue($companyId, self::CONFIG_REFRESH, self::CONFIG_REFRESH_LEGACY) !== null,
            'oauth' => [
                'authorization_endpoint' => '/marketplace/integrations/mercadolivre/authorization-page',
                'callback_endpoint' => '/oauth/mercadolivre/return',
            ],
        ];
    }

    public function getCurrentUser(int $companyId): array
    {
        $this->assertCompanyAccess($companyId);
        $data = $this->apiGet($companyId, '/users/me');

        return $this->sanitizeUser($data);
    }

    public function searchItems(int $companyId, int $offset = 0, int $limit = 50): array
    {
        $this->assertCompanyAccess($companyId);
        $userId = $this->resolveSellerId($companyId);
        $offset = max(0, $offset);
        $limit = max(1, min(100, $limit));

        $data = $this->apiGet($companyId, sprintf('/users/%s/items/search', rawurlencode($userId)), [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        return [
            'company_id' => $companyId,
            'seller_id' => $userId,
            'results' => $data['results'] ?? [],
            'paging' => $data['paging'] ?? [
                'total' => isset($data['results']) ? count($data['results']) : 0,
                'offset' => $offset,
                'limit' => $limit,
            ],
        ];
    }

    public function getItem(int $companyId, string $itemId): array
    {
        $this->assertCompanyAccess($companyId);
        $itemId = trim($itemId);
        if ($itemId === '') {
            throw new \InvalidArgumentException('item_id is required');
        }

        return $this->sanitizeItem($this->apiGet($companyId, '/items/' . rawurlencode($itemId)));
    }

    public function getItemDescription(int $companyId, string $itemId): array
    {
        $this->assertCompanyAccess($companyId);
        $itemId = trim($itemId);
        if ($itemId === '') {
            throw new \InvalidArgumentException('item_id is required');
        }

        $data = $this->apiGet($companyId, sprintf('/items/%s/description', rawurlencode($itemId)));

        return [
            'item_id' => $itemId,
            'plain_text' => $data['plain_text'] ?? null,
            'text' => $data['text'] ?? null,
        ];
    }

    public function getCategory(int $companyId, string $categoryId): array
    {
        $this->assertCompanyAccess($companyId);
        $categoryId = trim($categoryId);
        if ($categoryId === '') {
            throw new \InvalidArgumentException('category_id is required');
        }

        // Category metadata is public on ML API; still requires a connected company for audit.
        $this->requireAccessToken($companyId);
        $data = $this->apiGet($companyId, '/categories/' . rawurlencode($categoryId));

        return [
            'id' => $data['id'] ?? $categoryId,
            'name' => $data['name'] ?? null,
            'path_from_root' => $data['path_from_root'] ?? [],
        ];
    }

    public function getOrder(int $companyId, string $orderId): array
    {
        $this->assertCompanyAccess($companyId);
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw new \InvalidArgumentException('order_id is required');
        }

        return $this->sanitizeOrder($this->apiGet($companyId, '/orders/' . rawurlencode($orderId)));
    }

    public function importProducts(int $companyId, int $showcaseId, int $limit = 50): array
    {
        $this->assertCompanyAccess($companyId);
        if ($showcaseId < 1) {
            throw new \InvalidArgumentException('showcase_id must be >= 1');
        }
        $limit = max(1, min(100, $limit));

        // First slice: list remote items only. Full product upsert is a follow-up task.
        $search = $this->searchItems($companyId, 0, $limit);
        $imported = [];
        foreach ($search['results'] as $itemId) {
            if (!is_string($itemId) || $itemId === '') {
                continue;
            }
            try {
                $item = $this->getItem($companyId, $itemId);
                $imported[] = [
                    'item_id' => $itemId,
                    'title' => $item['title'] ?? null,
                    'status' => $item['status'] ?? null,
                    'available_quantity' => $item['available_quantity'] ?? null,
                    'price' => $item['price'] ?? null,
                    'showcase_id' => $showcaseId,
                    'import_status' => 'listed_only',
                ];
            } catch (\Throwable $e) {
                $this->logger?->warning('Mercado Livre item import skipped', [
                    'company_id' => $companyId,
                    'item_id' => $itemId,
                    'exception_class' => $e::class,
                ]);
            }
        }

        return [
            'company_id' => $companyId,
            'showcase_id' => $showcaseId,
            'count' => count($imported),
            'items' => $imported,
            'note' => 'listed_only: product upsert into local catalog is not implemented in this slice',
        ];
    }

    private function assertCompanyAccess(int $companyId): void
    {
        if ($companyId < 1) {
            throw new \InvalidArgumentException('company_id must be >= 1');
        }
        $companies = $this->companyScopeProvider->listForCurrentUser();
        $ids = array_map(static fn (array $c): int => (int) $c['id'], $companies);
        if (!in_array($companyId, $ids, true)) {
            throw new \InvalidArgumentException('company_id is not accessible to the current user');
        }
    }

    private function getConfigValue(int $companyId, string $key, ?string $fallbackKey = null): ?string
    {
        $people = $this->em->find(People::class, $companyId);
        if ($people === null) {
            throw new \InvalidArgumentException('company not found');
        }

        foreach (array_filter([$key, $fallbackKey], static fn ($k) => is_string($k) && $k !== '') as $candidate) {
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
                $trimmed = trim($value);
                // Integration may JSON-encode scalar config values.
                if ($trimmed !== '' && ($trimmed[0] === '"' || $trimmed[0] === '{')) {
                    try {
                        $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                        if (is_scalar($decoded)) {
                            return trim((string) $decoded);
                        }
                    } catch (\JsonException) {
                    }
                }
                return $trimmed;
            }
            if (is_scalar($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function getAccessToken(int $companyId): ?string
    {
        return $this->getConfigValue($companyId, self::CONFIG_ACCESS, self::CONFIG_ACCESS_LEGACY);
    }

    private function requireAccessToken(int $companyId): string
    {
        $token = $this->getAccessToken($companyId);
        if ($token === null || $token === '') {
            throw new \RuntimeException('Mercado Livre is not connected for this company (missing access token config)');
        }

        return $token;
    }

    private function resolveSellerId(int $companyId): string
    {
        $stored = $this->getConfigValue($companyId, self::CONFIG_USER_ID, self::CONFIG_USER_ID_LEGACY);
        if ($stored !== null && $stored !== '') {
            return $stored;
        }
        $me = $this->apiGet($companyId, '/users/me');
        $id = $me['id'] ?? null;
        if ($id === null) {
            throw new \RuntimeException('Unable to resolve Mercado Livre seller id');
        }

        return (string) $id;
    }

    /**
     * @param array<string, scalar> $query
     * @return array<string, mixed>
     */
    private function apiGet(int $companyId, string $path, array $query = []): array
    {
        $token = $this->requireAccessToken($companyId);
        $url = self::API_BASE . $path;
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'query' => $query,
                'timeout' => 20,
            ]);
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger?->error('Mercado Livre API request failed', [
                'company_id' => $companyId,
                'path' => $path,
                'exception_class' => $e::class,
            ]);
            throw new \RuntimeException('Mercado Livre API request failed');
        }

        if ($status >= 400) {
            $this->logger?->warning('Mercado Livre API error status', [
                'company_id' => $companyId,
                'path' => $path,
                'status' => $status,
            ]);
            $message = is_array($data) && isset($data['message']) ? (string) $data['message'] : 'Mercado Livre API error';
            throw new \RuntimeException($message);
        }

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $data */
    private function sanitizeUser(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'permalink' => $data['permalink'] ?? null,
            'site_id' => $data['site_id'] ?? null,
            'user_type' => $data['user_type'] ?? null,
            'tags' => $data['tags'] ?? [],
        ];
    }

    /** @param array<string, mixed> $data */
    private function sanitizeItem(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'title' => $data['title'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'price' => $data['price'] ?? null,
            'currency_id' => $data['currency_id'] ?? null,
            'available_quantity' => $data['available_quantity'] ?? null,
            'sold_quantity' => $data['sold_quantity'] ?? null,
            'status' => $data['status'] ?? null,
            'permalink' => $data['permalink'] ?? null,
            'thumbnail' => $data['thumbnail'] ?? null,
        ];
    }

    /** @param array<string, mixed> $data */
    private function sanitizeOrder(array $data): array
    {
        $orderItems = [];
        foreach ($data['order_items'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = is_array($row['item'] ?? null) ? $row['item'] : [];
            $orderItems[] = [
                'item_id' => $item['id'] ?? null,
                'title' => $item['title'] ?? null,
                'quantity' => $row['quantity'] ?? null,
                'unit_price' => $row['unit_price'] ?? null,
            ];
        }

        return [
            'id' => $data['id'] ?? null,
            'status' => $data['status'] ?? null,
            'date_created' => $data['date_created'] ?? null,
            'date_closed' => $data['date_closed'] ?? null,
            'total_amount' => $data['total_amount'] ?? null,
            'currency_id' => $data['currency_id'] ?? null,
            'order_items' => $orderItems,
        ];
    }
}
