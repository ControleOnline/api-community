<?php

namespace App\Filter;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Task;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use App\Entity\People;
use ApiPlatform\Metadata\Operation;
use App\Entity\MyContract;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class MyContractEntityFilter extends AbstractFilter
{


    protected $requestStack;
    protected $params = [];

    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, NameConverterInterface $nameConverter = null, RequestStack $requestStack)
    {
        parent::__construct($managerRegistry,  $logger,  $nameConverter);
        $this->requestStack = $requestStack;

        $request = $this->requestStack->getCurrentRequest();
        $this->params['searchBy'] = $request->query->get('searchBy');
    }    
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [])
    {
    }

    
    
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operationName = null, array $context = [])
    {
        
        $params = $this->params;
        
        if ($resourceClass !== MyContract::class) {
            return;
        }
        
        if (($searchBy = $params['searchBy']) === null)
        return;
        
            $alias = $queryBuilder->getRootAliases()[0];
            
            $queryBuilder->leftJoin(
                sprintf('%s.contractPeople', $alias),
                'contract_people'
            );

        $queryBuilder->leftJoin('contract_people.people', 'people');

        // or filter

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(                
                $queryBuilder->expr()->eq(sprintf('%s', $alias), ':search_value'),
                $queryBuilder->expr()->like(sprintf('%s.startDate', $alias), ':search_like'),
                $queryBuilder->expr()->like(sprintf('%s.endDate'  , $alias), ':search_like'),
                $queryBuilder->expr()->like('people.name'         , ':search_like'),
                $queryBuilder->expr()->like('people.alias'        , ':search_like'),
            )
        );

        $queryBuilder->setParameter(':search_like', '%' . $searchBy . '%');
        $queryBuilder->setParameter(':search_value',  $searchBy );
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
