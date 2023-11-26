<?php

namespace App\Filter;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Task;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use App\Entity\People;
use ApiPlatform\Metadata\Operation;
use App\Entity\ComissionInvoice;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class ComissionInvoiceEntityFilter extends AbstractFilter
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
        if ($resourceClass !== ComissionInvoice::class) {
            return;
        }
        $queryBuilder->andWhere(sprintf('%s.orderType = \'comission\'', $alias));
        if (($param = $params['searchBy']) === null)
            return;



        // inner order

        $queryBuilder->leftJoin(
            sprintf('%s.order', $alias),
            'invord'
        );
        $queryBuilder->leftJoin('invord.order', 'order');
        $queryBuilder->leftJoin('order.client', 'client');
        $queryBuilder->leftJoin('order.invoiceTax', 'ordtax');
        $queryBuilder->leftJoin('ordtax.invoiceTax', 'invtax');

        // or filter

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq(
                    sprintf('%s.id', $alias),
                    ':search_value'
                ),
                $queryBuilder->expr()->eq('invtax.invoiceNumber', ':search_value'),
                $queryBuilder->expr()->like('client.name', ':search_like'),
                $queryBuilder->expr()->like('client.alias', ':search_like')
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
