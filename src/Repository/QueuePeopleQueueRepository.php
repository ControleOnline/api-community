<?php

namespace App\Repository;

use App\Entity\QueuePeopleQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method QueuePeopleQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method QueuePeopleQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method QueuePeopleQueue[]    findAll()
 * @method QueuePeopleQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueuePeopleQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueuePeopleQueue::class);
    }
}
