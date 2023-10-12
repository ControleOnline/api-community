<?php

namespace App\Repository;

use App\Entity\PeopleCarrier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeopleCarrier|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleCarrier|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleCarrier[]    findAll()
 * @method PeopleCarrier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleCarrierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleCarrier::class);
    }
}
