<?php

namespace ControleOnline\Common\Tests\Serializer;

use ControleOnline\Serializer\ExtraDataNormalizer;
use ControleOnline\Service\ExtraDataService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class ExtraDataNormalizerTest extends TestCase
{
    public function testReadsEachRepeatedEntityOnceAndPreservesTheFullResponse(): void
    {
        $entities = array_map(fn ($id) => new ExtraDataFixture($id), range(1, 22));
        $input = array_map(fn ($i) => $entities[$i % 22], range(0, 844));
        $service = $this->createMock(ExtraDataService::class);
        $service->expects(self::exactly(22))->method('getExtraDataFromEntity')->willReturn([]);
        $serializer = new Serializer([new ExtraDataNormalizer($service), new ObjectNormalizer()]);
        $output = $serializer->normalize($input);
        self::assertCount(845, $output);
        foreach ($output as $i => $row) {
            self::assertSame(($i % 22) + 1, $row['id']);
            self::assertSame([], $row['extraData']);
        }
    }

    public function testDoesNotReuseDataAcrossSeparateSerializations(): void
    {
        $entity = new ExtraDataFixture(1);
        $service = $this->createMock(ExtraDataService::class);
        $service->expects(self::exactly(2))->method('getExtraDataFromEntity')->willReturn([]);
        $serializer = new Serializer([new ExtraDataNormalizer($service), new ObjectNormalizer()]);
        self::assertSame($serializer->normalize([$entity, $entity]), $serializer->normalize([$entity, $entity]));
    }

    public function testPreservesExtraFieldMetadataAndReadsNewValuesOnTheNextResponse(): void
    {
        $fields = $this->createStub(\ControleOnline\Entity\ExtraFields::class);
        foreach (['getId' => 9, 'getName' => 'code', 'getType' => 'text', 'getContext' => 'PDV',
            'getConfigs' => '{}', 'getRequired' => true] as $method => $value) {
            $fields->method($method)->willReturn($value);
        }
        $extra = $this->createStub(\ControleOnline\Entity\ExtraData::class);
        foreach (['getId' => 11, 'getEntityId' => '1', 'getEntityName' => 'Product',
            'getSource' => 'ERP', 'getExtraFields' => $fields] as $method => $value) {
            $extra->method($method)->willReturn($value);
        }
        $extra->method('getValue')->willReturnOnConsecutiveCalls('first', 'second');
        $service = $this->createMock(ExtraDataService::class);
        $service->expects(self::exactly(2))->method('getExtraDataFromEntity')->willReturn([$extra]);
        $serializer = new Serializer([new ExtraDataNormalizer($service), new ObjectNormalizer()]);
        $entity = new ExtraDataFixture(1);
        $first = $serializer->normalize([$entity]);
        $second = $serializer->normalize([$entity]);
        self::assertSame('first', $first[0]['extraData'][0]['value']);
        self::assertSame('second', $second[0]['extraData'][0]['value']);
        self::assertSame(['id' => 9, 'name' => 'code', 'type' => 'text', 'context' => 'PDV',
            'configs' => '{}', 'required' => true], $second[0]['extraData'][0]['extra_fields']);
    }

    public function testSharesTheReadAcrossNestedBranchesWithoutDroppingFields(): void
    {
        $product = new ExtraDataFixture(1343);
        $service = $this->createMock(ExtraDataService::class);
        $service->expects(self::once())->method('getExtraDataFromEntity')->willReturn([]);
        $serializer = new Serializer([new ExtraDataNormalizer($service), new ObjectNormalizer()]);
        $input = [['product' => $product, 'children' => [['product' => $product]]], ['product' => $product]];
        $result = $serializer->normalize($input);
        self::assertSame(1343, $result[0]['children'][0]['product']['id']);
        self::assertSame(1343, $result[1]['product']['id']);
    }
}

final class ExtraDataFixture
{
    public array $extraData = [];
    public function __construct(public int $id) {}
    public function setExtraData(array $value): void { $this->extraData = $value; }
}
