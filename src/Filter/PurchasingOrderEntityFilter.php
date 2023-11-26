<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Task;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use App\Entity\People;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\PurchasingOrder AS Order;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class PurchasingOrderEntityFilter extends AbstractFilter
{
    
    private $requestStack;


    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, RequestStack $requestStack, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry,  $logger,  $properties,  $nameConverter, $requestStack);
        $this->requestStack = $requestStack;
    }    
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [])
    {
    }

    

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operationName = null, array $context = [])
    {
        
        $params = $this->requestStack->getCurrentRequest()->query->get('searchBy',null);
        
        $alias = $queryBuilder->getRootAliases()[0];
        if ($resourceClass !== Order::class) {
            return;
        }
        
        //$queryBuilder->andWhere(sprintf('%s.orderType IN(\'purchase\',\'sale\',\'comission\',\'royalties\')', $alias));        

        if (($param = $params) === null)
        return;
        
        
            
        // inner invoice_tax

        $queryBuilder->leftJoin(
            sprintf('%s.invoiceTax', $alias), 'ordtax'
        );
        $queryBuilder->leftJoin('ordtax.invoiceTax', 'invtax');

        // inner address_origin
        $queryBuilder->leftJoin(
            sprintf('%s.addressOrigin', $alias), 'addori'
        );
        $queryBuilder->leftJoin('addori.street'  , 'strori');
        $queryBuilder->leftJoin('strori.district', 'disori');
        $queryBuilder->leftJoin('disori.city'    , 'citori');
        $queryBuilder->leftJoin('citori.state'   , 'staori');

        // inner retrieve people

        $queryBuilder->leftJoin(
            sprintf('%s.retrievePeople', $alias), 'retpep'
        );

        // inner delivery people

        $queryBuilder->leftJoin(
            sprintf('%s.deliveryPeople', $alias), 'delpep'
        );

        // inner quote

        $queryBuilder->leftJoin(
            sprintf('%s.quote', $alias), 'quote'
        );
        $queryBuilder->leftJoin('quote.carrier', 'carrie');

        // or filter

        $queryBuilder->andWhere    (
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq  ('invtax.invoiceNumber', ':search_value'),
                $queryBuilder->expr()->eq  ('citori.city'         , ':search_value'),
                $queryBuilder->expr()->eq  ('staori.uf'           , ':search_value'),
                $queryBuilder->expr()->like('retpep.name'         , ':search_like' ),
                $queryBuilder->expr()->like('retpep.alias'        , ':search_like' ),
                $queryBuilder->expr()->like('delpep.name'         , ':search_like' ),
                $queryBuilder->expr()->like('delpep.alias'        , ':search_like' ),
                $queryBuilder->expr()->like('carrie.name'         , ':search_like' ),
                $queryBuilder->expr()->like('carrie.alias'        , ':search_like' )
            )
        );

        $queryBuilder->setParameter(':search_value', $param);
        $queryBuilder->setParameter(':search_like' , '%' . $param . '%');
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
