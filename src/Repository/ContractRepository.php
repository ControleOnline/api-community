<?php

namespace App\Repository;

use App\Entity\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    /**
     * @param $peopleId
     *
     * @return Contract[]
     *
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function getActiveContracts($peopleId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'SELECT c.*
            FROM contract c
            INNER JOIN contract_people cp on c.id = cp.contract_id and cp.people_id=:people_id
            WHERE c.contract_status="Active"
        ';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['people_id' => $peopleId]);

        return $stmt->fetchAll();
    }
}
