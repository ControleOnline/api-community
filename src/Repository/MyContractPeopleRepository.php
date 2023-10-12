<?php

namespace App\Repository;

use App\Entity\MyContractPeople;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyContractPeople|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyContractPeople|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyContractPeople[]    findAll()
 * @method MyContractPeople[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyContractPeopleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyContractPeople::class);
    }
}
