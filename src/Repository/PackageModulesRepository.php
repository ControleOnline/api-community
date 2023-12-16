<?php

namespace App\Repository;

use ControleOnline\Entity\PackageModules;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PackageModules|null find($id, $lockMode = null, $lockVersion = null)
 * @method PackageModules|null findOneBy(array $criteria, array $orderBy = null)
 * @method PackageModules[]    findAll()
 * @method PackageModules[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PackageModulesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackageModules::class);
    }
}
