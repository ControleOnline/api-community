<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Status;
use App\Entity\People;
use App\Entity\PeopleClient;
use App\Entity\PeopleSalesman;
use App\Entity\SalesOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function updateData(int $clientId, array $data): void
    {
        $rawSQL = '';
        $params = [];

        try {

            $this->getEntityManager()->getConnection()->executeQuery('START TRANSACTION', $params);

            // update client document

            if (isset($data['document']) && !empty($data['document'])) {
                $rawSQL = 'UPDATE document SET document = :document WHERE people_id = :people_id AND document_type_id = 3';
                $params = [
                    'people_id' => $clientId,
                    'document'  => $data['document'],
                ];

                $this->getEntityManager()->getConnection()->executeQuery($rawSQL, $params);
            }

            // update client name

            if (isset($data['name']) && !empty($data['name'])) {
                $rawSQL = 'UPDATE people SET name = :name WHERE id = :people_id';
                $params = [
                    'people_id' => $clientId,
                    'name'      => $data['name'],
                ];

                $this->getEntityManager()->getConnection()->executeQuery($rawSQL, $params);
            }

            // update client alias

            if (isset($data['alias']) && !empty($data['alias'])) {
                $rawSQL = 'UPDATE people SET alias = :alias WHERE id = :people_id';
                $params = [
                    'people_id' => $clientId,
                    'alias'     => $data['alias'],
                ];

                $this->getEntityManager()->getConnection()->executeQuery($rawSQL, $params);
            }

            // update client email

            if (isset($data['email']) && !empty($data['email'])) {
                $rawSQL = 'UPDATE email SET email = :email WHERE people_id = :people_id';
                $params = [
                    'people_id' => $clientId,
                    'email'     => $data['email'],
                ];

                $this->getEntityManager()->getConnection()->executeQuery($rawSQL, $params);
            }

            // update client phone

            if (isset($data['phone']) && is_array($data['phone']) && !empty($data['phone'])) {
                $rawSQL = 'UPDATE phone SET ddd = :ddd, phone = :phone WHERE people_id = :people_id';
                $params = [
                    'people_id' => $clientId,
                    'ddd'       => $data['phone']['ddd'],
                    'phone'     => $data['phone']['phone'],
                ];

                $this->getEntityManager()->getConnection()->executeQuery($rawSQL, $params);
            }

            $this->getEntityManager()->getConnection()->executeQuery('COMMIT', $params);

        } catch (\Exception $e) {
            $this->getEntityManager()->getConnection()->executeQuery('ROLLBACK', $params);

            throw new \Exception('Error updating client data');
        }
    }

    public function getSalesmanClientCollection(string $type, string $fromDate, string $toDate, People $provider, People $salesman)
    {
        $queryBuilder = $this->createQueryBuilder('myclients');

        switch ($type) {

            case 'active':

                $queryBuilder->innerJoin(SalesOrder::class,     'O'  , 'WITH', 'O.client = myclients.id');
                $queryBuilder->innerJoin(People::class,         'C'  , 'WITH', 'C.id = O.provider');
                $queryBuilder->innerJoin(PeopleSalesman::class, 'kkk', 'WITH', 'kkk.company = C.id');
                $queryBuilder->innerJoin(People::class,         'S'  , 'WITH', 'S.id = kkk.salesman');
                $queryBuilder->innerJoin(PeopleClient::class,   'PC' , 'WITH', 'PC.client = myclients.id AND PC.company_id = S.id');
                $queryBuilder->andWhere('O.provider = :provider');
                $queryBuilder->andWhere('S.id IN (:my_companies)');
                $queryBuilder->andWhere('O.status NOT IN (:statuses)');
                $queryBuilder->andWhere('O.orderDate BETWEEN :from_date AND :to_date');
                $queryBuilder->setParameter('provider'    , $provider);
                $queryBuilder->setParameter('statuses'    , $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open','canceled']]));
                $queryBuilder->setParameter('from_date'   , $fromDate);
                $queryBuilder->setParameter('to_date'     , $toDate);
                $queryBuilder->setParameter('my_companies', $this->getMyCompanies($salesman));

            break;

            case 'inactive':

                $queryBuilder->innerJoin(SalesOrder::class,     'O'  , 'WITH', 'O.client = myclients.id');
                $queryBuilder->innerJoin(People::class,         'C'  , 'WITH', 'C.id = O.provider');
                $queryBuilder->innerJoin(PeopleSalesman::class, 'kkk', 'WITH', 'kkk.company = C.id');
                $queryBuilder->innerJoin(People::class,         'S'  , 'WITH', 'S.id = kkk.salesman');
                $queryBuilder->innerJoin(PeopleClient::class,   'PC' , 'WITH', 'PC.client = myclients.id AND PC.company_id = S.id');
                $queryBuilder->leftJoin (SalesOrder::class,     'jj' , 'WITH', 'jj.client = myclients.id
                                                                             AND   (jj.status NOT IN (:statuses) OR jj.id IS NULL)
                                                                             AND   ((jj.orderDate  BETWEEN  :from_date AND :to_date) OR jj.id IS NULL)
                                                                             AND   (jj.provider = :provider  OR jj.id IS NULL)');
                $queryBuilder->andWhere('O.provider = :provider');
                $queryBuilder->andWhere('S.id IN (:my_companies)');
                $queryBuilder->andWhere('O.status NOT IN (:statuses)');
                $queryBuilder->andWhere('O.orderDate  NOT BETWEEN :from_date AND :to_date');
                $queryBuilder->groupBy('myclients.id');
                $queryBuilder->having('COUNT(jj.id) > :count');
                $queryBuilder->andHaving('COUNT(O.id) = :count');
                $queryBuilder->setParameter('provider' , $provider);
                $queryBuilder->setParameter('statuses' , $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open','canceled']]));
                $queryBuilder->setParameter('from_date', $fromDate);
                $queryBuilder->setParameter('to_date'  , $toDate);
                $queryBuilder->setParameter('count'    , 0);
                $queryBuilder->setParameter('my_companies', $this->getMyCompanies($salesman));

            break;

            case 'prospect':

                $queryBuilder->innerJoin(SalesOrder::class,     'O' , 'WITH', 'O.client = myclients.id');
                $queryBuilder->innerJoin(People::class,         'C' , 'WITH', 'C.id = O.provider');
                $queryBuilder->innerJoin(PeopleSalesman::class, 'PS', 'WITH', 'PS.company = C.id');
                $queryBuilder->innerJoin(People::class,         'S' , 'WITH', 'S.id = PS.salesman');
                $queryBuilder->innerJoin(PeopleClient::class,   'PC', 'WITH', 'PC.client = myclients.id AND PC.company_id = S.id');
                $queryBuilder->leftJoin (SalesOrder::class,     'jj' , 'WITH', 'jj.client = myclients.id
                                                                             AND   (jj.status NOT IN (:statuses) OR jj.id IS NULL)                                                                             
                                                                             AND   (jj.provider = :provider  OR jj.id IS NULL)');
                $queryBuilder->andWhere('O.provider = :provider');
                $queryBuilder->andWhere('S.id IN (:my_companies)');
                $queryBuilder->andWhere('O.status NOT IN (:statuses)');
                $queryBuilder->groupBy('myclients.id');
                $queryBuilder->having('COUNT(jj.id) = :count');

                $queryBuilder->setParameter('provider', $provider);
                $queryBuilder->setParameter('statuses', $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open','canceled']]));
                $queryBuilder->setParameter('count'   , 0);
                $queryBuilder->setParameter('my_companies', $this->getMyCompanies($salesman));

            break;

            case 'new':

                $queryBuilder->innerJoin(SalesOrder::class,     'O'  , 'WITH', 'O.client = myclients.id');
                $queryBuilder->innerJoin(People::class,         'C'  , 'WITH', 'C.id = O.provider');
                $queryBuilder->innerJoin(PeopleSalesman::class, 'kkk', 'WITH', 'kkk.company = C.id');
                $queryBuilder->innerJoin(People::class,         'S'  , 'WITH', 'S.id = kkk.salesman');
                $queryBuilder->innerJoin(PeopleClient::class,   'PC' , 'WITH', 'PC.client = myclients.id AND PC.company_id = S.id');

                $queryBuilder->andWhere('O.provider = :provider');
                $queryBuilder->andWhere('S.id IN (:my_companies)');
                $queryBuilder->andWhere('O.status NOT IN (:statuses)');
                $queryBuilder->andWhere('O.orderDate    BETWEEN :from_date AND :to_date');
                $queryBuilder->andWhere('myclients.registerDate BETWEEN :from_date AND :to_date');

                $queryBuilder->setParameter('provider'    , $provider);
                $queryBuilder->setParameter('statuses'    , $this->getEntityManager()->getRepository(Status::class)->findBy(['realStatus' => ['open','canceled']]));
                $queryBuilder->setParameter('from_date'   , $fromDate);
                $queryBuilder->setParameter('to_date'     , $toDate);
                $queryBuilder->setParameter('my_companies', $this->getMyCompanies($salesman));

            break;

            default:
                $queryBuilder->andWhere('myclients.id = 0');
            break;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function getMyCompanies(People $salesman): array
    {
        $sql = "
            SELECT
              pee.company_id
            FROM people_employee pee
            WHERE
              pee.employee_id = :employee_id
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('company_id', 'company_id', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('employee_id', $salesman->getId());

        return $nqu->getArrayResult();
    }
}
