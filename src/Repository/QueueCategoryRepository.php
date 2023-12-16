<?php

namespace App\Repository;

use ControleOnline\Entity\QueueCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method QueueCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method QueueCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method QueueCategory[]    findAll()
 * @method QueueCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueueCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueueCategory::class);
    }
}
