<?php

declare(strict_types=1);

namespace App\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderProduct;
use ControleOnline\Entity\Inventory;
use ControleOnline\Entity\Product;
use ControleOnline\Entity\ProductUnity;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\McpCompanyScopeProviderInterface;
use ControleOnline\Service\McpWriteOperationProviderInterface;
use ControleOnline\Service\OrderProductService;
use ControleOnline\Service\OrderCommercialContextService;
use ControleOnline\Service\StatusService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** MCP writes routed through the same company and catalog authorization services as the API. */
final class McpWriteOperationProvider implements McpWriteOperationProviderInterface
{
    private const CONFIG_KEYS = ['devices'];
    private const PRODUCT_FIELDS = ['product', 'sku', 'type', 'price', 'productCondition', 'description', 'active', 'productUnitId'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly PeopleService $peopleService,
        private readonly McpCompanyScopeProviderInterface $companyScopeProvider,
        private readonly ConfigService $configService,
        private readonly ProductService $productService,
        private readonly OrderProductService $orderProductService,
        private readonly StatusService $statusService,
        private readonly OrderCommercialContextService $commercialContextService,
    ) {
    }

    public function write(string $operation, array $arguments): array
    {
        $company = $this->managedCompany($arguments['company_id'] ?? null);
        $payload = $arguments['payload'] ?? null;
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('payload must be an object');
        }

        return match ($operation) {
            'upsert_company_config' => $this->upsertCompanyConfig($company, $payload),
            'create_product' => $this->createProduct($company, $payload),
            'update_product' => $this->updateProduct($company, $arguments['record_id'] ?? null, $payload),
            'create_stock_order' => $this->createStockOrder($company, $payload),
            default => throw new \InvalidArgumentException('Unsupported write operation'),
        };
    }

    /** @param mixed $companyId */
    private function managedCompany(mixed $companyId): People
    {
        if (!is_int($companyId) || $companyId < 1) {
            throw new \InvalidArgumentException('company_id must be a positive integer');
        }

        $user = $this->security->getUser();
        $actor = is_object($user) && method_exists($user, 'getPeople') ? $user->getPeople() : null;
        $company = $this->entityManager->getRepository(People::class)->find($companyId);
        $readableIds = array_map(
            static fn (array $item): int => (int) $item['id'],
            $this->companyScopeProvider->listForCurrentUser(),
        );

        if (!$company instanceof People
            || !in_array($companyId, $readableIds, true)
            || !$actor instanceof People
            || !$this->peopleService->canAccessCompany($company, $actor, PeopleLink::ADMIN_LINK)) {
            throw new AccessDeniedException('Company management access is required');
        }

        return $company;
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function createStockOrder(People $company, array $payload): array
    {
        $type = $payload['order_type'] ?? null;
        $partnerId = $payload['partner_id'] ?? null;
        $items = $payload['items'] ?? null;
        if (!in_array($type, ['purchase', 'sale', 'transfer'], true)
            || !is_array($items) || $items === [] || !array_is_list($items)) {
            throw new \InvalidArgumentException('order_type and a non-empty items list are required');
        }

        $partner = null;
        if ($type === 'transfer') {
            $destinationId = $payload['destination_company_id'] ?? null;
            $destination = is_int($destinationId) ? $this->managedCompany($destinationId) : null;
            if (!$destination instanceof People || $destination->getId() === $company->getId()) {
                throw new \InvalidArgumentException('A different managed destination_company_id is required for transfer orders');
            }
            $partner = $destination;
        } else {
            if (!is_int($partnerId) || $partnerId < 1) {
                throw new \InvalidArgumentException('partner_id is required for purchase and sale orders');
            }
            $partner = $this->entityManager->getRepository(People::class)->find($partnerId);
            if (!$partner instanceof People) {
                throw new \InvalidArgumentException('Order partner not found');
            }
            $role = $type === 'purchase' ? 'provider' : 'client';
            if (!$this->peopleService->canAccessCompany($company, $partner, [$role])) {
                throw new AccessDeniedException('Order partner is not linked to the selected company');
            }
        }

        $productsById = [];
        foreach ($items as $item) {
            if (!is_array($item) || !is_int($item['product_id'] ?? null) || $item['product_id'] < 1
                || !is_numeric($item['quantity'] ?? null) || (float) $item['quantity'] <= 0) {
                throw new \InvalidArgumentException('Each order item requires product_id and a positive quantity');
            }
            $product = $this->entityManager->getRepository(Product::class)->find($item['product_id']);
            if (!$product instanceof Product || (int) $product->getCompany()?->getId() !== (int) $company->getId()) {
                throw new AccessDeniedException('Order product is outside the selected company');
            }
            $this->productService->assertCanManageProduct($product);
            if (isset($productsById[$product->getId()])) {
                throw new \InvalidArgumentException('Use one order line per product');
            }
            $allowedLineFields = $type === 'transfer'
                ? ['product_id', 'quantity', 'in_inventory_id', 'out_inventory_id']
                : ['product_id', 'quantity', 'comment'];
            if (array_diff(array_keys($item), $allowedLineFields) !== []) {
                throw new \InvalidArgumentException('Unsupported order item fields');
            }
            $productsById[$product->getId()] = $product;
        }

        $destination = $type === 'transfer'
            ? $this->managedCompany((int) $payload['destination_company_id'])
            : null;
        $order = new Order();
        $order->setProvider($type === 'purchase' ? $partner : $company);
        $order->setClient($type === 'purchase' ? $company : ($destination ?? $partner));
        $order->setPayer($type === 'purchase' ? $company : ($destination ?? $partner));
        $order->setOrderType($type);
        $order->setStatus($this->statusService->discoveryStatus('open', 'open', 'order'));
        $order->setApp('mcp');
        $this->commercialContextService->prepare($order);

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            foreach ($items as $item) {
                $product = $productsById[$item['product_id']];
                if ($type === 'transfer') {
                    $outInventory = $this->managedInventory($item['out_inventory_id'] ?? null, $company);
                    $inInventory = $this->managedInventory($item['in_inventory_id'] ?? null, $destination);
                    $line = new OrderProduct();
                    $line->setOrder($order);
                    $line->setProduct($product);
                    $line->setQuantity((float) $item['quantity']);
                    $line->setPrice((float) $product->getPrice());
                    $line->setTotal((float) $product->getPrice() * (float) $item['quantity']);
                    $line->setInInventory($inInventory);
                    $line->setOutInventory($outInventory);
                    $line->setStatus($this->statusService->discoveryStatus('open', 'open', 'order_product'));
                    $this->entityManager->persist($line);
                    $order->addOrderProduct($line);
                    continue;
                }

                $this->orderProductService->addOrderProduct(
                    $order,
                    $product,
                    (float) $item['quantity'],
                    (float) $product->getPrice(),
                    comment: isset($item['comment']) && is_string($item['comment']) ? $item['comment'] : null,
                );
            }
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) $connection->rollBack();
            $this->entityManager->clear();
            throw $exception;
        }

        return ['operation' => 'create_stock_order', 'company_id' => $company->getId(), 'order_id' => (int) $order->getId(), 'order_type' => $type, 'item_count' => count($items), 'saved' => true];
    }

    private function managedInventory(mixed $inventoryId, People $company): Inventory
    {
        if (!is_int($inventoryId) || $inventoryId < 1) {
            throw new \InvalidArgumentException('Transfer items require in_inventory_id and out_inventory_id');
        }
        $inventory = $this->entityManager->getRepository(Inventory::class)->find($inventoryId);
        if (!$inventory instanceof Inventory || (int) $inventory->getPeople()?->getId() !== (int) $company->getId()) {
            throw new AccessDeniedException('Inventory is outside the selected company');
        }

        return $inventory;
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function upsertCompanyConfig(People $company, array $payload): array
    {
        $key = $payload['config_key'] ?? null;
        $value = $payload['config_value'] ?? null;
        if (!is_string($key) || !in_array($key, self::CONFIG_KEYS, true) || !is_array($value)) {
            throw new \InvalidArgumentException('Only the devices configuration accepts structured writes');
        }
        $this->assertNoCredentialFields($value);

        $config = $this->configService->addConfigFromPayload([
            'people' => $company->getId(),
            'configKey' => $key,
            'configValue' => $value,
            'visibility' => 'private',
        ]);

        return ['operation' => 'upsert_company_config', 'company_id' => $company->getId(), 'config_key' => $config->getConfigKey(), 'saved' => true];
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function createProduct(People $company, array $payload): array
    {
        $this->assertProductFields($payload);
        $product = new Product();
        $product->setCompany($company);
        $this->applyProductFields($product, $payload);
        $this->productService->assertCanManageProduct($product);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return ['operation' => 'create_product', 'company_id' => $company->getId(), 'product_id' => (int) $product->getId(), 'saved' => true];
    }

    /** @param mixed $recordId
     *  @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function updateProduct(People $company, mixed $recordId, array $payload): array
    {
        if (!is_int($recordId) || $recordId < 1) {
            throw new \InvalidArgumentException('record_id must be a positive integer');
        }
        $this->assertProductFields($payload, false);
        $product = $this->entityManager->getRepository(Product::class)->find($recordId);
        if (!$product instanceof Product || (int) $product->getCompany()?->getId() !== (int) $company->getId()) {
            throw new AccessDeniedException('Product is outside the selected company');
        }
        $this->productService->assertCanManageProduct($product);
        $this->applyProductFields($product, $payload);
        $this->productService->assertCanManageProduct($product);
        $this->entityManager->flush();

        return ['operation' => 'update_product', 'company_id' => $company->getId(), 'product_id' => (int) $product->getId(), 'saved' => true];
    }

    /** @param array<string, mixed> $payload */
    private function assertProductFields(array $payload, bool $requireName = true): void
    {
        if (array_diff(array_keys($payload), self::PRODUCT_FIELDS) !== []) {
            throw new \InvalidArgumentException('Unsupported product fields');
        }
        if ($requireName && (!is_string($payload['product'] ?? null) || trim($payload['product']) === '')) {
            throw new \InvalidArgumentException('product is required');
        }
        if ($requireName && (!is_int($payload['productUnitId'] ?? null) || $payload['productUnitId'] < 1)) {
            throw new \InvalidArgumentException('productUnitId is required when creating a product');
        }
        if (isset($payload['type']) && !in_array($payload['type'], ['product', 'custom', 'component', 'service', 'manufactured'], true)) {
            throw new \InvalidArgumentException('Unsupported product type');
        }
        if (isset($payload['price']) && (!is_numeric($payload['price']) || (float) $payload['price'] < 0)) {
            throw new \InvalidArgumentException('price must be zero or greater');
        }
        foreach (['sku', 'type', 'productCondition', 'description'] as $field) {
            if (isset($payload[$field]) && !is_string($payload[$field])) {
                throw new \InvalidArgumentException($field . ' must be a string');
            }
        }
        if (isset($payload['active']) && !is_bool($payload['active'])) {
            throw new \InvalidArgumentException('active must be boolean');
        }
    }

    /** @param array<string, mixed> $payload */
    private function applyProductFields(Product $product, array $payload): void
    {
        if (isset($payload['product'])) $product->setProduct(trim($payload['product']));
        if (array_key_exists('sku', $payload)) $product->setSku($payload['sku']);
        if (isset($payload['type'])) $product->setType($payload['type']);
        if (isset($payload['price'])) $product->setPrice((float) $payload['price']);
        if (isset($payload['productCondition'])) $product->setProductCondition($payload['productCondition']);
        if (array_key_exists('description', $payload)) $product->setDescription($payload['description']);
        if (isset($payload['active'])) $product->setActive($payload['active']);
        if (isset($payload['productUnitId'])) {
            $unit = $this->entityManager->getRepository(ProductUnity::class)->find((int) $payload['productUnitId']);
            if (!$unit instanceof ProductUnity) throw new \InvalidArgumentException('Product unit not found');
            $product->setProductUnit($unit);
        }
    }

    /** @param array<mixed> $value */
    private function assertNoCredentialFields(array $value): void
    {
        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/password|secret|token|credential|private.?key/i', $key) === 1) {
                throw new \InvalidArgumentException('Credential fields cannot be written through MCP');
            }
            if (is_array($item)) $this->assertNoCredentialFields($item);
        }
    }
}
