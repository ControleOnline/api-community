<?php
namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\Product;
use ControleOnline\Repository\ExtraDataRepository;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\ExtraDataService;
use ControleOnline\Service\SkyNetService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ExtraDataBatchReadTest extends TestCase
{
    public function testBatchKeepsEmptyEntriesAndDoesNotWriteOrQueryPerProduct(): void
    {
        $a = new Product(); $b = new Product();
        $id = new \ReflectionProperty(Product::class, 'id'); $id->setValue($a, 2); $id->setValue($b, 3);
        $row = $this->createStub(ExtraData::class); $row->method('getEntityId')->willReturn('2');
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('persist'); $manager->expects(self::never())->method('flush');
        $repo = $this->createMock(ExtraDataRepository::class);
        $manager->expects(self::once())->method('getRepository')->with(ExtraData::class)->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects(self::once())->method('createQueryBuilder')->with('batch_extra')->willReturn($qb);
        $qb->expects(self::once())->method('leftJoin')->with('batch_extra.extra_fields', 'batch_fields')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf(); $qb->method('andWhere')->willReturnSelf();
        $params = []; $qb->method('setParameter')->willReturnCallback(function($key, $value) use (&$params, $qb) { $params[$key] = $value; return $qb; });
        $qb->method('orderBy')->willReturnSelf();
        $query = $this->createMock(Query::class); $query->expects(self::once())->method('getResult')->willReturn([$row]);
        $qb->method('getQuery')->willReturn($query);
        $service = new ExtraDataService($manager, new RequestStack(), $this->createStub(TokenStorageInterface::class),
            $this->createStub(DeviceService::class), $this->createStub(SkyNetService::class));
        $cache = $service->getExtraDataFromEntities([$a, $a, $b, new \stdClass()]);
        self::assertSame(['entityName' => 'Product', 'entityIds' => [2, 3]], $params);
        self::assertSame([$row], $cache[$a]); self::assertSame([], $cache[$b]); self::assertCount(2, $cache);
    }
}
