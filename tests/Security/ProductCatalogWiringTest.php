<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Kernel;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ControleOnline\Entity\{Product, ProductGroup, ProductPeople, ProductCategory, ProductFile, ProductGroupParent, ProductGroupProduct, Inventory, ProductInventory, ProductShowcase, ProductShowcaseItem};
use ControleOnline\Doctrine\Extension\ProductCatalogSecurityExtension;
use ControleOnline\Listener\ProductCatalogWriteListener;
use ControleOnline\Security\ProductCatalogVoter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Exercises installed package registration, rather than a manually assembled factory. */
final class ProductCatalogWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testInstalledCatalogResourcesCarryCompanyWriteAuthorization(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $factory = $container->get(ResourceMetadataCollectionFactoryInterface::class);
        $resources = [Product::class, ProductGroup::class, ProductPeople::class, ProductCategory::class,
            ProductFile::class, ProductGroupParent::class, ProductGroupProduct::class,
            Inventory::class, ProductInventory::class, ProductShowcase::class, ProductShowcaseItem::class];

        foreach ($resources as $class) {
            $writes = 0;
            foreach ($factory->create($class) as $resource) {
                foreach ($resource->getOperations() ?? [] as $operation) {
                    if (in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                        ++$writes;
                        self::assertStringContainsString('CATALOG_MANAGE',
                            ($operation->getSecurity() ?? '').($operation->getSecurityPostDenormalize() ?? ''), $class);
                    } elseif ($operation->getMethod() === 'GET') {
                        self::assertStringContainsString('ROLE_HUMAN', $operation->getSecurity() ?? '', $class);
                    }
                }
            }
            self::assertGreaterThan(0, $writes, $class);
        }

        self::assertInstanceOf(ProductCatalogSecurityExtension::class, $container->get(ProductCatalogSecurityExtension::class));
        self::assertInstanceOf(ProductCatalogVoter::class, $container->get(ProductCatalogVoter::class));
        $listeners = $container->get('doctrine')->getManager()->getEventManager()->getListeners('onFlush');
        self::assertTrue((bool) array_filter($listeners, static fn ($listener) => $listener instanceof ProductCatalogWriteListener));
    }
}
