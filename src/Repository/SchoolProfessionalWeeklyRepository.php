<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Entity\SchoolTeamSchedule;
use App\Entity\SchoolProfessionalWeekly;
use App\Entity\Team;
use App\Entity\Contract;

/**
 * @method SchoolProfessionalWeekly|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchoolProfessionalWeekly|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchoolProfessionalWeekly[]    findAll()
 * @method SchoolProfessionalWeekly[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolProfessionalWeeklyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolProfessionalWeekly::class);
    }

    public function getAvailableProfessionals(string $weekDay, string $startTime, string $endTime): array
    {
        $queryBuilder = $this->createQueryBuilder('available_schedule');

        $queryBuilder->leftJoin(SchoolTeamSchedule::class, 'team_schedule', 'WITH',
            '
              team_schedule.peopleProfessional = available_schedule.peopleProfessional
              AND team_schedule.weekDay = :weekDay
              AND ((:startTime >= team_schedule.startTime AND :startTime <= team_schedule.endTime) OR :endTime <= team_schedule.endTime)
            '
        );

        $queryBuilder->leftJoin(Team::class    , 'team'    , 'WITH', 'team = team_schedule.team');
        $queryBuilder->leftJoin(Contract::class, 'contract', 'WITH', 'contract = team.contract');

        $queryBuilder->andWhere('available_schedule.weekDay = :weekDay');
        $queryBuilder->andWhere('(:startTime >= available_schedule.startTime AND :startTime <= available_schedule.endTime)');
        $queryBuilder->andWhere(':endTime <= available_schedule.endTime');
        $queryBuilder->andWhere('(team_schedule.id IS NULL OR (contract.id IS NOT NULL AND contract.contractStatus != :contractStatus))');

        $queryBuilder->setParameter('weekDay'       , $weekDay);
        $queryBuilder->setParameter('startTime'     , $startTime);
        $queryBuilder->setParameter('endTime'       , $endTime);
        $queryBuilder->setParameter('contractStatus', 'Active');

        return $queryBuilder->getQuery()->getResult();
    }
}
