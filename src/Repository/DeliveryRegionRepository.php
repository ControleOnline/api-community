<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use App\Entity\DeliveryRegion;
use App\Entity\People;

/**
 * @method DeliveryRegion|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryRegion|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryRegion[]    findAll()
 * @method DeliveryRegion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryRegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryRegion::class);
    }

    public function getAllPeopleRegions(People $people, ?array $search = null, ?array $paginate = null, bool $isCount = false)
    {
      /**
       * Build people query
       */

      if ($isCount) {
          $sql = 'SELECT COUNT(dre.id) AS total';
      }
      else {
          $sql  = 'SELECT';
          $sql .= ' dre.id,';
          $sql .= ' dre.region,';
          $sql .= ' dre.deadline,';
          $sql .= ' dre.retrieve_tax as tax';
      }

      $sql .= ' FROM delivery_region dre';
      $sql .= ' WHERE dre.people_id = :people_id';

      if (!$isCount) {
        $sql .= ' ORDER BY dre.region ASC';
      }

      // search

      if (is_array($search)) {
        if (isset($search['search'])) {
          $sql .= ' AND (';
          $sql .= ' dre.region LIKE \'%' . $search['search'] . '%\'';
          $sql .= ')';
        }
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
        $rsm->addScalarResult('id'      , 'id'      , 'integer');
        $rsm->addScalarResult('region'  , 'region'  , 'string');
        $rsm->addScalarResult('deadline', 'deadline', 'integer');
        $rsm->addScalarResult('tax'     , 'tax'     , 'float');
      }

      $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

      $nqu->setParameter('people_id', $people->getId());

      $result = $nqu->getArrayResult();

      if (empty($result)) {
          return $isCount ? 0 : [];
      }

      return $isCount ? $result[0]['total'] : $result;
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

      $st = $qb->insert('delivery_region')
          ->values       ($_values)
          ->setParameters($params );

      $st->execute();

      return $this->getEntityManager()
        ->getConnection()->lastInsertId();
    }
}
