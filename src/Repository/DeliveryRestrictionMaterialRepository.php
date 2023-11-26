<?php

namespace App\Repository;

use App\Entity\DeliveryRestrictionMaterial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeliveryRestrictionMaterial|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryRestrictionMaterial|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryRestrictionMaterial[]    findAll()
 * @method DeliveryRestrictionMaterial[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryRestrictionMaterialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryRestrictionMaterial::class);
    }

    // /**
    //  * @return DeliveryRestrictionMaterial[] Returns an array of DeliveryRestrictionMaterial objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DeliveryRestrictionMaterial
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
