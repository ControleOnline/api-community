<?php

namespace App\Repository;

use ControleOnline\Entity\SchoolClassStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SchoolClassStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchoolClassStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchoolClassStatus[]    findAll()
 * @method SchoolClassStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolClassStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolClassStatus::class);
    }

    // /**
    //  * @return SchoolClassStatus[] Returns an array of SchoolClassStatus objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SchoolClassStatus
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
