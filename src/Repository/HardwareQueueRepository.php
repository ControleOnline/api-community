<?php

namespace App\Repository;

use App\Entity\HardwareQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method HardwareQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method HardwareQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method HardwareQueue[]    findAll()
 * @method HardwareQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HardwareQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HardwareQueue::class);
    }
}
