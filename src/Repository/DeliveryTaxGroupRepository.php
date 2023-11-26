<?php

namespace App\Repository;

use App\Entity\DeliveryTaxGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeliveryTaxGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryTaxGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryTaxGroup[]    findAll()
 * @method DeliveryTaxGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryTaxGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryTaxGroup::class);
    }

    public function getAllTaxNamesByGroup(int $groupId)
    {
      $conn = $this->getEntityManager()->getConnection();

      $sql  = 'SELECT DISTINCT tax_name';
      $sql .= ' FROM delivery_tax';
      $sql .= ' WHERE delivery_tax_group_id = :group_id';

      $stmt = $conn->prepare($sql);

      // query params

      $params = [
        'group_id' => $groupId
      ];

      // get all

      $stmt->execute($params);

      return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function updateAllTaxPrices(int $groupId, float $increase, string $taxName = null, int $origin = null, int $destination = null)
    {
      $qb = $this->getEntityManager()->createQueryBuilder();

      $sm = $qb->update('App\Entity\DeliveryTax', 'dtx');

      $sm->set('dtx.price', '(dtx.price * ?1)');

      $sm->where('dtx.groupTax = ?2');

      $sm->setParameter(1, $increase);
      $sm->setParameter(2, $groupId);

      if (is_string($taxName)) {
        $sm->andWhere('dtx.taxName = ?3');
        $sm->setParameter(3, $taxName);
      }

      if (is_int($origin)) {
        $sm->andWhere('dtx.regionOrigin = ?4');
        $sm->setParameter(4, $origin);
      }

      if (is_int($destination)) {
        $sm->andWhere('dtx.regionDestination = ?5');
        $sm->setParameter(5, $destination);
      }

      return $sm->getQuery()->getSingleScalarResult();
    }
}
