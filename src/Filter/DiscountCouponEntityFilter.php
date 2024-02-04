<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ControleOnline\Entity\Task;

use ControleOnline\Entity\People;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use ControleOnline\Entity\DiscountCoupon;

class DiscountCouponEntityFilter extends AbstractFilter
{
   



    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry,  $logger,  $properties,  $nameConverter);
    }    
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [])
    {
    }

    

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operationName = null, array $context = [])
    {
        $params = $this->getProperties();

        $alias = $queryBuilder->getRootAliases()[0];
        if ($resourceClass !== DiscountCoupon::class) {
            return;
        }



        $myCompany = $params['myCompany'];
        if ($myCompany) {
            $queryBuilder->andWhere(sprintf('%s.company = :company OR %s.company IS NULL', $alias, $alias));
            $queryBuilder->setParameter(':company', $myCompany);
        }

        $discountStartDate = $params['fromDate'];
        if ($discountStartDate) {
            $queryBuilder->andWhere(sprintf('%s.discountStartDate >= :discountStartDate', $alias));
            $queryBuilder->setParameter(':discountStartDate', $discountStartDate);
        }

        $discountEndDate = $params['toDate'];
        if ($discountEndDate) {
            $queryBuilder->andWhere(sprintf('%s.discountEndDate <= :discountEndDate', $alias));
            $queryBuilder->setParameter(':discountEndDate', $discountEndDate . ' 23:59:59');
        }

        $status = $params['status'];
        $queryBuilder->leftJoin(sprintf('%s.order', $alias), 'orders');

        if ($status  == 'open') {
            $queryBuilder->andWhere(sprintf('orders.id IS NULL'));
        } elseif ($status  == 'closed') {
            $queryBuilder->andWhere(sprintf('orders.id IS NOT NULL'));
        } elseif ($status  == 'expired') {
            $queryBuilder->andWhere(sprintf('%s.discountEndDate < :discountEndDate', $alias));
            $queryBuilder->setParameter(':discountEndDate', date('Y-m-d 00:00:00'));
        } elseif ($status  == 'not_expired') {
            $queryBuilder->andWhere(sprintf('%s.discountEndDate >= :discountEndDate', $alias));
            $queryBuilder->setParameter(':discountEndDate', date('Y-m-d 00:00:00'));
        }


        if (($param = $params['searchBy']) === null)
            return;

        $queryBuilder->leftJoin(sprintf('%s.client', $alias),'people');
        $queryBuilder->leftJoin('orders.client','client');
        // or filter
        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq(sprintf('%s', $alias), ':search_value'),
                $queryBuilder->expr()->like('client.name', ':search_like'),
                $queryBuilder->expr()->like('client.alias', ':search_like'),
                $queryBuilder->expr()->like('people.name', ':search_like'),
                $queryBuilder->expr()->like('people.alias', ':search_like'),
            )
        );

        $queryBuilder->setParameter(':search_value', $param);
        $queryBuilder->setParameter(':search_like', '%' . $param . '%');
    }

    /**
     * @param string $resourceClass
     * @return array
     */
    public function getDescription(string $resourceClass): array
    {
        return [
            'searchBy' => [
                'property' => null,
                'type'     => 'string',
                'required' => false,
                'swagger'  => ['description' => 'Example: foo'],
            ],
        ];
    }


}
