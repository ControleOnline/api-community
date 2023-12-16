<?php

namespace App\Repository;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleSalesman;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleSalesmanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleSalesman::class);
    }

    public function getMySalesman(People $company): array
    {
        $sql = "
        SELECT
            peo.id,
            peo.name,
            peo.alias,
            peo.people_type AS type,
            psa.enable,
            GROUP_CONCAT(IF(ema.email IS NULL, emi.email, ema.email) SEPARATOR ',')  AS email,
            doc.document
        FROM people peo
            INNER JOIN people_salesman psa ON psa.salesman_id = peo.id
            LEFT JOIN email            ema ON ema.people_id = psa.salesman_id
            LEFT JOIN people_employee  pem ON pem.id = (SELECT id FROM people_employee WHERE company_id = psa.salesman_id LIMIT 1)
            LEFT JOIN email            emi ON emi.people_id = pem.employee_id
            LEFT JOIN document         doc ON doc.id = (SELECT id FROM document WHERE people_id = psa.salesman_id LIMIT 1)
        WHERE
            psa.company_id = :company_id
        GROUP BY
            peo.id, doc.document
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id'         , 'id'         , 'integer');
        $rsm->addScalarResult('type'       , 'type'       , 'string');
        $rsm->addScalarResult('document'   , 'document'   , 'string');
        $rsm->addScalarResult('name'       , 'name'       , 'string');
        $rsm->addScalarResult('alias'      , 'alias'      , 'string');
        $rsm->addScalarResult('email'      , 'email'      , 'string');
        $rsm->addScalarResult('is_provider', 'is_provider', 'boolean');
        $rsm->addScalarResult('enable'     , 'enable'    , 'boolean');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('company_id', $company->getId());

        return $nqu->getArrayResult();
    }

    public function getMySaleCompanies(People $people): array
    {
        $sql = "
            SELECT DISTINCT
                peo.id                  AS people_id,
                peo.alias               AS people_alias,
                ima.id                  AS file_id,
                '".$_SERVER['HTTP_HOST'].'/files/download/'."' AS file_domain,
                ima.id                 AS file_url,
                (
                    SELECT document FROM document WHERE people_id = peo.id and document_type_id = 3
                ) AS people_document,
                psa.commission,
                psa.enable
            FROM people_salesman psa
                INNER JOIN people_employee pem ON pem.company_id = psa.salesman_id and pem.enable = 1
                INNER JOIN people          peo ON peo.id = psa.company_id
                LEFT  JOIN files           ima ON ima.id = peo.image_id
            WHERE
                pem.employee_id = :people_id 
      ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('people_id'      , 'people_id', 'integer');
        $rsm->addScalarResult('people_alias'   , 'people_alias');
        $rsm->addScalarResult('image_id'       , 'file_id');
        $rsm->addScalarResult('image_domain'   , 'file_domain');
        $rsm->addScalarResult('image_url'      , 'file_url');
        $rsm->addScalarResult('people_document', 'people_document');
        $rsm->addScalarResult('commission'     , 'commission', 'float');
        $rsm->addScalarResult('enable'         , 'enable'   , 'boolean');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('people_id', $people->getId());

        return $nqu->getArrayResult();
    }

    public function companyIsMyProvider(People $people, People $company): bool
    {
        $sql = "
            SELECT
                psa.company_id AS provider
            FROM people_salesman psa
                INNER JOIN people_employee pem ON pem.company_id = psa.salesman_id
            WHERE
                pem.employee_id    = :people_id
                AND psa.company_id = :company_id
                AND psa.enable     = 1
            LIMIT 1
      ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('provider', 'provider', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('people_id' , $people->getId() );
        $nqu->setParameter('company_id', $company->getId());

        return empty($nqu->getArrayResult()) === false;
    }

    /**
     * This query does not include providers as salesman
     */
    public function retrieveSalesmanById(string $id): ?array
    {
        $sql = "
            SELECT
                peo.id,
                peo.name,
                peo.alias,
                peo.people_type AS type
            FROM people peo
                INNER JOIN people_salesman psa ON psa.salesman_id = peo.id AND peo.id NOT IN (SELECT DISTINCT company_id FROM people_salesman)
                INNER JOIN document        doc ON doc.people_id = psa.salesman_id
            WHERE
                doc.document = :id
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id'   , 'id'   , 'integer');
        $rsm->addScalarResult('name' , 'name' , 'string');
        $rsm->addScalarResult('alias', 'alias', 'string');
        $rsm->addScalarResult('type' , 'type' , 'string');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('id', $id);

        $result = $nqu->getArrayResult();

        return empty($result) ? null : $result[0];
    }

    /**
     * This query does not include providers as salesman
     */
    public function customerHasSalesman(People $customer): bool
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql  = '
            SELECT DISTINCT
                ps.salesman_id
            FROM people_client pc
                INNER JOIN people_salesman ps ON ps.salesman_id = pc.company_id AND pc.company_id NOT IN (SELECT DISTINCT company_id FROM people_salesman)
            WHERE
               pc.client_id = :customer_id
        ';

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            'customer_id' => $customer->getId()
        ]);

        return empty($stmt->fetch()) === false;
    }
}
