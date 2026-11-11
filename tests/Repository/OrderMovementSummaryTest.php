<?php
namespace App\Tests\Repository;
use ControleOnline\Entity\Order;
use ControleOnline\Repository\OrderRepository;
use ControleOnline\Service\PeopleService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
#[AllowMockObjectsWithoutExpectations]
class OrderMovementSummaryTest extends TestCase {
 public function testCountsFilteredOrdersWithoutSalesStatusOrProductJoins(): void {
  $filtered=$this->createMock(QueryBuilder::class);
  $filtered->method('getDQL')->willReturn('SELECT DISTINCT scope.id FROM orders scope WHERE scope.provider = :provider');
  $filtered->method('getParameters')->willReturn(new ArrayCollection());
  $rows=[['orderId'=>1,'orderDate'=>new \DateTimeImmutable('2026-06-01')],['orderId'=>2,'orderDate'=>'2026-06-01'],['orderId'=>3,'orderDate'=>'2026-07-02'],['orderId'=>4,'orderDate'=>null]];
  $query=$this->createMock(Query::class);$query->method('getArrayResult')->willReturn($rows);
  $qb=$this->createMock(QueryBuilder::class);
  foreach(['select','from','setParameter'] as $method)$qb->method($method)->willReturnSelf();
  $qb->expects(self::once())->method('andWhere')->with('movement_order.id IN (SELECT DISTINCT scope.id FROM orders scope WHERE scope.provider = :provider)')->willReturnSelf();
  $qb->expects(self::never())->method('join');$qb->expects(self::never())->method('setMaxResults');
  $qb->method('getQuery')->willReturn($query);
  $em=$this->createMock(EntityManagerInterface::class);$em->method('createQueryBuilder')->willReturn($qb);$em->method('getClassMetadata')->with(Order::class)->willReturn(new ClassMetadata(Order::class));
  $registry=$this->createMock(ManagerRegistry::class);$registry->method('getManagerForClass')->with(Order::class)->willReturn($em);$registry->method('getManager')->willReturn($em);
  $repo=new OrderRepository($this->createMock(PeopleService::class),$registry);
  $result=(new \ControleOnline\Service\OrderDashboardSummary($repo))->resolveMovementSummary($filtered);
  self::assertSame(['orders'=>4,'undatedOrders'=>1],$result['totals']);
  self::assertSame([['key'=>'2026-06-01','label'=>'01/06','orders'=>2],['key'=>'2026-07-02','label'=>'02/07','orders'=>1]],$result['daily']);
  self::assertSame([['key'=>'2026-06','label'=>'06/2026','orders'=>2],['key'=>'2026-07','label'=>'07/2026','orders'=>1]],$result['monthly']);
 }
 public function testPaidValueKeepsScopeAndAggregatesOrdersWithoutProductOrInvoiceDuplication(): void {
  $filtered=$this->createMock(QueryBuilder::class);
  $filtered->method('getDQL')->willReturn('SELECT DISTINCT scope.id FROM orders scope WHERE scope.provider = :provider');
  $filtered->method('getParameters')->willReturn(new ArrayCollection());
  $query=$this->createMock(Query::class);$query->method('getSingleResult')->willReturn(['orders'=>'370','amount'=>'16095.00','unpricedOrders'=>'0']);
  $qb=$this->createMock(QueryBuilder::class);
  foreach(['select','from'] as $method)$qb->method($method)->willReturnSelf();
  $qb->expects(self::once())->method('join')->with('paid_order.status','paid_status')->willReturnSelf();
  $where=[];$qb->expects(self::exactly(3))->method('andWhere')->willReturnCallback(function($condition)use(&$where,$qb){$where[]=$condition;return $qb;});
  $parameters=[];$qb->method('setParameter')->willReturnCallback(function($key,$value)use(&$parameters,$qb){$parameters[$key]=$value;return $qb;});
  $qb->expects(self::never())->method('setMaxResults');$qb->method('getQuery')->willReturn($query);
  $em=$this->createMock(EntityManagerInterface::class);$em->method('createQueryBuilder')->willReturn($qb);$em->method('getClassMetadata')->with(Order::class)->willReturn(new ClassMetadata(Order::class));
  $registry=$this->createMock(ManagerRegistry::class);$registry->method('getManagerForClass')->with(Order::class)->willReturn($em);$registry->method('getManager')->willReturn($em);
  $repo=new OrderRepository($this->createMock(PeopleService::class),$registry);
  $result=(new \ControleOnline\Service\OrderDashboardSummary($repo))->resolvePaidOrdersSummary($filtered);
  self::assertSame(['orders'=>370,'amount'=>16095.0,'unpricedOrders'=>0],$result['totals']);
  self::assertSame('order-price',$result['basis']);
  self::assertContains('paid_order.id IN (SELECT DISTINCT scope.id FROM orders scope WHERE scope.provider = :provider)',$where);
  self::assertSame(['valuePaidStatus'=>'paid','valuePaidRealStatus'=>'open'],$parameters);
 }

}
