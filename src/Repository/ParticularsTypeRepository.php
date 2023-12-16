<?php

namespace App\Repository;

use ControleOnline\Entity\ParticularsType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ParticularsType|null find($id, $lockMode = null, $lockVersion = null)
 * @method ParticularsType|null findOneBy(array $criteria, array $orderBy = null)
 * @method ParticularsType[]    findAll()
 * @method ParticularsType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticularsTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticularsType::class);
    }
}
