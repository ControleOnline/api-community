<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use App\Entity\Import;

class ImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Import::class);
    }
    public function getAllImports(
        array $search,
        ?array $paginate = null,
        bool $isCount = false,
        array $orderBy = ['column' => 'id', 'orientation' => 'desc']
    ) {
        /**
         * Build import query
         */

        $sql = "";

        if ($isCount) {
            $sql .= 'SELECT COUNT(DISTINCT i.id) AS total';
        } else {
            $sql .= 'SELECT DISTINCT';

            $sql .= ' i.id,';
            $sql .= ' i.import_type,';
            $sql .= ' i.status,';
            $sql .= ' i.name,';
            $sql .= ' i.file_id,';
            $sql .= ' i.file_format,';
            $sql .= ' i.people_id,';
            $sql .= ' i.feedback,';
            $sql .= ' i.upload_date';
        }

        $sql .= ' FROM imports as i';

        $where = [];



        if (isset($search['tableParam']) && $search['tableParam']) {
            $condition = " i.name = '" . $search['tableParam'] . "'";
            $where[] = $condition;
        }



        if (!is_numeric($search['status'])) {
            $condition = " i.status IN('" . $search['status'] . "')";
            $where[] = $condition;
        } elseif ($search['status'] == -1) {
            $search['status'] = '"waiting","importing"';
            $condition = " i.status IN('" . $search['status'] . "')";
            $where[] = $condition;
        }



        if (isset($search['import_type']) && $search['import_type']) {
            $condition = " i.import_type = '" . $search['import_type'] . "'";
            $where[] = $condition;
        }


        if (!empty($search['import_type']) ){
            $condition = " i.import_type = '" . $search['import_type'] . "'";
            $where[] = $condition;
        }


        if (!empty($where)) {
            $sql .= " WHERE (" . implode(') AND (', $where) . ")";
        }

        if ($isCount === false) {
            $sql .= sprintf(' ORDER BY %s %s', $orderBy['column'], $orderBy['orientation']);
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
            $rsm->addScalarResult('import_type', 'importType', 'string');
            $rsm->addScalarResult('status', 'status', 'string');
            $rsm->addScalarResult('name', 'Name', 'string');
            $rsm->addScalarResult('file_id', 'fileId', 'integer');
            $rsm->addScalarResult('file_format', 'fileFormat', 'string');
            $rsm->addScalarResult('people_id', 'peopleId', 'integer');
            $rsm->addScalarResult('feedback', 'feedback', 'string');
            $rsm->addScalarResult('upload_date', 'uploadDate', 'string');
        }

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $result = $nqu->getArrayResult();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? $result[0]['total'] : $result;
    }
}
