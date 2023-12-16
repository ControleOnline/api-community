<?php

namespace App\Repository;

use ControleOnline\Entity\SchoolClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class SchoolClassRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolClass::class);
    }

    /**
     * @param $peopleId
     * @param $date
     *
     * @return SchoolClass[]
     *
     * @throws DBALException
     */
    public function findUpcomingClasses($peopleId, $date): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            "SELECT sc.*, p.name AS professional
            FROM school_class sc
                     INNER JOIN people_team pt1 ON pt1.people_id=:people_id
                     INNER JOIN people_team pt ON pt.team_id = sc.team_id AND pt.people_type = 'professional'
                     INNER JOIN people p ON p.id = pt.people_id
            WHERE sc.lesson_start IS NULL
              AND sc.team_id = pt1.team_id
              AND date(sc.start_prevision) = :date
            ORDER BY sc.start_prevision";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['people_id' => $peopleId, 'date' => $date]);

        return $stmt->fetchAll();
    }

    /**
     * @param $peopleId
     *
     * @throws DBALException
     */
    public function findDetailedPresence($peopleId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
        'SELECT count(sc.id) AS amount, scs.*
            FROM school_class_status scs
            LEFT JOIN school_class sc on scs.id = sc.school_class_status_id
            INNER JOIN team t on sc.team_id = t.id
            INNER JOIN people_team pt on t.id = pt.team_id AND pt.people_id=:people_id
            WHERE sc.id > 0
            GROUP BY scs.lesson_status';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['people_id' => $peopleId]);

        return $stmt->fetchAll();
    }

    /**
     * @throws DBALException
     */
    public function findAverage($peopleId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
        'SELECT count(sc.id) AS amount, scs.*
            FROM school_class_status scs
                     LEFT JOIN school_class sc on scs.id = sc.school_class_status_id
                     INNER JOIN team t on sc.team_id = t.id
                     INNER JOIN people_team pt on t.id = pt.team_id AND pt.people_id=:people_id
            WHERE sc.id > 0 AND scs.lesson_real_status IN ("Given", "Missed")
            GROUP BY scs.lesson_real_status, sc.id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['people_id' => $peopleId]);

        return $stmt->fetchAll();
    }

    /**
     * @param $peopleId
     *
     * @return SchoolClass[]
     *
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function findClassesByPeople($peopleId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'SELECT
              sc.*,
              p_professional.name AS professional,
              p_student.name AS student,
              scs.lesson_status,
              scs.lesson_real_status,
              scs.lesson_color,
              scs.generate_payment
            FROM school_class sc
                     INNER JOIN people_team pt ON pt.people_id=:people_id

                     INNER JOIN people_team pt_professional ON pt_professional.team_id=sc.team_id AND pt_professional.people_type="professional"
                     INNER JOIN people p_professional ON p_professional.id=pt_professional.people_id

                     INNER JOIN people_team pt_student ON pt_student.team_id=sc.team_id AND pt_student.people_type="student"
                     INNER JOIN people p_student ON p_student.id=pt_student.people_id

                     INNER JOIN school_class_status scs ON scs.id=sc.school_class_status_id
            WHERE sc.team_id = pt.team_id
            GROUP BY sc.id,sc.start_prevision
            ORDER BY sc.start_prevision';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['people_id' => $peopleId]);

        return $stmt->fetchAll();
    }

    public function getClassesByPeople($peopleId, ?array $search = null, ?array $paginate = null, ?bool $isCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();

        if ($isCount) {
          $sql = 'SELECT COUNT(DISTINCT sc.id) AS total';
        }
        else {
          $sql = 'SELECT sc.*, p_professional.name AS professional, p_student.name AS student, scs.lesson_status, scs.lesson_real_status, scs.lesson_color, scs.generate_payment';
        }

        $sql .= ' FROM school_class sc';

        $sql .= ' INNER JOIN people_team pt ON pt.people_id=:people_id';
        $sql .= ' INNER JOIN people_team pt_professional ON pt_professional.team_id=sc.team_id AND pt_professional.people_type="professional"';
        $sql .= ' INNER JOIN people p_professional ON p_professional.id=pt_professional.people_id';
        $sql .= ' INNER JOIN people_team pt_student ON pt_student.team_id=sc.team_id AND pt_student.people_type="student"';
        $sql .= ' INNER JOIN people p_student ON p_student.id=pt_student.people_id';
        $sql .= ' INNER JOIN school_class_status scs ON scs.id=sc.school_class_status_id';

        $sql .= ' WHERE sc.team_id = pt.team_id';

        // search

        if (is_array($search)) {
          if (isset($search['status'])) {
            $sql .= ' AND scs.id = :status';
          }

          if (isset($search['date'])) {
            $sql .= ' AND (sc.start_prevision BETWEEN :date_start AND :date_end)';
          }

          if (isset($search['from']) && isset($search['to'])) {
            $sql .= ' AND (sc.start_prevision BETWEEN :date_from AND :date_to)';
          }
        }

        if (!$isCount) {
          $sql .= ' GROUP BY sc.id,sc.start_prevision';
          $sql .= ' ORDER BY sc.start_prevision ASC';
        }

        // pagination

        if (is_array($paginate) && !$isCount) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        $stmt = $conn->prepare($sql);

        // query params

        $params = ['people_id' => $peopleId];

        if (is_array($search)) {
          if (isset($search['status'])) {
            $params['status'] = $search['status'];
          }

          if (isset($search['date'])) {
            $params['date_start'] = $search['date'] . ' 00:00:00';
            $params['date_end']   = $search['date'] . ' 23:59:59';
          }

          if (isset($search['from']) && isset($search['to'])) {
            $params['date_from'] = $search['from'] . ' 00:00:00';
            $params['date_to']   = $search['to']   . ' 23:59:59';
          }
        }

        // get all

        $stmt->execute($params);
        $result = $stmt->fetchAll();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }
        return $isCount ? (int) $result[0]['total'] : $result;
    }

    public function getAllProfessionalClasses(?array $search = null, ?array $paginate = null, ?bool $isCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();

        if ($isCount) {
          $sql = 'SELECT COUNT(DISTINCT sc.id) AS total';
        }
        else {
          $sql = 'SELECT sc.id, sc.start_prevision, sc.end_prevision, p_professional.name AS professional, GROUP_CONCAT(DISTINCT CONCAT(p_student.name, \' \', p_student.alias) SEPARATOR \', \') AS student, scs.lesson_status, scs.lesson_real_status, scs.lesson_color';
        }

        $sql .= ' FROM school_class sc';

        $sql .= ' INNER JOIN people_team pt ON pt.team_id = sc.team_id';
        $sql .= ' INNER JOIN people_team pt_professional ON pt_professional.team_id=sc.team_id AND pt_professional.people_type="professional"';
        $sql .= ' INNER JOIN people p_professional ON p_professional.id=pt_professional.people_id';
        $sql .= ' INNER JOIN people_team pt_student ON pt_student.team_id=sc.team_id AND pt_student.people_type="student"';
        $sql .= ' INNER JOIN people p_student ON p_student.id=pt_student.people_id';
        $sql .= ' INNER JOIN school_class_status scs ON scs.id=sc.school_class_status_id';

        if (isset($search['company'])) {
          $sql .= ' INNER JOIN team tea ON tea.id = sc.team_id AND tea.company_team_id = :company_id';
          $sql .= ' INNER JOIN contract con ON con.id = tea.contract_id AND con.contract_status = \'Active\'';
        }

        // search

        if (is_array($search)) {
          $conditions = [];

          if (isset($search['status'])) {
            $conditions[] = 'scs.id = :status';
          }

          if (isset($search['professional'])) {
            $conditions[] = '(CONCAT(p_professional.name, " ", p_professional.alias) LIKE :professional)';
          }

          if (isset($search['student'])) {
            $conditions[] = '(CONCAT(p_student.name, " ", p_student.alias) LIKE :student)';
          }

          if (isset($search['from']) && isset($search['to'])) {
            $conditions[] = '(sc.start_prevision BETWEEN :date_from AND :date_to)';
          }

          if (isset($search['professional_id'])) {
            $conditions[] = 'p_professional.id = :professional_id';
          }

          if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
          }
        }

        if (!$isCount) {
          $sql .= ' GROUP BY sc.id, sc.start_prevision, sc.end_prevision, p_professional.name, scs.lesson_status, scs.lesson_real_status, scs.lesson_color';
          $sql .= ' ORDER BY sc.start_prevision ASC';
        }

        // pagination

        if (is_array($paginate) && !$isCount) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        $stmt = $conn->prepare($sql);

        // query params

        $params = [];

        if (is_array($search)) {
          if (isset($search['status'])) {
            $params['status'] = $search['status'];
          }

          if (isset($search['professional'])) {
            $params['professional'] = $search['professional'] . '%';
          }

          if (isset($search['student'])) {
            $params['student'] = $search['student'] . '%';
          }

          if (isset($search['from']) && isset($search['to'])) {
            $params['date_from'] = $search['from'] . ' 00:00:00';
            $params['date_to']   = $search['to']   . ' 23:59:59';
          }

          if (isset($search['company'])) {
            $params['company_id'] = $search['company'];
          }

          if (isset($search['professional_id'])) {
            $params['professional_id'] = $search['professional_id'];
          }
        }

        // get all

        $stmt->execute($params);
        $result = $stmt->fetchAll();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }
        return $isCount ? (int) $result[0]['total'] : $result;
    }
}
