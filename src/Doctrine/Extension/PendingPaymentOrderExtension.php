<?php

declare(strict_types=1);
namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\Order;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/** Read-only operational view; never changes financial or production status. */
final class PendingPaymentOrderExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly RequestStack $requests) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== Order::class || $this->requests->getCurrentRequest()?->query->get('pendingPaymentOnly') !== '1') return;
        $root = $queryBuilder->getRootAliases()[0];
        $status = $queryNameGenerator->generateJoinAlias('pending_payment_status');
        $parameter = $queryNameGenerator->generateParameterName('paid_status_names');
        $queryBuilder->innerJoin($root.'.status', $status)
            ->andWhere('LOWER('.$status.'.status) NOT IN (:'.$parameter.')')
            ->setParameter($parameter, ['paid', 'pago', 'settled', 'liquidado']);
    }
}
