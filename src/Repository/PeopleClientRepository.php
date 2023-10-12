<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\People;
use App\Entity\PeopleClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method PeopleClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeopleClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeopleClient[]    findAll()
 * @method PeopleClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeopleClient::class);
    }

    public function peopleIsMyClient(People $myPeople, People $client): bool
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql  = '
            SELECT
                PC.client_id
            FROM people_client PC     
            LEFT JOIN people_employee PE ON (PE.employee_id = PC.client_id)                       
            WHERE
                PC.client_id = :client OR PE.company_id = :client
            LIMIT 1
        ';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'client'   => $client->getId()
        ]);

        if (empty($stmt->fetch()) === true) {
            $peopleClient = new PeopleClient();
            $peopleClient->setCompanyId($myPeople->getPeopleCompany()->first()->getCompany()->getId());
            $peopleClient->setClient($client);
            $peopleClient->setEnabled(true);
            $this->getEntityManager()->persist($peopleClient);
            $this->getEntityManager()->flush($peopleClient);
        }

        $sql  = '
            SELECT
                PC.client_id
            FROM people_client PC                
            LEFT JOIN people_employee PE ON (PE.employee_id = PC.client_id)
            WHERE
                ( PC.client_id =? AND PC.company_id IN (?)) OR               
                ( PE.company_id =? AND  PC.company_id IN (?))
            LIMIT 1
        ';

        $stmt = $conn->prepare($sql);
        $myCompanies = [];
        foreach ($myPeople->getPeopleCompany() as $conpanies) {
            $myCompanies[] = $conpanies->getCompany()->getId();
        }

        if (empty($myCompanies))
            return false;


        $types = [\PDO::PARAM_INT,$conn::PARAM_INT_ARRAY, \PDO::PARAM_INT,$conn::PARAM_INT_ARRAY];
        $stmt = $conn->executeQuery($sql, [$client->getId(),$myCompanies, $client->getId(),$myCompanies], $types);

        return empty($stmt->fetch()) === false;
    }

    public function clientBelongsToOtherSalesman(string $document, People $mySalesCompany): bool
    {
        $sql = "
            SELECT
                PC.client_id
            FROM people_client PC
                INNER JOIN document D ON D.people_id = PC.client_id                
                INNER JOIN people_salesman PS ON PC.company_id = PS.salesman_id
            WHERE
                D.document = :document
                AND PS.salesman_id != :my_sales_company
            LIMIT 1
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('client_id', 'client_id', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('document', $document);
        $nqu->setParameter('my_sales_company', $mySalesCompany->getId());

        return empty($nqu->getArrayResult()) === false;
    }

    public function getCustomerSalesman(People $customer): array
    {
        $sql = "
            SELECT DISTINCT
                peo.id,
                peo.name,
                peo.alias,
                peo.people_type AS type,
                IF(ema.email IS NULL, emi.email, ema.email) AS email,
                doc.document,
                IF((SELECT id FROM people_salesman WHERE company_id = peo.id LIMIT 1) IS NULL, 0, 1) AS is_provider
            FROM people peo
                INNER JOIN people_client   pcl ON pcl.company_id = peo.id
                INNER JOIN people_salesman psa ON psa.salesman_id = pcl.company_id
                LEFT JOIN email            ema ON ema.people_id = psa.salesman_id
                LEFT JOIN people_employee  pem ON pem.id = (SELECT id FROM people_employee WHERE company_id = psa.salesman_id LIMIT 1)
                LEFT JOIN email            emi ON emi.people_id = pem.employee_id
                LEFT JOIN document         doc ON doc.id = (SELECT id FROM document WHERE people_id = psa.salesman_id LIMIT 1)
            WHERE
                pcl.client_id = :customer_id
            AND pcl.commission > 0
            GROUP BY psa.salesman_id
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('type', 'type', 'string');
        $rsm->addScalarResult('document', 'document', 'string');
        $rsm->addScalarResult('name', 'name', 'string');
        $rsm->addScalarResult('alias', 'alias', 'string');
        $rsm->addScalarResult('email', 'email', 'string');
        $rsm->addScalarResult('is_provider', 'is_provider', 'boolean');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('customer_id', $customer->getId());

        return $nqu->getArrayResult();
    }
}
