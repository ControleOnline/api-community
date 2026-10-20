<?php
namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Product;
use ControleOnline\Service\CollectionReadBatchHydrator;
use ControleOnline\Service\ExtraDataService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class CollectionReadBatchHydratorTest extends TestCase
{
    public function testHydratesPeopleRelationsSeparatelyForOnlyTheAlreadyAuthorizedPage(): void
    {
        $people = [new People(), new People()];
        $manager = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $manager->expects(self::once())->method('getRepository')->with(People::class)->willReturn($repo);
        $relations = [];
        $repo->expects(self::exactly(7))->method('createQueryBuilder')->with('batch_root')
            ->willReturnCallback(function () use (&$relations, $people): QueryBuilder {
                $qb = $this->createMock(QueryBuilder::class);
                $qb->method('leftJoin')->willReturnCallback(function ($path) use (&$relations, $qb) {
                    $relations[] = $path; return $qb;
                });
                $qb->method('addSelect')->willReturnSelf();
                $qb->expects(self::once())->method('andWhere')->with('batch_root IN (:batchPage)')->willReturnSelf();
                $qb->expects(self::once())->method('setParameter')->with('batchPage', $people)->willReturnSelf();
                $query = $this->createMock(Query::class);
                $query->expects(self::once())->method('getResult')->willReturn($people);
                $qb->method('getQuery')->willReturn($query);
                return $qb;
            });
        $extras = $this->createMock(ExtraDataService::class);
        $extras->expects(self::never())->method('getExtraDataFromEntities');
        $cache = (new CollectionReadBatchHydrator($manager, $extras))->prepare($people, People::class);
        self::assertCount(0, $cache);
        self::assertContains('batch_root.phone', $relations);
        self::assertContains('batch_relation.product', $relations);
        self::assertContains('batch_relation.street', $relations);
        self::assertContains('batch_address_street.district', $relations);
        self::assertContains('batch_address_street.cep', $relations);
        self::assertContains('batch_address_district.city', $relations);
        self::assertContains('batch_address_city.state', $relations);
        self::assertContains('batch_address_state.country', $relations);
    }

    public function testDoesNotConsumeSinglePassCollections(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('getRepository');
        $extras = $this->createMock(ExtraDataService::class);
        $extras->expects(self::never())->method('getExtraDataFromEntities');
        $person = new People();
        $generator = (static function () use ($person) { yield $person; })();
        self::assertCount(0, (new CollectionReadBatchHydrator($manager, $extras))->prepare($generator, People::class));
        self::assertSame([$person], iterator_to_array($generator));
    }

    public function testEmptyAndUnrelatedCollectionsNeverQuery(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('getRepository');
        $extras = $this->createMock(ExtraDataService::class);
        $extras->expects(self::never())->method('getExtraDataFromEntities');
        $hydrator = new CollectionReadBatchHydrator($manager, $extras);
        self::assertCount(0, $hydrator->prepare([], People::class));
        self::assertCount(0, $hydrator->prepare([new \stdClass()], \stdClass::class));
    }

    public function testProductPagePreloadsFileObjectsAndBatchMetadataWithoutChangingEntities(): void
    {
        $products = [new Product(), new Product()];
        $manager = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $manager->expects(self::once())->method('getRepository')->with(Product::class)->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects(self::exactly(2))->method('createQueryBuilder')->willReturn($qb);
        $qb->method('leftJoin')->willReturnSelf(); $qb->method('addSelect')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->expects(self::exactly(2))->method('setParameter')->with('batchPage', $products)->willReturnSelf();
        $query = $this->createMock(Query::class); $query->expects(self::exactly(2))->method('getResult')->willReturn($products);
        $qb->method('getQuery')->willReturn($query);
        $cache = new \WeakMap(); $cache[$products[0]] = [];
        $extras = $this->createMock(ExtraDataService::class);
        $extras->expects(self::once())->method('getExtraDataFromEntities')->with($products)->willReturn($cache);
        self::assertSame($cache, (new CollectionReadBatchHydrator($manager, $extras))->prepare($products, Product::class));
    }

    public function testProductListPreloadsFileMetadataButPreservesFullReadsForOtherGroups(): void
    {
        foreach ([['product:read'], ['product:read', 'file_item:read'], []] as $groups) {
            $products = [new Product()];
            $manager = $this->createMock(EntityManagerInterface::class);
            $repo = $this->createMock(EntityRepository::class);
            $manager->method('getRepository')->willReturn($repo);
            $qb = $this->createMock(QueryBuilder::class);
            $repo->method('createQueryBuilder')->willReturn($qb);
            $qb->method('leftJoin')->willReturnSelf();
            $selections = [];
            $qb->method('addSelect')->willReturnCallback(function ($value) use (&$selections, $qb) {
                $selections[] = $value; return $qb;
            });
            $qb->method('andWhere')->willReturnSelf(); $qb->method('setParameter')->willReturnSelf();
            $query = $this->createMock(Query::class); $query->method('getResult')->willReturn($products);
            $qb->method('getQuery')->willReturn($query);
            $extras = $this->createMock(ExtraDataService::class);
            $extras->method('getExtraDataFromEntities')->willReturn(new \WeakMap());
            (new CollectionReadBatchHydrator($manager, $extras))->prepare($products, Product::class, ['groups' => $groups]);
            $partial = 'partial batch_nested.{id,fileType,fileName,context,extension,public}';
            if ($groups === ['product:read']) {
                self::assertContains($partial, $selections);
                self::assertNotContains('batch_nested', $selections);
            } else {
                self::assertContains('batch_nested', $selections);
                self::assertNotContains($partial, $selections);
            }
        }
    }
}
