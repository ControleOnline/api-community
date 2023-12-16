<?php

namespace App\Repository;

use ControleOnline\Entity\PeoplePackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeoplePackage|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeoplePackage|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeoplePackage[]    findAll()
 * @method PeoplePackage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeoplePackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeoplePackage::class);
    }
}
