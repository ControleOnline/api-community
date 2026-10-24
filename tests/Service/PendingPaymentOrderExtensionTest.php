<?php

declare(strict_types=1);
use App\Doctrine\Extension\PendingPaymentOrderExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class PendingPaymentOrderExtensionTest extends TestCase
{
    public function testExcludesOnlyPaidStatusesWhenExplicitlyRequested(): void
    {
        $requests = new RequestStack();$requests->push(new Request(['pendingPaymentOnly'=>'1']));
        $qb = new QueryBuilder($this->createStub(EntityManagerInterface::class));$qb->select('o')->from(Order::class,'o')->andWhere('o.provider = :company')->setParameter('company',3);
        (new PendingPaymentOrderExtension($requests))->applyToCollection($qb,new QueryNameGenerator(),Order::class);
        self::assertStringContainsString('o.provider = :company',$qb->getDQL());
        self::assertStringContainsString('NOT IN',$qb->getDQL());
        self::assertCount(2,$qb->getParameters());
        self::assertSame(['paid','pago','settled','liquidado'],$qb->getParameters()->last()->getValue());
    }
    public function testDefaultHistoryAndOtherResourcesRemainUnchanged(): void
    {
        foreach ([[[],Order::class],[['pendingPaymentOnly'=>'1'],People::class]] as [$query,$resource]) {
            $requests=new RequestStack();$requests->push(new Request($query));
            $qb=new QueryBuilder($this->createStub(EntityManagerInterface::class));$qb->select('o')->from($resource,'o');$before=$qb->getDQL();
            (new PendingPaymentOrderExtension($requests))->applyToCollection($qb,new QueryNameGenerator(),$resource);
            self::assertSame($before,$qb->getDQL());
        }
    }
}
