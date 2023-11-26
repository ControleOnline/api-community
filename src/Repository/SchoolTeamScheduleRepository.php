<?php

namespace App\Repository;

use App\Entity\SchoolTeamSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SchoolTeamSchedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchoolTeamSchedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchoolTeamSchedule[]    findAll()
 * @method SchoolTeamSchedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolTeamScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolTeamSchedule::class);
    }
}
