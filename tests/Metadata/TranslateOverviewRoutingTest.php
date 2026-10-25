<?php

use ApiPlatform\Metadata\ApiResource;
use App\Kernel;
use ControleOnline\Entity\TranslateOverview;
use PHPUnit\Framework\TestCase;

final class TranslateOverviewRoutingTest extends TestCase
{
    public function testOverviewIsMatchedBeforeTheGenericTranslateIdentifier(): void
    {
        $kernel = new Kernel('dev', false);
        try {
            $kernel->boot();
            $match = $kernel->getContainer()->get('router')->match('/translates/overview');
            self::assertSame(TranslateOverview::class, $match['_api_resource_class']);
        } finally {
            $kernel->shutdown();
        }
        $resource = (new ReflectionClass(TranslateOverview::class))->getAttributes(ApiResource::class)[0]->newInstance();
        $operation = iterator_to_array($resource->getOperations())[0];
        self::assertSame("is_granted('ROLE_HUMAN')", $operation->getSecurity());
    }
}
