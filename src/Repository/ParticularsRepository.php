<?php

namespace App\Repository;

use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method Particulars|null find($id, $lockMode = null, $lockVersion = null)
 * @method Particulars|null findOneBy(array $criteria, array $orderBy = null)
 * @method Particulars[]    findAll()
 * @method Particulars[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticularsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Particulars::class);
    }

    public function getParticularsByPeopleAndFieldType(People $people, array $fieldTypes): array
    {
        $sql = "
            SELECT
                par.id,
                pty.id AS type_id,
                pty.type_value,
                par.particular_value AS value
            FROM particulars par
                INNER JOIN particulars_type pty ON pty.id = par.particulars_type_id
            WHERE
                par.people_id = :people_id AND pty.field_type IN (:field_types)
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id'        , 'id'        , 'integer');
        $rsm->addScalarResult('type_id'   , 'type_id'   , 'integer');
        $rsm->addScalarResult('type_value', 'type_value', 'string');
        $rsm->addScalarResult('value'     , 'value'     , 'string');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('people_id'  , $people->getId());
        $nqu->setParameter('field_types', $fieldTypes);

        return $nqu->getArrayResult();
    }

    public function getParticularsByPeopleAndContext(People $people, string $context): array
    {
        $sql = "
            SELECT
                par.id,
                pty.id AS type_id,
                pty.type_value,
                par.particular_value AS value
            FROM particulars par
                INNER JOIN particulars_type pty ON pty.id = par.particulars_type_id
            WHERE
                par.people_id = :people_id AND pty.context LIKE :context
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id'        , 'id'        , 'integer');
        $rsm->addScalarResult('type_id'   , 'type_id'   , 'integer');
        $rsm->addScalarResult('type_value', 'type_value', 'string');
        $rsm->addScalarResult('value'     , 'value'     , 'string');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('people_id', $people->getId());
        $nqu->setParameter('context'  , '%' . $context . '%');

        return $nqu->getArrayResult();
    }
}
