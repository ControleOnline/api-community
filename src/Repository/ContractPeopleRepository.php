<?php

namespace App\Repository;

use ControleOnline\Entity\ContractPeople;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ContractPeople|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContractPeople|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContractPeople[]    findAll()
 * @method ContractPeople[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContractPeopleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractPeople::class);
    }

    /*
    public function findOneBySomeField($value): ?ContractPeople
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
