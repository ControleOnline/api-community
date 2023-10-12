<?php

namespace App\Repository;

use App\Entity\DeliveryTax;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\DeliveryTaxGroup;

/**
 * @method DeliveryTax|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryTax|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryTax[]    findAll()
 * @method DeliveryTax[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryTaxRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, DeliveryTax::class);
  }


  public function getAllTaxesTypeByGroup(int $groupId, string $taxType, array $regions): array
  {
    $conn = $this->getEntityManager()->getConnection();

    $sql  = 'SELECT *';
    $sql .= ' FROM delivery_tax';
    $sql .= ' WHERE delivery_tax_group_id = :group_id AND people_id IS NULL';

    switch ($taxType) {
      case 'tabela':
        $sql .= ' AND region_origin_id = :origin_id';
        $sql .= ' AND region_destination_id = :dest_id';
        $sql .= ' AND (tax_type = \'fixed\' AND tax_subtype IS NULL)';
        break;

      case 'taxpercentinvoice':
        $sql .= ' AND region_origin_id = :origin_id';
        $sql .= ' AND region_destination_id = :dest_id';
        $sql .= ' AND tax_type = \'percentage\'';
        break;

      default:
        throw new \Exception('Tax type is not valid');
        break;
    }

    $stmt = $conn->prepare($sql);

    // query params

    $params = [
      'group_id' => $groupId
    ];

    switch ($taxType) {
      case 'tabela':
        $params['origin_id'] = $regions['origin'];
        $params['dest_id']   = $regions['destination'];
        break;

      case 'taxpercentinvoice':
        $params['origin_id'] = $regions['origin'];
        $params['dest_id']   = $regions['destination'];
        break;
    }

    // get all

    $stmt->execute($params);

    return $stmt->fetchAll();
  }

  public function insert(array $values)
  {
    $cn = $this->getEntityManager()->getConnection();
    $qb = $cn->createQueryBuilder();

    $_values = [];
    $params  = [];
    foreach ($values as $key => $value) {
      $_values[$key] = ':' . $key;
      $params[$key] = $value;
    }

    $st = $qb->insert('delivery_tax')
      ->values($_values)
      ->setParameters($params);

    $st->execute();

    return $this->getEntityManager()
      ->getConnection()->lastInsertId();
  }
}
