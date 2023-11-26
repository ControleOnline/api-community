<?php

namespace App\Repository;

use App\Entity\OrderLogisticSurveys;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method OrderLogisticSurveys|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderLogisticSurveys|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderLogisticSurveys[]    findAll()
 * @method OrderLogisticSurveys[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderLogisticSurveysRepository extends ServiceEntityRepository
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct($registry, OrderLogisticSurveys::class);
    }

    /**
     * @param $email
     * @return array
     */
    public function getPeopleSurveyorByExactMail($email): array
    {

        $sql = "select p.name, p.id from people as p left join email as m on p.id = m.people_id
                where m.email = :emailParam";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('name', 'name', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $nqu->setParameter('emailParam', $email);

        return $nqu->getResult();

    }

    /**
     * @param $orderId
     * @return array
     */
    public function getCollectionSurveys($orderId): array
    {

        $sql = "select ols.id, ols.token_url, date_format(ols.updated_at, '%d/%m/%Y - %H:%i') as date, p.name as client_name, ols.type_survey,
                    o.product_type as vehicle,
                    ols.status
                from order_logistic_surveys as ols
                left join order_logistic as ol on ols.order_logistic_id = ol.id
                inner join orders o on ol.order_id = o.id
                left join people p on o.client_id = p.id
                where o.id = :orderId order by (ols.updated_at is not null), ols.id desc";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('token_url', 'token_url', 'string');
        $rsm->addScalarResult('date', 'date', 'string');
        $rsm->addScalarResult('client_name', 'client_name', 'string');
        $rsm->addScalarResult('vehicle', 'vehicle', 'string');
        $rsm->addScalarResult('type_survey', 'type_survey', 'string');
        $rsm->addScalarResult('status', 'status', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $nqu->setParameter('orderId', $orderId);

        return $nqu->getResult();

    }

    /**
     * Trás todos os pontos de encontro pelo id da Default Company
     *
     * @param $defaultCompanyId
     * @return array
     */
    public function getPeopleProfessionalByDefaultCompanyId($defaultCompanyId): array
    {

        $sql = "select pt.professional_id, a.id as address_id, p.alias, c.city, d.district, s2.UF
                from people as p
                     left join people_professional as pt on p.id = pt.professional_id
                     inner join address a on p.id = a.people_id
                     left join street s on a.street_id = s.id
                     left join district d on s.district_id = d.id
                     left join city c on d.city_id = c.id
                     left join state s2 on c.state_id = s2.id
                where pt.company_id = :defaultCompanyId order by p.alias";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('professional_id', 'professional_id', 'integer');
        $rsm->addScalarResult('address_id', 'address_id', 'integer');
        $rsm->addScalarResult('alias', 'alias', 'string');
        $rsm->addScalarResult('district', 'district', 'string');
        $rsm->addScalarResult('city', 'city', 'string');
        $rsm->addScalarResult('UF', 'UF', 'string');
        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $nqu->setParameter('defaultCompanyId', $defaultCompanyId);

        return $nqu->getResult();

    }

    // /**
    //  * @return TasksSurveys[] Returns an array of TasksSurveys objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TasksSurveys
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
