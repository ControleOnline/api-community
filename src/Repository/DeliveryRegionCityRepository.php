<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use ControleOnline\Entity\DeliveryRegionCity;

/**
 * @method DeliveryRegionCity|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryRegionCity|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryRegionCity[]    findAll()
 * @method DeliveryRegionCity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryRegionCityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryRegionCity::class);
    }

    public function getAllRegionCitiesByRegionId(int $regionId)
    {
      $sql  = 'SELECT';
      $sql .= ' cit.id,';
      $sql .= ' cit.city,';
      $sql .= ' sta.uf';

      $sql .= ' FROM delivery_region dre';

      $sql .= ' INNER JOIN delivery_region_city dci ON dci.delivery_region_id = dre.id';
      $sql .= ' INNER JOIN city cit ON cit.id = dci.city_id';
      $sql .= ' INNER JOIN state sta ON sta.id = cit.state_id';

      $sql .= ' WHERE dre.id = :region_id';

      // mapping

      $rsm = new ResultSetMapping();

      $rsm->addScalarResult('id'  , 'id'  , 'integer');
      $rsm->addScalarResult('city', 'city', 'string');
      $rsm->addScalarResult('uf'  , 'uf'  , 'string');

      $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

      $nqu->setParameter('region_id', $regionId);

      $result = $nqu->getArrayResult();

      return empty($result) ? [] : $result;
    }

    public function insert(array $values)
    {
      $cn = $this->getEntityManager()->getConnection();
      $qb = $cn->createQueryBuilder();

      $_values = [];
      $params  = [];
      foreach ($values as $key => $value) {
        $_values[$key] = ':' . $key;
        $params [$key] = $value;
      }

      $st = $qb->insert('delivery_region_city')
          ->values       ($_values)
          ->setParameters($params );

      $st->execute();

      return $this->getEntityManager()
        ->getConnection()->lastInsertId();
    }
}
