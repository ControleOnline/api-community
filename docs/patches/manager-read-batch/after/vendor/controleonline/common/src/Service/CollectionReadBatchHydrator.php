<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

/** Preloads existing response relationships, never chooses visibility or changes pagination. */
class CollectionReadBatchHydrator
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly ExtraDataService $extraDataService,
    ) {}

    /** @return \WeakMap<object, array> Serialization-local extra data, including empty results. */
    public function prepare(iterable $collection, string $resourceClass, array $context = []): \WeakMap
    {
        $cache = new \WeakMap();
        if (!in_array($resourceClass, [People::class, Product::class], true)
            || ($collection instanceof \Iterator && !$collection instanceof \ArrayIterator)) {
            return $cache;
        }
        $page = [];
        foreach ($collection as $entity) {
            if ($entity instanceof $resourceClass) {
                $page[] = $entity;
            }
        }
        if ($page === []) {
            return $cache;
        }

        // Roots came from the filtered, authorized page. Each separate fetch join has
        // only one to-many path, avoiding a Cartesian product or LIMIT over child rows.
        $paths = $resourceClass === People::class
            ? ['document', 'company_document', 'address', 'phone', 'email', 'productPeople' => 'product', 'peopleMedia']
            : ['productFiles' => 'file', 'productPeople'];
        $repository = $this->manager->getRepository($resourceClass);
        foreach (array_chunk($page, 100) as $batch) {
            foreach ($paths as $key => $path) {
                $relation = is_int($key) ? $path : $key;
                $qb = $repository->createQueryBuilder('batch_root')
                    ->leftJoin('batch_root.' . $relation, 'batch_relation')
                    ->addSelect('batch_relation')
                    ->andWhere('batch_root IN (:batchPage)')
                    ->setParameter('batchPage', $batch);
                if (!is_int($key)) {
                    // Product lists serialize file metadata; do not fetch binary content
                    // that belongs to the separate authenticated download operation.
                    $metadataOnly = $resourceClass === Product::class && $relation === 'productFiles'
                        && ($context['groups'] ?? []) === ['product:read'];
                    $qb->leftJoin('batch_relation.' . $path, 'batch_nested')->addSelect($metadataOnly
                        ? 'partial batch_nested.{id,fileType,fileName,context,extension,public}'
                        : 'batch_nested');
                }
                if ($resourceClass === People::class && $relation === 'address') {
                    // Only to-one descendants: the address batch still has one to-many path.
                    foreach ([
                        ['batch_relation.street', 'batch_address_street'],
                        ['batch_address_street.district', 'batch_address_district'],
                        ['batch_address_street.cep', 'batch_address_cep'],
                        ['batch_address_district.city', 'batch_address_city'],
                        ['batch_address_city.state', 'batch_address_state'],
                        ['batch_address_state.country', 'batch_address_country'],
                    ] as [$joinPath, $alias]) {
                        $qb->leftJoin($joinPath, $alias)->addSelect($alias);
                    }
                }
                $qb->getQuery()->getResult();
            }
        }

        return $resourceClass === Product::class
            ? $this->extraDataService->getExtraDataFromEntities($page)
            : $cache;
    }
}
