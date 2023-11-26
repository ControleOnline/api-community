<?php

namespace App\Repository;

use App\Entity\MyContract;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method MyContract|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyContract|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyContract[]    findAll()
 * @method MyContract[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyContract::class);
    }

    private function createContractsCollectionQuery(array $filters, string $type = 'rows'): string
    {
        $select = "
            c.id                               AS id,
            c.contract_model_id                AS contract_model_id,
            c.contract_status                  AS contract_status,
            c.start_date                       AS start_date,
            c.end_date                         AS end_date,
            c.creation_date                    AS creation_date,
            c.alter_date                       AS alter_date,
            c.contract_parent_id               AS contract_parent_id,
            c.html_content                     AS html_content,
            c.doc_key                          AS doc_key,
            GROUP_CONCAT(cpp.id SEPARATOR ',') AS contract_people,
            cp.people_type                     AS people_type,
            cpp.name                           AS people_name,
            cpp.alias                          AS people_alias
        ";

        if ($type === 'count') {
            $select = "
                COUNT(DISTINCT c.id) AS invoice_count
            ";
        }

        $query  = "
            SELECT
                " . $select . "
            FROM contract c
                LEFT JOIN contract_people cp ON 
                    cp.contract_id = c.id
                LEFT JOIN people cpp ON
                    cpp.id = cp.people_id

        ";

        if (!empty($filters)) {
            $criteria = [];

            // searchBy

            if (isset($filters['searchBy'])) {
                $searchBy  = '(';

                if (is_numeric($filters['searchBy'])) {
                    $searchBy .= sprintf(' c.id = %d', ((float) $filters['searchBy']));
                    $searchBy .= sprintf(' OR c.contract_parent_id = %d', ((float) $filters['searchBy']));
                }
                else {
                    $searchBy = ' (CONCAT(cpp.name, " ", cpp.alias) LIKE :searchBy)';
                }

                $searchBy .= ')';

                $criteria[] = [
                    'operator'  => 'AND',
                    'condition' => $searchBy,
                    'added'     => false
                ];
            }

            // contract status 

            if (isset($filters['status'])) {
                $criteria[] = [
                    'operator'  => 'AND',
                    'condition' => 'c.contract_status = :status',
                    'added'     => false
                ];
            }

            // provider 

            if (isset($filters['providerId'])) {
                $criteria[] = [
                    'operator'  => 'AND',
                    'condition' => 'cp.people_id = :providerId AND cp.people_type = "Provider"',
                    'added'     => false
                ];
            }
        }

        if ($type === 'count') {
          return $query;
        }

        return $query . ' GROUP BY c.id, cpp.id';
    }
    
    public function getContractsCollectionCount(array $filters = []): int
    {
        // build query string

        $query = $this->createContractsCollectionQuery($filters, 'count');

        // mapping

    
        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('invoice_count', 'invoice_count', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($filters as $name => $value) {
            if (!empty($value)) {
                $_value = $value;

                if ($name === 'searchBy') {
                    $_value = is_numeric($_value) ? (float) $_value : '%' . $_value . '%';
                }

                $nqu->setParameter($name, $_value);
            }
        }

        $count = $nqu->getArrayResult();

        return isset($count[0]) ? $count[0]['invoice_count'] : 0;
    }

    public function getContractsCollection(array $filters = [], int $from = 0, int $limit = 10): array
    {
        // build query string

        $query  = $this->createContractsCollectionQuery($filters, 'rows');
        $query .= '
            ORDER BY c.alter_date DESC
            LIMIT :from, :limit
        ';

        // mapping

        $rsm = new ResultSetMapping();
        
        $rsm->addScalarResult('id'                 , 'id');
        $rsm->addScalarResult('contract_model_id'  , 'contract_model_id');
        $rsm->addScalarResult('contract_status'    , 'contract_status');
        $rsm->addScalarResult('start_date'         , 'start_date');
        $rsm->addScalarResult('end_date'           , 'end_date');
        $rsm->addScalarResult('creation_date'      , 'creation_date');
        $rsm->addScalarResult('alter_date'         , 'alter_date');
        $rsm->addScalarResult('contract_parent_id' , 'contract_parent_id');
        $rsm->addScalarResult('html_content'       , 'html_content');
        $rsm->addScalarResult('doc_key'            , 'doc_key');
        $rsm->addScalarResult('contract_people'    , 'contract_people');
        $rsm->addScalarResult('people_type'        , 'people_type');
        $rsm->addScalarResult('people_name'        , 'people_name');
        $rsm->addScalarResult('people_alias'       , 'people_alias');

        $nqu = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($filters as $name => $value) {
            if (!empty($value)) {
                $_value = $value;

                if ($name === 'searchBy') {
                    $_value = is_numeric($_value) ? $_value : $_value . '%';
                }

                $nqu->setParameter($name, $_value);
            }
        }
        $nqu->setParameter('from' , $from);
        $nqu->setParameter('limit', $limit);

        // adjust result

        $output = [];
        
        foreach ($nqu->getArrayResult() as $contract) {
          $output[$contract['id']] = [
              'id'                 => $contract['id'],
              'contract_model_id'  => $contract['contract_model_id'],
              'contract_status'    => $contract['contract_status'],
              'start_date'         => $contract['start_date'],
              'end_date'           => $contract['end_date'],
              'creation_date'      => $contract['creation_date'],
              'alter_date'         => $contract['alter_date'],
              'contract_parent_id' => $contract['contract_parent_id'],
              'html_content'       => $contract['html_content'],
              'doc_key'            => $contract['doc_key'],
              'contract_people'    => []
          ];
          
          if (!empty($contract['contract_people'])) {
            $contractPeople = explode(',', $contract['contract_people']);

            foreach ($contractPeople as $peopleId) {
              $output[$contract['id']]['contract_people'][] = [
                  'id'           => $peopleId,
                  'people_type'  => $contract['people_type'],
                  'people_name'  => $contract['people_name'],
                  'people_alias' => $contract['people_alias']
              ];
            }
          }
        }

        return $output;
    }
}
