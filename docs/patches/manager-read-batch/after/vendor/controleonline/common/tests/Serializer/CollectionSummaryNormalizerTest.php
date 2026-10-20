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
    public function testSymfonySelectsBatchNormalizerForPlainCollection(): void
    {
        $collection = new \ArrayIterator([new \ControleOnline\Entity\People()]);
        $hydrator = $this->createMock(\ControleOnline\Service\CollectionReadBatchHydrator::class);
        $hydrator->expects(self::once())->method('prepare')->with($collection, \ControleOnline\Entity\People::class)->willReturn(new \WeakMap());
        $delegate = new class implements NormalizerInterface {
            public function getSupportedTypes(?string $format): array { return ['object' => false, 'array' => false]; }
            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool { return is_iterable($data); }
            public function normalize(mixed $object, ?string $format = null, array $context = []): array { return ['member' => [], 'totalItems' => 1]; }
        };
        $serializer = new \Symfony\Component\Serializer\Serializer([new CollectionSummaryNormalizer(null, $hydrator), $delegate]);
        self::assertSame(['member' => [], 'totalItems' => 1], $serializer->normalize($collection, 'jsonld', ['operation' => new GetCollection(class: \ControleOnline\Entity\People::class)]));
    }

    public function testBatchingPreservesTheOriginalFilteredPaginatorSummaryAndContext(): void
    {
        $collection = new \ArrayIterator([new \ControleOnline\Entity\Product()]);
        $wrapper = new \ControleOnline\State\CollectionSummaryResult($collection, ['total' => 91]);
        $preloaded = new \WeakMap();
        $hydrator = $this->createMock(\ControleOnline\Service\CollectionReadBatchHydrator::class);
        $hydrator->expects(self::once())->method('prepare')
            ->with($collection, \ControleOnline\Entity\Product::class)->willReturn($preloaded);
        $delegate = $this->createMock(NormalizerInterface::class);
        $operation = new GetCollection(class: \ControleOnline\Entity\Product::class);
        $delegate->expects(self::once())->method('normalize')->with($collection, 'jsonld', self::callback(
            static fn(array $context) => $context['operation'] === $operation
                && $context['filters'] === ['company' => '/people/3']
                && $context['_extra_data_preloaded'] === $preloaded
        ))->willReturn(['hydra:member' => [['id' => 7]], 'hydra:totalItems' => 27]);
        $normalizer = new CollectionSummaryNormalizer(null, $hydrator);
        $normalizer->setNormalizer($delegate);
        self::assertSame(['member' => [['id' => 7]], 'totalItems' => 27, 'summary' => ['total' => 91]],
            $normalizer->normalize($wrapper, 'jsonld', ['operation' => $operation, 'filters' => ['company' => '/people/3']]));
    }

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
