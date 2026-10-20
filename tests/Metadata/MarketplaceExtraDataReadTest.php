<?php

use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use ControleOnline\Repository\ExtraDataRepository;
use ControleOnline\Repository\ExtraFieldsRepository;
use ControleOnline\Service\ExtraDataService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class MarketplaceExtraDataReadTest extends TestCase
{
    public function testStatusReadsBatchByEntityKeepLatestAndDoNotSurviveRequest(): void
    {
        $field = (new ExtraFields())->setName('sync_hash')->setContext('ifood')->setType('text');
        (new ReflectionProperty($field, 'id'))->setValue($field, 17);
        $old = (new ExtraData())->setExtraFields($field)->setValue('old');
        $latest = (new ExtraData())->setExtraFields($field)->setValue(' latest ');
        $fields = $this->createMock(ExtraFieldsRepository::class);
        // One definition lookup in each request, including repeated calls for the same entity.
        $fields->expects(self::exactly(2))->method('findOneByContextNameType')->willReturn($field);
        $data = $this->createMock(ExtraDataRepository::class);
        $data->expects(self::exactly(2))->method('findByEntityNameEntityId')
            ->with('Product', 9)->willReturnOnConsecutiveCalls([$old, $latest], []);
        $data->expects(self::never())->method('findOneByExtraFieldsEntityNameEntityId');
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnCallback(fn ($class) => $class === ExtraFields::class ? $fields : $data);
        $stack = new RequestStack();
        $stack->push(Request::create('/marketplace/integrations/catalog/status'));
        $service = (new ReflectionClass(ExtraDataService::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($service, 'manager'))->setValue($service, $manager);
        (new ReflectionProperty($service, 'requestStack'))->setValue($service, $stack);
        self::assertSame('latest', $service->getExtraDataValue('ifood', 'Product', 9, 'sync_hash'));
        self::assertSame('latest', $service->getExtraDataValue('ifood', 'Product', 9, 'sync_hash'));
        $stack->pop();
        $stack->push(Request::create('/marketplace/integrations/catalog/status'));
        self::assertNull($service->getExtraDataValue('ifood', 'Product', 9, 'sync_hash'));
        self::assertNull($service->getExtraDataValue('ifood', 'Product', 9, 'sync_hash'));
    }
    public function testOnlyTheTwoReadOnlyMarketplaceEndpointsUseSnapshots(): void
    {
        self::assertTrue(\ControleOnline\Service\MarketplaceExtraDataRead::supports(Request::create('/marketplace/integrations')));
        foreach ([Request::create('/marketplace/integrations', 'POST'), Request::create('/products'), null] as $request) {
            self::assertFalse(\ControleOnline\Service\MarketplaceExtraDataRead::supports($request));
        }
    }

    public function testMutationRequestsKeepFreshScalarLookupSemantics(): void
    {
        $field = (new ExtraFields())->setName('code')->setContext('ifood')->setType('text');
        (new ReflectionProperty($field, 'id'))->setValue($field, 17);
        $fields = $this->createMock(ExtraFieldsRepository::class);
        $fields->expects(self::exactly(2))->method('findOneByContextNameType')->willReturn($field);
        $data = $this->createMock(ExtraDataRepository::class);
        $data->expects(self::never())->method('findByEntityNameEntityId');
        $data->expects(self::exactly(2))->method('findOneByExtraFieldsEntityNameEntityId')
            ->willReturnOnConsecutiveCalls((new ExtraData())->setValue('first'), (new ExtraData())->setValue('second'));
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnCallback(fn ($class) => $class === ExtraFields::class ? $fields : $data);
        $stack = new RequestStack();
        $stack->push(Request::create('/marketplace/integrations', 'POST'));
        $service = (new ReflectionClass(ExtraDataService::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($service, 'manager'))->setValue($service, $manager);
        (new ReflectionProperty($service, 'requestStack'))->setValue($service, $stack);
        self::assertSame('first', $service->getExtraDataValue('ifood', 'Product', 9));
        self::assertSame('second', $service->getExtraDataValue('ifood', 'Product', 9));
    }

}
