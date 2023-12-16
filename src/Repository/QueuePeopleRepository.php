<?php

namespace App\Repository;

use ControleOnline\Entity\QueuePeople;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method QueuePeople|null find($id, $lockMode = null, $lockVersion = null)
 * @method QueuePeople|null findOneBy(array $criteria, array $orderBy = null)
 * @method QueuePeople[]    findAll()
 * @method QueuePeople[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueuePeopleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueuePeople::class);
    }
}
