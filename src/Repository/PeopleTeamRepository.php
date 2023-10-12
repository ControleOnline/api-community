<?php

namespace App\Repository;

use App\Entity\PeopleTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeopleTeam|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleTeam|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleTeam[]    findAll()
 * @method PeopleTeam[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleTeam::class);
    }

    // /**
    //  * @return PeopleTeam[] Returns an array of PeopleTeam objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PeopleTeam
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
