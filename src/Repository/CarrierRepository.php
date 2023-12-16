<?php

namespace App\Repository;

use ControleOnline\Entity\Carrier;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method Carrier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Carrier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Carrier[]    findAll()
 * @method Carrier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarrierRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Carrier::class);
  }

  public function getAllCarriers(array $search, ?array $paginate = null, bool $isCount = false)
  {
    /**
     * Build people query
     */

    if ($isCount) {
      $sql = 'SELECT COUNT(DISTINCT car.id) AS total';
    } else {
      $sql  = 'SELECT DISTINCT';
      $sql .= ' car.id,';
      $sql .= ' car.name,';
      $sql .= ' car.alias,';
      $sql .= ' pec.id AS people_carrier_id,';
      $sql .= ' IF(CHAR_LENGTH(doc.document) = 14, doc.document, CONCAT("0", doc.document)) AS document,';
      $sql .= ' ema.email,';
      $sql .= ' CONCAT(pho.ddd, pho.phone) AS phone,';
      $sql .= ' pec.enable,';
      $sql .= ' ima.url AS file,';
      $sql .= ' ima.id AS fileId';
    }

    $sql .= ' FROM people as car';

    $sql .= ' INNER JOIN people_carrier pec ON pec.carrier_id = car.id';
    $sql .= ' LEFT JOIN document doc ON doc.id = (SELECT id FROM document WHERE document_type_id = 3 AND people_id = car.id LIMIT 1)';
    $sql .= ' LEFT JOIN email ema ON ema.id = (SELECT id FROM email WHERE people_id = car.id LIMIT 1)';
    $sql .= ' LEFT JOIN phone pho ON pho.id = (SELECT id FROM phone WHERE people_id = car.id LIMIT 1)';
    $sql .= ' LEFT JOIN files ima ON ima.id = car.image_id';

    $sql .= ' WHERE car.people_type = "J"';

    // search

    if (isset($search['search'])) {
      $sql .= ' AND (';
      $sql .= ' car.name LIKE \'%'     . $search['search'] . '%\' OR';
      $sql .= ' car.alias LIKE \'%'    . $search['search'] . '%\' OR';
      $sql .= ' doc.document LIKE \'%' . $search['search'] . '%\' OR';
      $sql .= ' ema.email LIKE \'%'    . $search['search'] . '%\' OR';
      $sql .= ' pho.phone LIKE \'%'    . $search['search'] . '%\'';
      $sql .= ')';
    }

    if (isset($search['company'])) {
      if ($search['company'] instanceof People) {
        $sql .= ' AND pec.company_id = :company_id';
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
    } else {
      $rsm->addScalarResult('id', 'id', 'integer');
      $rsm->addScalarResult('name', 'name', 'string');
      $rsm->addScalarResult('alias', 'alias', 'string');
      $rsm->addScalarResult('document', 'document', 'string');
      $rsm->addScalarResult('email', 'email', 'string');
      $rsm->addScalarResult('phone', 'phone', 'string');
      $rsm->addScalarResult('enable', 'enable', 'boolean');
      $rsm->addScalarResult('file', 'image', 'string');
      $rsm->addScalarResult('fileId', 'imageId', 'string');
      $rsm->addScalarResult('people_carrier_id', 'people_carrier_id', 'integer');
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
}
