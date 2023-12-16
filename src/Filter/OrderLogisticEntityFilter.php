<?php

namespace App\Filter;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ControleOnline\Entity\Task;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use ControleOnline\Entity\People;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\ComissionInvoice;
use ControleOnline\Entity\Contract;
use ControleOnline\Entity\OrderLogistic;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class OrderLogisticEntityFilter extends AbstractFilter
{

    protected $requestStack;
    protected $params = [];


    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry,  $logger,  $properties,  $nameConverter);
        $this->requestStack = $requestStack;

        $request = $this->requestStack->getCurrentRequest();
        $this->params['searchBy'] = $request->query->get('searchBy');
        $this->params['pendings'] = $request->query->get('pendings');
    }    
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [])
    {
    }

    

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operationName = null, array $context = [])
    {
        $params = $this->getProperties();

        $alias = $queryBuilder->getRootAliases()[0];
        if ($resourceClass !== OrderLogistic::class) {
            return;
        }

    
        // $queryBuilder->Join($alias.'.order', 'order');
        // $queryBuilder->leftJoin('order.contract', 'contract');

        // // or filter

        // if ($this->params['searchBy']) {
        //     $queryBuilder->andWhere(
        //         $queryBuilder->expr()->orX(
        //             $queryBuilder->expr()->eq('contract.id', ':search_value'),
        //             $queryBuilder->expr()->like('order.otherInformations', ':search_like_car'),
        //             $queryBuilder->expr()->like('order.productType', ':search_like')
        //         )
        //     );
        //     $queryBuilder->setParameter(':search_value', $this->params['searchBy']);
        //     $queryBuilder->setParameter(':search_like_car', '%car:%' . $this->params['searchBy'] . '%');
        //     $queryBuilder->setParameter(':search_like', '%' . $this->params['searchBy'] . '%');
        // }
        // if ($this->params['pendings']) {
        //     $fieldsToCheck = [
        //         'estimatedShippingDate',
        //         'shippingDate',
        //         'estimatedArrivalDate',
        //         'arrivalDate',
        //         'originType',
        //         'originRegion',
        //         'originState',
        //         'originCity',
        //         'originAddress',
        //         'originLocator',
        //         'originState',
        //         'price',
        //         'amountPaid',
        //         'balance',
        //         'provider',
        //         'status',
        //         'destinationType',
        //         'destinationRegion',
        //         'destinationState',
        //         'destinationCity',
        //         'destinationAdress',
        //         'destinationProvider',
        //         'orderLogisticSurvey',
        //     ];
            
        //     if ($this->params['pendings'] == 'true') {
        //         $orX = $queryBuilder->expr()->orX();
        //         foreach ($fieldsToCheck as $field) {
        //             $orX->add($queryBuilder->expr()->isNull($alias.'.'.$field));
        //         }
        //         $queryBuilder->andWhere($orX);
        //     } else {
        //         $andX = $queryBuilder->expr()->andX();
        //         foreach ($fieldsToCheck as $field) {
        //             $andX->add($queryBuilder->expr()->isNotNull($alias.'.'.$field));
        //         }
        //         $queryBuilder->andWhere($andX);
        //     }
        // }
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
