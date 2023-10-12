<?php

namespace App\Repository;

use App\Entity\People;
use App\Entity\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method People|null find($id, $lockMode = null, $lockVersion = null)
 * @method People|null findOneBy(array $criteria, array $orderBy = null)
 * @method People[]    findAll()
 * @method People[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeopleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, People::class);
    }



    public function getSuperActiveCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminActiveCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }

    public function getSuperInactiveCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminInactiveCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }

    public function getSuperProspectCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminProspectCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }


    public function getSalesmanLeadsCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminLeadsCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }

    public function getSuperLeadsCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminLeadsCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }


    public function getSalesmanAllCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminAllCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }

    public function getSuperAllCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminAllCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }

    public function getClientAllCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null){
        return $this->getAdminAllCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }

    public function getSuperNewCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        return $this->getAdminNewCustomers($table, $search, $provider, $people, $isCount, $paginate);
    }

    public function getAdminActiveCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        // build query

        $sub  = 'SELECT P.id';
        $sub .= ' FROM people P';
        $sub .= ' INNER JOIN orders O ON O.client_id = P.id';
        $sub .= ' LEFT JOIN people_employee PEE ON PEE.employee_id = P.id';

        if ($table === "customer") {
            $sub .= ' WHERE O.provider_id = :provider_id';
        } else if ($table === "provider") {
            $sub .= ' WHERE O.client_id = :provider_id';
        }

        $sub .= ' AND O.status_id NOT IN (SELECT id FROM status WHERE real_status IN(\'open\', \'canceled\'))';
        $sub .= ' AND (O.order_date BETWEEN :from_date AND :to_date)';
        $sub .= ' GROUP BY P.id';
        $sub .= ' HAVING COUNT(PEE.employee_id) = 0';

        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';

        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';

        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';

        $sql .= ' WHERE PEO.id IN (' . $sub . ')';

        $sql .= ' AND PEO.people_type IN (:people_type)';
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);


        $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }


    public function getAdminLeadsCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= '"active",';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            if ($table != "provider" && isset($search['search'])) {
                $sql .= ' NULL AS email,';
                $sql .= ' NULL AS phone';
            } else {
                $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
                $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
            }
        }
        $sql .= ' FROM people PEO';

        $sql .= ' LEFT JOIN orders SO ON SO.client_id = PEO.id OR SO.payer_people_id = PEO.id';
        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = PEO.id';
        $sql .= ' WHERE 1=1';
        $sql .= ' AND PEO.people_type IN (:people_type)';
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' DOC.document = \''     . $search['search'] . '\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }


        $sql .= ' GROUP BY PEO.id';
        $sql .= ' HAVING COUNT(SO.id) = 0';
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        if ($isCount) {
            $sql = 'SELECT COUNT(C.total) AS total FROM (' . $sql . ') AS C';
        }



        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $salesmans = [];

        foreach ($people->getPeopleCompany() as $company) {
            $salesmans[] = $company->getCompany();
        }

        $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('salesman_id', $salesmans);
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }



    public function getAdminAllCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {


        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';

            if (!isset($search['search'])) {
                $sql .= ' PC.enable AS active,';
                $sql .= ' PC.id AS people_client_id,';
            } else {
                $sql .= '"active",';
            }

            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';

            if ($table != "provider" && isset($search['search'])) {
                $sql .= ' NULL AS email,';
                $sql .= ' NULL AS phone';
            } else {
                $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
                $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
            }
        }

        $sql .= ' FROM people PEO';


        if ($table == "provider") {
            $sql .= ' INNER JOIN people_provider PC ON PC.provider_id = PEO.id AND company_id=:provider_id AND company_id IN(:salesman_id)';
        } else if (!isset($search['search'])) {
            $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id=:provider_id';
            $sql .= ' INNER JOIN people_client PCS ON PCS.client_id = PC.client_id AND PCS.company_id IN(:salesman_id)';
        }

        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = PEO.id';
        $sql .= ' WHERE 1=1';

        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }

        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' DOC.document = \''     . $search['search'] . '\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }


        $sql .= ' GROUP BY PEO.id';
        if (!isset($search['search'])) {
            $sql .= ' HAVING COUNT(PEEE.employee_id) = 0';
        }
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        if ($isCount) {
            $sql = 'SELECT COUNT(C.total) AS total FROM (' . $sql . ') AS C';
        }



        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $salesmans = [];

        foreach ($people->getPeopleCompany() as $company) {
            $salesmans[] = $company->getCompany();
        }

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }

        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('salesman_id', $salesmans);
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }


    public function getAdminInactiveCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {



        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';
        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';

        $sql .= ' INNER JOIN orders OC ON (
                  OC.client_id = PEO.id
                  AND OC.provider_id = :provider_id
                  AND OC.status_id NOT IN (SELECT id FROM status WHERE real_status IN(\'open\', \'canceled\'))
                  AND OC.order_date NOT BETWEEN :from_date AND :to_date
                  )';

        $sql .= ' LEFT JOIN orders OOO ON (';
        $sql .= ' OOO.client_id = PEO.id';
        $sql .= ' AND OOO.provider_id = :provider_id';
        $sql .= ' AND OOO.status_id IN (SELECT id FROM status WHERE real_status IN(\'pending\', \'closed\'))';
        $sql .= ' AND OOO.order_date BETWEEN :from_date AND :to_date';
        $sql .= ')';



        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';
        //$sql .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = PEO.id';

        if ($table === "customer") {
            $sql .= ' WHERE OC.provider_id = :provider_id ';
        } else if ($table === "provider") {
            $sql .= ' WHERE OC.client_id = :provider_id ';
        }


        // search
        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }

        $sql .= ' GROUP BY PEO.id';
        $sql .= ' HAVING (COUNT(OOO.id) = 0 ';
        $sql .= ' )';
        //$sql .= ' AND COUNT(PEEE.employee_id) = 0)';

        $sql .= ' ORDER BY PEO.register_date DESC ';

        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }


        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }

    public function getAdminProspectCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        if ($table != "customer") {
            return $isCount ? 0 : [];
        }

        // build customer query

        $sub  = 'SELECT P.id';
        $sub .= ' FROM people P';

        $sub .= ' LEFT JOIN orders OO ON OO.client_id = P.id';
        $sub .= ' LEFT JOIN order_invoice OI ON OI.order_id = OO.id';
        $sub .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = P.id';
        $sub .= ' WHERE ((OO.provider_id = :provider_id OR OO.provider_id IS NULL)';
        $sub .= ' AND (P.register_date BETWEEN :from_date AND :to_date))';
        $sub .= ' OR P.id IN (SELECT client_id FROM people_client WHERE company_id = :provider_id)';

        $sub .= ' GROUP BY P.id';
        $sub .= ' HAVING (COUNT(OI.invoice_id) = 0';
        $sub .= ' AND COUNT(PEEE.employee_id) = 0)';

        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';
        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';
        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';

        $sql .= ' WHERE PEO.id IN (' . $sub . ')';

        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }

        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination



        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }

    public function getAdminNewCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        // build query

        $sub  = 'SELECT P.id';
        $sub .= ' FROM people P';

        $sub .= ' INNER JOIN orders O ON O.client_id = P.id';
        $sub .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = P.id';

        if ($table === "customer") {
            $sub .= ' WHERE O.provider_id = :provider_id';
        } else if ($table === "provider") {
            $sub .= ' WHERE O.client_id = :provider_id';
        }

        $sub .= ' AND O.status_id NOT IN (SELECT id FROM status WHERE real_status IN(\'open\', \'canceled\'))';
        $sub .= ' AND (O.order_date BETWEEN :from_date AND :to_date)';
        $sub .= ' AND (P.register_date BETWEEN :from_date AND :to_date)';

        $sub .= ' GROUP BY P.id';
        $sub .= ' HAVING COUNT(PEEE.employee_id) = 0';

        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';
        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';
        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';

        $sql .= ' WHERE PEO.id IN (' . $sub . ')';

        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }

    public function getSalesmanActiveCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        // build query

        $sub  = 'SELECT P.id';
        $sub .= ' FROM people P';

        $sub .= ' INNER JOIN orders O ON O.client_id = P.id';
        $sub .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = P.id';

        if ($table === "customer") {
            $sub .= ' INNER JOIN people C ON C.id = O.provider_id';
        } else if ($table === "provider") {
            $sub .= ' INNER JOIN people C ON C.id = O.client_id';
        }

        $sub .= ' INNER JOIN people_salesman PS ON PS.company_id = C.id';
        $sub .= ' INNER JOIN people S ON S.id = PS.salesman_id';
        $sub .= ' INNER JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id';

        if ($table === "customer") {
            $sub .= ' WHERE O.provider_id = :provider_id';
        } else if ($table === "provider") {
            $sub .= ' WHERE O.client_id = :provider_id';
        }

        $sub .= ' AND O.status_id NOT IN (SELECT id FROM status WHERE real_status IN(\'open\', \'canceled\'))';
        $sub .= ' AND (O.order_date BETWEEN :from_date AND :to_date)';
        $sub .= ' AND S.id IN (SELECT company_id FROM people_employee WHERE employee_id = :employee_id)';

        $sub .= ' GROUP BY P.id';
        $sub .= ' HAVING COUNT(PEEE.employee_id) = 0';

        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';
        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';
        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';

        $sql .= ' WHERE PEO.id IN (' . $sub . ')';
        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }

        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('employee_id', $people->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }

    public function getSalesmanInactiveCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        // build query

        $sub  = 'SELECT P.id';
        $sub .= ' FROM people P';

        $sub .= ' INNER JOIN orders O ON O.client_id = P.id';

        if ($table === "customer") {
            $sub .= ' INNER JOIN people C ON C.id = O.provider_id';
        } else if ($table === "provider") {
            $sub .= ' INNER JOIN people C ON C.id = O.client_id';
        }

        $sub .= ' INNER JOIN people_salesman PS ON PS.company_id = C.id';
        $sub .= ' INNER JOIN people S ON S.id = PS.salesman_id';
        $sub .= ' INNER JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id';
        $sub .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = P.id';
        $sub .= ' LEFT JOIN orders OO ON (';
        $sub .= ' OO.client_id = P.id';
        $sub .= ' AND (OO.status_id NOT IN (SELECT id FROM status WHERE real_status IN(\'open\', \'canceled\')) OR OO.id IS NULL)';
        $sub .= ' AND ((OO.order_date BETWEEN :from_date AND :to_date) OR OO.id IS NULL)';

        if ($table === "customer") {
            $sub .= ' AND (OO.provider_id = :provider_id  OR OO.id IS NULL)';
        } else if ($table === "provider") {
            $sub .= ' AND (OO.client_id = :provider_id  OR OO.id IS NULL)';
        }

        $sub .= ')';

        if ($table === "customer") {
            $sub .= ' WHERE O.provider_id = :provider_id';
        } else if ($table === "provider") {
            $sub .= ' WHERE O.client_id = :provider_id';
        }

        $sub .= ' AND O.status_id NOT IN (SELECT id FROM status WHERE real_status IN(\'open\', \'canceled\'))';
        $sub .= ' AND (O.order_date NOT BETWEEN :from_date AND :to_date)';
        $sub .= ' AND S.id IN (SELECT company_id FROM people_employee WHERE employee_id = :employee_id)';


        $sub .= ' GROUP BY P.id';
        $sub .= ' HAVING COUNT(PEEE.employee_id) = 0';

        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';
        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';
        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';

        $sql .= ' WHERE PEO.id IN (' . $sub . ')';

        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('employee_id', $people->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }

    public function getSalesmanProspectCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        if ($table != "customer") {
            return $isCount ? 0 : [];
        }

        // build query

        $sub  = 'SELECT P.id';
        $sub .= ' FROM people P';

        $sub .= ' LEFT JOIN orders OO ON OO.client_id = P.id';
        $sub .= ' LEFT JOIN order_invoice OI ON OI.order_id = OO.id';
        $sub .= ' LEFT JOIN people C ON C.id = OO.provider_id';
        $sub .= ' LEFT JOIN people_salesman PS ON PS.company_id = C.id';
        $sub .= ' LEFT JOIN people S ON S.id = PS.salesman_id';
        $sub .= ' LEFT JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id';
        $sub .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = P.id';

        $sub .= ' WHERE ((OO.provider_id = :provider_id OR OO.provider_id IS NULL)';
        $sub .= ' AND (P.register_date BETWEEN :from_date AND :to_date)';
        $sub .= ' AND S.id IN (SELECT company_id FROM people_employee WHERE employee_id = :employee_id))';
        $sub .= ' OR P.id IN (SELECT client_id FROM people_client WHERE company_id IN (SELECT company_id FROM people_employee WHERE employee_id = :employee_id))';

        $sub .= ' GROUP BY P.id';
        $sub .= ' HAVING (COUNT(OI.invoice_id) = 0';
        $sub .= ' AND COUNT(PEEE.employee_id) = 0)';



        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';
        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';
        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';

        $sql .= ' WHERE PEO.id IN (' . $sub . ')';

        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('employee_id', $people->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }

    public function getSalesmanNewCustomers($table = "customer", array $search, People $provider, People $people, bool $isCount = false, $paginate = null)
    {
        // build query

        $sub  = 'SELECT P.id';
        $sub .= ' FROM people P';

        $sub .= ' INNER JOIN orders O ON O.client_id = P.id';

        if ($table === "customer") {
            $sub .= ' INNER JOIN people C ON C.id = O.provider_id';
        } else if ($table === "provider") {
            $sub .= ' INNER JOIN people C ON C.id = O.client_id';
        }

        $sub .= ' INNER JOIN people_salesman PS ON PS.company_id = C.id';
        $sub .= ' INNER JOIN people S ON S.id = PS.salesman_id';
        $sub .= ' INNER JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id';
        $sub .= ' LEFT JOIN people_employee PEEE ON PEEE.employee_id = P.id';

        if ($table === "customer") {
            $sub .= ' WHERE O.provider_id = :provider_id';
        } else if ($table === "provider") {
            $sub .= ' WHERE O.client_id = :provider_id';
        }

        $sub .= ' AND O.status_id NOT IN (SELECT id FROM status WHERE real_status IN(\'open\', \'canceled\'))';
        $sub .= ' AND (O.order_date BETWEEN :from_date AND :to_date)';
        $sub .= ' AND (P.register_date BETWEEN :from_date AND :to_date)';
        $sub .= ' AND S.id IN (SELECT company_id FROM people_employee WHERE employee_id = :employee_id)';

        $sub .= ' GROUP BY P.id';
        $sub .= ' HAVING COUNT(PEEE.employee_id) = 0';


        /**
         * Build people query
         */

        if ($isCount) {
            $sql = 'SELECT COUNT(PEO.id) AS total';
        } else {
            $sql  = 'SELECT DISTINCT';
            $sql .= ' PEO.id,';
            $sql .= ' PEO.name,';
            $sql .= ' PEO.register_date,';
            $sql .= ' PEO.alias,';
            $sql .= ' PEO.enable,';
            $sql .= ' PEO.people_type,';
            $sql .= ' PC.enable AS active,';
            $sql .= ' PC.id AS people_client_id,';
            $sql .= ' IF(CHAR_LENGTH(DOC.document) = 10 OR CHAR_LENGTH(DOC.document) = 13, CONCAT(\'0\', DOC.document), DOC.document) AS document,';
            $sql .= ' IF(EMA.email IS NULL, EMA2.email, EMA.email) AS email,';
            $sql .= ' IF(PHO.phone IS NULL, CONCAT(PHO2.ddd, PHO2.phone), CONCAT(PHO.ddd, PHO.phone)) AS phone';
        }

        $sql .= ' FROM people PEO';
        $sql .= ' INNER JOIN people_client PC ON PC.client_id = PEO.id AND PC.company_id = :provider_id';
        $sql .= ' LEFT JOIN document DOC ON DOC.id = (SELECT id FROM document WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA ON EMA.id = (SELECT id FROM email WHERE people_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO ON PHO.id = (SELECT id FROM phone WHERE people_id = PEO.id LIMIT 1)';

        $sql .= ' LEFT JOIN people_employee PEE ON PEE.id = (SELECT id FROM people_employee WHERE company_id = PEO.id LIMIT 1)';
        $sql .= ' LEFT JOIN email EMA2 ON EMA2.id = (SELECT id FROM email WHERE people_id = PEE.employee_id LIMIT 1)';
        $sql .= ' LEFT JOIN phone PHO2 ON PHO2.id = (SELECT id FROM phone WHERE people_id = PEE.employee_id LIMIT 1)';

        $sql .= ' WHERE PEO.id IN (' . $sub . ')';

        if(isset($search['people_type'])){
            $sql .= ' AND PEO.people_type IN (:people_type)';
        }
        // search

        if (isset($search['search'])) {
            $sql .= ' AND (';
            $sql .= ' PEO.name LIKE \'%'     . $search['search'] . '%\' OR';
            $sql .= ' PEO.alias LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' DOC.document LIKE \'%' . $search['search'] . '%\' OR';
            $sql .= ' EMA.email LIKE \'%'    . $search['search'] . '%\' OR';
            $sql .= ' PHO.phone LIKE \'%'    . $search['search'] . '%\'';
            $sql .= ')';
        }
        $sql .= ' ORDER BY PEO.register_date DESC ';
        // pagination

        if ($paginate !== null) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        // mapping

        $rsm = new ResultSetMapping();

        if ($isCount) {
            $rsm->addScalarResult('total', 'total', 'integer');
        } else {
            $rsm->addScalarResult('id', 'id', 'integer');
            $rsm->addScalarResult('people_client_id', 'people_client_id', 'integer');
            $rsm->addScalarResult('active', 'active', 'boolean');
            $rsm->addScalarResult('enable', 'enable', 'boolean');
            $rsm->addScalarResult('people_type', 'people_type', 'string');
            $rsm->addScalarResult('name', 'name', 'string');
            $rsm->addScalarResult('register_date', 'register_date', 'string');
            $rsm->addScalarResult('alias', 'alias', 'string');
            $rsm->addScalarResult('document', 'document', 'string');
            $rsm->addScalarResult('email', 'email', 'string');
            $rsm->addScalarResult('phone', 'phone', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        if(isset($search['people_type'])){
            $nqu->setParameter('people_type', $search['people_type'] ?: ['F', 'J']);
        }
        $nqu->setParameter('provider_id', $provider->getId());
        $nqu->setParameter('employee_id', $people->getId());
        $nqu->setParameter('from_date', $search['fromDate']);
        $nqu->setParameter('to_date', $search['toDate']);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }

    public function getActiveClientsCountByDate(\DateTime $fromDate, \DateTime $toDate, int $providerId, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): int
    {
        $sql = "
                SELECT COUNT(DISTINCT P.id) AS qtd FROM people P
                INNER JOIN orders O ON O.client_id = P.id";


        if (!$mainDashboard) {
            $sql .= "
            INNER JOIN people C ON C.id = O.provider_id
            INNER JOIN people_salesman PS ON PS.company_id = C.id
                         INNER JOIN people S ON S.id = PS.salesman_id
                         INNER JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id";
        }

        $sql .= "
        WHERE   O.provider_id = :provider_id
                AND   O.status_id NOT IN (:status_id)
                AND   (O.order_date    BETWEEN :from_date AND :to_date)";

        if (!$mainDashboard) {
            $sql .= " AND S.id IN (:my_companies)";
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('qtd', 'qtd', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('provider_id', $providerId);
        if (!$mainDashboard) {
            $nqu->setParameter('my_companies', $myCompanies);
        }
        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));
        $nqu->setParameter('from_date', $fromDate->format('Y-m-d 00:00:00'));
        $nqu->setParameter('to_date', $toDate->format('Y-m-d 23:59:59'));

        return $nqu->getArrayResult() ? $nqu->getArrayResult()[0]['qtd'] : 0;
    }

    public function getNewClientsCountByDate(\DateTime $fromDate, \DateTime $toDate, int $providerId, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): int
    {
        $sql = "
                SELECT COUNT(DISTINCT P.id) AS qtd FROM people P
                INNER JOIN orders O ON O.client_id = P.id";

        if (!$mainDashboard) {
            $sql .= "
            INNER JOIN people C ON C.id = O.provider_id
            INNER JOIN people_salesman PS ON PS.company_id = C.id
                INNER JOIN people S ON S.id = PS.salesman_id
                INNER JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id";
        }
        $sql .= "
        WHERE O.provider_id = :provider_id
                AND   O.status_id NOT IN (:status_id)
                AND   (O.order_date    BETWEEN :from_date AND :to_date)
                AND   (P.register_date BETWEEN :from_date AND :to_date)";

        if (!$mainDashboard) {
            $sql .= " AND   S.id IN (:my_companies)";
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('qtd', 'qtd', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('provider_id', $providerId);
        if (!$mainDashboard) {
            $nqu->setParameter('my_companies', $myCompanies);
        }
        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));
        $nqu->setParameter('from_date', $fromDate->format('Y-m-d 00:00:00'));
        $nqu->setParameter('to_date', $toDate->format('Y-m-d 23:59:59'));

        return $nqu->getArrayResult() ? $nqu->getArrayResult()[0]['qtd'] : 0;
    }

    public function getAllProfessionals(array $search, ?array $paginate = null, bool $isCount = false)
    {
      /**
       * Build people query
       */

      if ($isCount) {
          $sql = 'SELECT COUNT(DISTINCT tra.id) AS total';
      }
      else {
          $sql  = 'SELECT DISTINCT';
          $sql .= ' tra.id,';
          $sql .= ' tra.name,';
          $sql .= ' tra.alias,';
          $sql .= ' IF(CHAR_LENGTH(doc.document) = 11, doc.document, CONCAT("0", doc.document)) AS document,';
          $sql .= ' ema.email,';
          $sql .= ' CONCAT(pho.ddd, pho.phone) AS phone,';
          $sql .= ' pet.enable,';
          $sql .= ' uf.uf,';
          $sql .= ' uf.state,';
          $sql .= ' ct.city,';
          $sql .= ' ds.district,';
          $sql .= ' st.street,';
          $sql .= ' cp.cep,';
          $sql .= ' ad.number,';
          $sql .= ' ad.latitude,';
          $sql .= ' ad.longitude,';
          $sql .= ' ad.complement';
      }

      $sql .= ' FROM people as tra';

      $sql .= ' INNER JOIN people_professional pet ON pet.professional_id = tra.id';
      $sql .= ' LEFT JOIN document doc ON doc.id = (SELECT id FROM document WHERE document_type_id = 2 AND people_id = tra.id LIMIT 1)';
      $sql .= ' LEFT JOIN email ema ON ema.id = (SELECT id FROM email WHERE people_id = tra.id LIMIT 1)';
      $sql .= ' LEFT JOIN phone pho ON pho.id = (SELECT id FROM phone WHERE people_id = tra.id LIMIT 1)';
      $sql .= ' LEFT JOIN address ad ON tra.id = ad.people_id';
      $sql .= ' LEFT JOIN street st ON st.id = ad.street_id';
      $sql .= ' LEFT JOIN district ds ON ds.id = st.district_id';
      $sql .= ' LEFT JOIN city ct ON ct.id = ds.city_id';
      $sql .= ' LEFT JOIN state uf ON uf.id = ct.state_id';
      $sql .= ' LEFT JOIN cep cp ON cp.id = st.cep_id';

      $sql .= ' WHERE tra.people_type = "F"';

      // search

      if (isset($search['search'])) {
          $sql .= ' AND (';
          $sql .= ' tra.name LIKE \'%'     . $search['search'] . '%\' OR';
          $sql .= ' tra.alias LIKE \'%'    . $search['search'] . '%\' OR';
          $sql .= ' doc.document LIKE \'%' . $search['search'] . '%\' OR';
          $sql .= ' ema.email LIKE \'%'    . $search['search'] . '%\' OR';
          $sql .= ' pho.phone LIKE \'%'    . $search['search'] . '%\'';
          $sql .= ')';
      }

      if (isset($search['company'])) {
        if ($search['company'] instanceof People) {
          $sql .= ' AND pet.company_id = :company_id';
        }
      }

      if (isset($search['ids']) && count($search['ids']) > 0) {
        $sql .= ' AND tra.id IN (' . join(', ', $search['ids']) . ')';
      }

      // pagination

      if ($paginate !== null) {
          $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
      }

      // mapping

      $rsm = new ResultSetMapping();

      if ($isCount) {
        $rsm->addScalarResult('total', 'total', 'integer');
      }
      else {
        $rsm->addScalarResult('id'        , 'id'        , 'integer');
        $rsm->addScalarResult('name'      , 'name'      , 'string');
        $rsm->addScalarResult('alias'     , 'alias'     , 'string');
        $rsm->addScalarResult('document'  , 'document'  , 'string');
        $rsm->addScalarResult('email'     , 'email'     , 'string');
        $rsm->addScalarResult('phone'     , 'phone'     , 'string');
        $rsm->addScalarResult('enable'    , 'enable'    , 'boolean');
        $rsm->addScalarResult('uf'        , 'uf'        , 'string');
        $rsm->addScalarResult('state'     , 'state'     , 'string');
        $rsm->addScalarResult('city'      , 'city'      , 'string');
        $rsm->addScalarResult('district'  , 'district'  , 'string');
        $rsm->addScalarResult('street'    , 'street'    , 'string');
        $rsm->addScalarResult('cep'       , 'cep'       , 'string');
        $rsm->addScalarResult('number'    , 'number'    , 'string');
        $rsm->addScalarResult('latitude'  , 'latitude'  , 'float');
        $rsm->addScalarResult('longitude' , 'longitude' , 'float');
        $rsm->addScalarResult('complement', 'complement', 'string');
      }

      $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

      if (isset($search['company'])) {
        if ($search['company'] instanceof People) {
          $nqu->setParameter('company_id', $search['company']->getId());
        }
      }

      $result = $nqu->getArrayResult();

      if (empty($result)) {
          return $isCount ? 0 : [];
      }

      return $isCount ? $result[0]['total'] : $result;
    }

    public function search($input, $limit = 50)
    {
        $sql = "SELECT P.* FROM people P WHERE
                    P.name LIKE :input OR
                    P.alias LIKE :input
                ORDER BY P.name LIMIT :limit";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('name', 'name', 'string');
        $rsm->addScalarResult('alias', 'alias', 'string');
        $rsm->addScalarResult('people_type', 'people_type', 'string');
        $rsm->addScalarResult('enable', 'enable', 'boolean');
        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('input', '%' . $input . '%');
        $nqu->setParameter('limit',  $limit);

        $result = $nqu->getArrayResult();

        if (empty($result))
            return null;

        return $result;
    }

    public function getInactiveClientsCountByDate(\DateTime $fromDate, \DateTime $toDate, int $providerId, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): int
    {
        $sql = "SELECT COUNT(DISTINCT P.id) AS qtd FROM people P
                INNER JOIN orders O ON O.client_id = P.id";


        if (!$mainDashboard) {
            $sql .= "
            INNER JOIN people C ON C.id = O.provider_id
            INNER JOIN people_salesman PS ON PS.company_id = C.id
                INNER JOIN people S ON S.id = PS.salesman_id
                INNER JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id";
        }
        $sql .= "
        LEFT  JOIN orders OO ON (
                                    OO.client_id = P.id
                                     AND   (OO.status_id NOT IN (:status_id) OR OO.id IS NULL)
                                     AND   ((OO.order_date  BETWEEN  :from_date AND :to_date) OR OO.id IS NULL)
                                     AND   (OO.provider_id = :provider_id  OR OO.id IS NULL)
                )
                WHERE O.provider_id = :provider_id
                AND   O.status_id NOT IN (:status_id)
                AND   (O.order_date  NOT  BETWEEN :from_date AND :to_date)";
        if (!$mainDashboard) {
            $sql .= " AND   S.id IN (:my_companies)";
        }
        $sql .= "
        GROUP BY OO.client_id
                HAVING COUNT(OO.id) = :count";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('qtd', 'qtd', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $nqu->setParameter('count', 0);
        $nqu->setParameter('provider_id', $providerId);
        if (!$mainDashboard) {
            $nqu->setParameter('my_companies', $myCompanies);
        }
        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));
        $nqu->setParameter('from_date', $fromDate->format('Y-m-d 00:00:00'));
        $nqu->setParameter('to_date', $toDate->format('Y-m-d 23:59:59'));

        return $nqu->getArrayResult() ? $nqu->getArrayResult()[0]['qtd'] : 0;
    }

    public function getProspectClientsCountByDate(\DateTime $fromDate, \DateTime $toDate, int $providerId, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): int
    {
        /*
        $sql = "
                SELECT COUNT(DISTINCT P.id) AS qtd FROM people P
                LEFT JOIN orders O ON O.client_id = P.id AND O.status_id NOT IN (:status_id) AND (O.provider_id = :provider_id OR OO.provider_id IS NULL)";
        if (!$mainDashboard) {

            $sql .= "
                INNER JOIN people C ON C.id = O.provider_id
                INNER JOIN people_salesman PS ON PS.company_id = C.id
                INNER JOIN people S ON S.id = PS.salesman_id
                INNER JOIN people_client PC ON PC.client_id = P.id AND PC.company_id = S.id";
        }
        //$sql .= " LEFT JOIN orders OO ON OO.client_id = P.id AND OO.status_id NOT IN (:status_id) AND   OO.provider_id = :provider_id":

        $sql .= " WHERE O.provider_id = :provider_id OR O.provider_id IS NULL";
        if (!$mainDashboard) {
            $sql .= " AND   S.id IN (:my_companies)";
        }
        $sql .= "
        GROUP BY O.provider_id
            HAVING COUNT(DISTINCT O.id) = :count
        ";


        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('qtd', 'qtd', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $nqu->setParameter('count', 0);
        $nqu->setParameter('provider_id', $providerId);
        if (!$mainDashboard) {
            $nqu->setParameter('my_companies', $myCompanies);
        }
        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));

        return $nqu->getArrayResult() ? $nqu->getArrayResult()[0]['qtd'] : 0;
        */
        return 0;
    }

    public function getQuoteTotalsByDate(\DateTime $fromDate, \DateTime $toDate, int $providerId, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): ?array
    {
        $sql = "
            SELECT
            SUM(O.price)       AS total_price,
            COUNT(DISTINCT O.id) AS total_count,
            " . ($groupByDate ? "DATE(O.order_date)" : "null") . " AS order_date
            FROM orders O";

        $sql .= "
        WHERE O.provider_id = :provider_id
            AND   O.status_id IN (:status_id)
            AND   (O.order_date    BETWEEN :from_date AND :to_date)
            AND   O.order_type = ':order_type'
            ";


        if (!$mainDashboard) {
            $sql .= " AND   S.id IN (:my_companies)";
        }

        $sql .= ($groupByDate ? " GROUP BY order_date" : " LIMIT 1");

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('total_price', 'total_price', 'float');
        $rsm->addScalarResult('total_count', 'total_count', 'integer');
        $rsm->addScalarResult('order_date', 'order_date', 'string');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));
        $nqu->setParameter('provider_id', $providerId);
        if (!$mainDashboard) {
            $nqu->setParameter('my_companies', $myCompanies);
        }
        $nqu->setParameter('from_date', $fromDate->format('Y-m-d 00:00:00'));
        $nqu->setParameter('to_date', $toDate->format('Y-m-d 23:59:59'));
        $nqu->setParameter('order_type', 'sale');


        $result = $nqu->getArrayResult();
        if (empty($result))
            return null;

        /**
         * @todo agrupar por date na query não com foreach
         */
        if ($groupByDate) {
            $_result = [];

            foreach ($result as $data) {
                if (empty($data['order_date']))
                    continue;

                if (array_key_exists($data['order_date'], $_result)) {
                    $_result[$data['order_date']]['total_price'] += empty($data['total_price']) ? 0 : $data['total_price'];
                    $_result[$data['order_date']]['total_count'] += $data['total_count'];
                } else {
                    $_result[$data['order_date']] = [
                        'total_price' => empty($data['total_price']) ? 0 : $data['total_price'],
                        'total_count' => $data['total_count'],
                        'order_date'  => $data['order_date'],
                    ];
                }
            }

            return array_values($_result);
        }

        return [
            'total_price' => empty($result[0]['total_price']) ? 0 : $result[0]['total_price'],
            'total_count' => $result[0]['total_count'],
            'order_date'  => $result[0]['order_date']
        ];
    }

    public function getSalesTotalsByDate(\DateTime $fromDate, \DateTime $toDate, int $providerId, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): ?array
    {
        $sql = "
                SELECT
                DISTINCT O.id,
                SUM(O.price)       AS total_price,
                COUNT(DISTINCT O.id)        AS total_count,
                " . ($groupByDate ? "DATE(O.order_date)" : "null") . " AS order_date
            FROM orders O";
        if (!$mainDashboard) {
            $sql .= "
            INNER JOIN people C ON C.id = O.provider_id
            INNER JOIN people_salesman PS ON PS.company_id = C.id
            INNER JOIN people S ON S.id = PS.salesman_id
            INNER JOIN people_client PC ON PC.client_id = O.client_id AND PC.company_id = S.id";
        }


        $sql .= "
        INNER JOIN
        (SELECT main_order_id FROM orders WHERE orders.order_type = :porder_type GROUP BY orders.main_order_id) AS PO ON PO.main_order_id = O.id
        ";


        $sql .= "
        WHERE O.provider_id = :provider_id
            AND   O.status_id NOT IN (:status_id)
            AND   (O.order_date    BETWEEN :from_date AND :to_date)
            AND   O.order_type =:order_type
            ";
        if (!$mainDashboard) {
            $sql .= " AND   S.id IN (:my_companies)";
        }
        $sql .= ($groupByDate ? " GROUP BY order_date" : " LIMIT 1");

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('total_price', 'total_price', 'float');
        $rsm->addScalarResult('total_count', 'total_count', 'integer');
        $rsm->addScalarResult('order_date', 'order_date', 'string');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));
        $nqu->setParameter('provider_id', $providerId);
        if (!$mainDashboard) {
            $nqu->setParameter('my_companies', $myCompanies);
        }
        $nqu->setParameter('from_date', $fromDate->format('Y-m-d 00:00:00'));
        $nqu->setParameter('to_date', $toDate->format('Y-m-d 23:59:59'));
        $nqu->setParameter('order_type', 'sale');
        $nqu->setParameter('porder_type', 'purchase');


        $result = $nqu->getArrayResult();
        if (empty($result))
            return null;

        /**
         * @todo agrupar por date na query não com foreach
         */
        if ($groupByDate) {
            $_result = [];

            foreach ($result as $data) {
                if (empty($data['order_date']))
                    continue;

                if (array_key_exists($data['order_date'], $_result)) {
                    $_result[$data['order_date']]['total_price'] += empty($data['total_price']) ? 0 : $data['total_price'];
                    $_result[$data['order_date']]['total_count'] += $data['total_count'];
                } else {
                    $_result[$data['order_date']] = [
                        'total_price' => empty($data['total_price']) ? 0 : $data['total_price'],
                        'total_count' => $data['total_count'],
                        'order_date'  => $data['order_date'],
                    ];
                }
            }

            return array_values($_result);
        }

        return [
            'total_price' => empty($result[0]['total_price']) ? 0 : $result[0]['total_price'],
            'total_count' => $result[0]['total_count'],
            'order_date'  => $result[0]['order_date']
        ];
    }


    public function getComissionTotalsByDate(\DateTime $fromDate, \DateTime $toDate, int $client_id, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): ?array
    {
        $sql = "
                    SELECT
                    SUM(CO.price)       AS total_price,
                    COUNT(CO.id)        AS total_count,
                    " . ($groupByDate ? "DATE(CO.order_date)" : "null") . " AS order_date
                FROM orders CO";

        $sql .= "
            INNER JOIN
        (SELECT id,status_id,order_date,order_type FROM orders WHERE orders.order_type = :sorder_type GROUP BY orders.main_order_id) AS SO ON CO.main_order_id = SO.id
        ";

        $sql .= "
        WHERE CO.client_id = :client_id
            AND   SO.status_id NOT IN (:status_id)
            AND   (SO.order_date    BETWEEN :from_date AND :to_date)
            AND   SO.order_type =:order_type
            ";

        if (!$mainDashboard) {
            $sql .= " AND   S.id IN (:my_companies)";
        }
        $sql .= ($groupByDate ? " GROUP BY order_date" : " LIMIT 1");


        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('total_price', 'total_price', 'float');
        $rsm->addScalarResult('total_count', 'total_count', 'integer');
        $rsm->addScalarResult('order_date', 'order_date', 'string');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));
        $nqu->setParameter('client_id', $client_id);

        $nqu->setParameter('from_date', $fromDate->format('Y-m-d 00:00:00'));
        $nqu->setParameter('to_date', $toDate->format('Y-m-d 23:59:59'));
        $nqu->setParameter('sorder_type', 'sale');
        $nqu->setParameter('order_type', 'comission');

        $result = $nqu->getArrayResult();
        if (empty($result))
            return null;

        /**
         * @todo agrupar por date na query não com foreach
         */
        if ($groupByDate) {
            $_result = [];

            foreach ($result as $data) {
                if (empty($data['order_date']))
                    continue;

                if (array_key_exists($data['order_date'], $_result)) {
                    $_result[$data['order_date']]['total_price'] += empty($data['total_price']) ? 0 : $data['total_price'];
                    $_result[$data['order_date']]['total_count'] += $data['total_count'];
                } else {
                    $_result[$data['order_date']] = [
                        'total_price' => empty($data['total_price']) ? 0 : $data['total_price'],
                        'total_count' => $data['total_count'],
                        'order_date'  => $data['order_date'],
                    ];
                }
            }

            return array_values($_result);
        }

        return [
            'total_price' => empty($result[0]['total_price']) ? 0 : $result[0]['total_price'],
            'total_count' => $result[0]['total_count'],
            'order_date'  => $result[0]['order_date']
        ];
    }

    public function getPurshasingTotalsByDate(\DateTime $fromDate, \DateTime $toDate, int $providerId, array $myCompanies, bool $groupByDate = false, bool $mainDashboard = false): ?array
    {
        $sql = "
                    SELECT
                    SUM(O.price)       AS total_price,
                    COUNT(DISTINCT O.id)        AS total_count,
                    " . ($groupByDate ? "DATE(SO.order_date)" : "null") . " AS order_date
                FROM orders O";
        if (!$mainDashboard) {
            $sql .= "
                INNER JOIN people C ON C.id = O.provider_id
                INNER JOIN people_salesman PS ON PS.company_id = C.id
                INNER JOIN people S ON S.id = PS.salesman_id
                INNER JOIN people_client PC ON PC.client_id = O.client_id AND PC.company_id = S.id";
        }
        $sql .= "
                INNER JOIN orders SO ON SO.id = O.main_order_id AND SO.status_id NOT IN (:status_id) AND   SO.order_type =:sorder_type
                WHERE SO.provider_id = :provider_id
                AND   O.order_type =:order_type
                AND   SO.status_id NOT IN (:status_id)
                AND   (SO.order_date    BETWEEN :from_date AND :to_date)";
        if (!$mainDashboard) {
            $sql .= " AND   S.id IN (:my_companies)";
        }
        $sql .= ($groupByDate ? " GROUP BY O.order_date" : " LIMIT 1");

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('total_price', 'total_price', 'float');
        $rsm->addScalarResult('total_count', 'total_count', 'integer');
        $rsm->addScalarResult('order_date', 'order_date', 'string');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('status_id', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open', 'canceled']]));
        $nqu->setParameter('provider_id', $providerId);
        $nqu->setParameter('order_type', 'purchase');
        $nqu->setParameter('sorder_type', 'sales');

        if (!$mainDashboard) {
            $nqu->setParameter('my_companies', $myCompanies);
        }
        $nqu->setParameter('from_date', $fromDate->format('Y-m-d 00:00:00'));
        $nqu->setParameter('to_date', $toDate->format('Y-m-d 23:59:59'));

        $result = $nqu->getArrayResult();
        if (empty($result))
            return null;

        /**
         * @todo agrupar por date na query não com foreach
         */
        if ($groupByDate) {
            $_result = [];

            foreach ($result as $data) {
                if (empty($data['order_date']))
                    continue;

                if (array_key_exists($data['order_date'], $_result)) {
                    $_result[$data['order_date']]['total_price'] += empty($data['total_price']) ? 0 : $data['total_price'];
                    $_result[$data['order_date']]['total_count'] += $data['total_count'];
                } else {
                    $_result[$data['order_date']] = [
                        'total_price' => empty($data['total_price']) ? 0 : $data['total_price'],
                        'total_count' => $data['total_count'],
                        'order_date'  => $data['order_date'],
                    ];
                }
            }

            return array_values($_result);
        }

        return [
            'total_price' => empty($result[0]['total_price']) ? 0 : $result[0]['total_price'],
            'total_count' => $result[0]['total_count'],
            'order_date'  => $result[0]['order_date']
        ];
    }
}
