<?php

namespace ControleOnline\Common\Tests\Serializer;

use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Doctrine\Extension\CollectionDoctrineQueryDebugExtension;
use ControleOnline\Serializer\CollectionSummaryNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CollectionSummaryNormalizerTest extends TestCase
{
    public function testAddsDebugQueryFromCurrentRequest(): void
    {
        $request = Request::create('/orders', 'GET');
        $request->attributes->set(CollectionDoctrineQueryDebugExtension::REQUEST_ATTRIBUTE, [
            'filledQuery' => "SELECT * FROM orders WHERE id = '1'",
            'parameters' => ['id' => 1],
            'query' => "SELECT * FROM orders WHERE id = 1",
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $normalizer = new CollectionSummaryNormalizer($requestStack);
        $normalizer->setNormalizer(new class implements NormalizerInterface {
            public function normalize(mixed $object, ?string $format = null, array $context = []): array
            {
                return [
                    'member' => [],
                    'totalItems' => 0,
                ];
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function getSupportedTypes(?string $format): array
            {
                return ['*' => false];
            }
        });

        $result = $normalizer->normalize([], 'jsonld', [
            'operation' => new GetCollection(class: DebugQueryNormalizerFixture::class),
        ]);

        self::assertSame("SELECT * FROM orders WHERE id = 1", $result['debug']['query'] ?? null);
        self::assertSame("SELECT * FROM orders WHERE id = '1'", $result['debug']['filledQuery'] ?? null);
        self::assertSame(['id' => 1], $result['debug']['parameters'] ?? null);
    }
}

final class DebugQueryNormalizerFixture
{
}
