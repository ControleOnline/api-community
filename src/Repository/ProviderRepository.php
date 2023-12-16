<?php

namespace App\Repository;

use ControleOnline\Entity\Provider;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method Provider|null find($id, $lockMode = null, $lockVersion = null)
 * @method Provider|null findOneBy(array $criteria, array $orderBy = null)
 * @method Provider[]    findAll()
 * @method Provider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProviderRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Provider::class);
  }

  public function getAllProviders(array $search, ?array $paginate = null, bool $isCount = false)
  {
    /**
     * Build people query
     */

    if ($isCount) {
      $sql = 'SELECT COUNT(DISTINCT pro.id) AS total';
    } else {
      $sql  = 'SELECT DISTINCT';
      $sql .= ' pro.id,';
      $sql .= ' pro.name,';
      $sql .= ' pro.alias,';
      $sql .= ' pec.id AS people_provider_id,';
      $sql .= ' IF(CHAR_LENGTH(doc.document) = 14, doc.document, CONCAT("0", doc.document)) AS document,';
      $sql .= ' ema.email,';
      $sql .= ' CONCAT(pho.ddd, pho.phone) AS phone,';
      $sql .= ' pec.enable,';
      $sql .= ' ima.url AS file,';
      $sql .= ' ima.id AS fileId';
    }

    $sql .= ' FROM people as pro';

    $sql .= ' INNER JOIN people_provider pec ON pec.provider_id = pro.id';
    $sql .= ' LEFT JOIN document doc ON doc.id = (SELECT id FROM document WHERE people_id = pro.id LIMIT 1)';
    $sql .= ' LEFT JOIN email ema ON ema.id = (SELECT id FROM email WHERE people_id = pro.id LIMIT 1)';
    $sql .= ' LEFT JOIN phone pho ON pho.id = (SELECT id FROM phone WHERE people_id = pro.id LIMIT 1)';
    $sql .= ' LEFT JOIN files ima ON ima.id = pro.image_id';

    // $sql .= ' WHERE pro.people_type = "J"';

    // search

    if (isset($search['search'])) {
      $sql .= ' AND (';
      $sql .= ' pro.name LIKE \'%'     . $search['search'] . '%\' OR';
      $sql .= ' pro.alias LIKE \'%'    . $search['search'] . '%\' OR';
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
      $rsm->addScalarResult('people_provider_id', 'people_provider_id', 'integer');
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
