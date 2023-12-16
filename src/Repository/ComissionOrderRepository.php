<?php

namespace App\Repository;

use ControleOnline\Entity\ComissionOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ComissionOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComissionOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComissionOrder[]    findAll()
 * @method ComissionOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComissionOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComissionOrder::class);
    }
}
