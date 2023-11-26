<?php

namespace App\Filter;


use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
#use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface;
use App\Entity\Task;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use App\Entity\People;
use ApiPlatform\Metadata\Operation;
use App\Entity\SalesOrder as Order;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class SalesOrderEntityFilter extends AbstractFilter  implements ContextAwareFilterInterface
{

    protected $requestStack;
    protected $params = [];

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack)
    {
        parent::__construct($managerRegistry, $requestStack);
        $this->requestStack = $requestStack;

        $request = $this->requestStack->getCurrentRequest();
        $this->params['searchBy'] = $request->query->get('searchBy');

    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null/* , array $context = [] */)
    {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?string $operationName = null, array $context = [])
    {


        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.orderType IN(\'purchase\',\'sale\')', $alias));


        $fromDate = $this->params['fromDate'];
        if ($fromDate) {
            $queryBuilder->andWhere(sprintf('%s.orderDate >= :fromDate', $alias));
            $queryBuilder->setParameter(':fromDate', $fromDate);
        }

        $toDate = $this->params['toDate'];
        if ($toDate) {
            $queryBuilder->andWhere(sprintf('%s.orderDate <= :toDate', $alias));
            $queryBuilder->setParameter(':toDate', $toDate . ' 23:59:59');
        }


        $orderType = $this->params['orderType'];
        if ($orderType) {
            $queryBuilder->andWhere(sprintf('%s.orderType IN (:orderType)', $alias));
            $queryBuilder->setParameter(':orderType', $orderType);
        }


        if (($param = $this->params['searchBy']) === null)
            return;


        // inner invoice_tax

        $queryBuilder->leftJoin(
            sprintf('%s.invoiceTax', $alias),
            'ordtax'
        );
        $queryBuilder->leftJoin('ordtax.invoiceTax', 'invtax');

        // inner address_origin

        $queryBuilder->leftJoin(
            sprintf('%s.addressOrigin', $alias),
            'addori'
        );
        $queryBuilder->leftJoin('addori.street', 'strori');
        $queryBuilder->leftJoin('strori.district', 'disori');
        $queryBuilder->leftJoin('disori.city', 'citori');
        $queryBuilder->leftJoin('citori.state', 'staori');

        // inner retrieve people

        $queryBuilder->leftJoin(
            sprintf('%s.retrievePeople', $alias),
            'retpep'
        );

        // inner delivery people

        $queryBuilder->leftJoin(
            sprintf('%s.deliveryPeople', $alias),
            'delpep'
        );

        // inner quote

        $queryBuilder->leftJoin(
            sprintf('%s.quote', $alias),
            'quote'
        );
        $queryBuilder->leftJoin('quote.carrier', 'carrie');



        // or filter

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq(sprintf('%s', $alias), ':search_value'),
                $queryBuilder->expr()->eq('invtax.invoiceNumber', ':search_value'),
                $queryBuilder->expr()->eq('citori.city', ':search_value'),
                $queryBuilder->expr()->eq('staori.uf', ':search_value'),
                $queryBuilder->expr()->like('retpep.name', ':search_like'),
                $queryBuilder->expr()->like('retpep.alias', ':search_like'),
                $queryBuilder->expr()->like('delpep.name', ':search_like'),
                $queryBuilder->expr()->like('delpep.alias', ':search_like'),
                $queryBuilder->expr()->like('carrie.name', ':search_like'),
                $queryBuilder->expr()->like('carrie.alias', ':search_like')
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
