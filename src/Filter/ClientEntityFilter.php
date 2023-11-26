<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Task;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use App\Entity\People;
use ApiPlatform\Metadata\Operation;
use App\Entity\Client;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class ClientEntityFilter extends AbstractFilter
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


        if ($resourceClass !== Client::class) {
            return;
        }

        if (($searchBy = $params['searchBy']) === null)
            return;

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->leftJoin(
            sprintf('%s.document', $alias),
            'client_document'
        );
        $queryBuilder->leftJoin(
            sprintf('%s.email', $alias),
            'client_email'
        );
        $queryBuilder->leftJoin(
            sprintf('%s.phone', $alias),
            'client_phone'
        );

        // or filter

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->like(sprintf('%s.name', $alias), ':search_like'),
                $queryBuilder->expr()->like(sprintf('%s.alias', $alias), ':search_like'),
                $queryBuilder->expr()->like('client_document.document', ':search_like'),
                $queryBuilder->expr()->like('client_email.email', ':search_like'),
                $queryBuilder->expr()->like('client_phone.phone', ':search_like'),

            )
        );

        $queryBuilder->setParameter(':search_like', '%' . $searchBy . '%');
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
