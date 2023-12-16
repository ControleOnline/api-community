<?php

namespace App\Repository;

use ControleOnline\Entity\LessonCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LessonCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method LessonCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method LessonCategory[]    findAll()
 * @method LessonCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonCategory::class);
    }

    // /**
    //  * @return LessonCategory[] Returns an array of LessonCategory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LessonCategory
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
