<?php

namespace App\Repository;

use ControleOnline\Entity\PeopleProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeopleProvider|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleProvider|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleProvider[]    findAll()
 * @method PeopleProvider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleProvider::class);
    }
}
