<?php

namespace App\Repository;

use App\Entity\ProductOld;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductOld|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductOld|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductOld[]    findAll()
 * @method ProductOld[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductOldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductOld::class);
    }
}
