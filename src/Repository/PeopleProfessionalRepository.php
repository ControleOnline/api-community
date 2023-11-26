<?php

namespace App\Repository;

use App\Entity\PeopleProfessional;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PeopleProfessional|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleProfessional|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleProfessional[]    findAll()
 * @method PeopleProfessional[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleProfessionalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleProfessional::class);
    }
}
