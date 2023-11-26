<?php


namespace App\Repository;


use App\Entity\SchoolClass;
use App\Entity\StudentProficiency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;

class StudentProficiencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProficiency::class);
    }

    /**
     * @param $studentId
     * @return array
     * @throws DBALException
     */
    public function findStudentProficiencyDone($studentId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'SELECT
                (SELECT SUM(proficient) as total_points FROM lessons) AS total,
                SUM(proficiencies.lesson_points) AS done
             FROM (
                SELECT
                   IF((SELECT l.proficient FROM lessons l WHERE l.id = sp.lesson_id) > 0, 1, 0) AS lesson_points,
                   sp.lesson_id,
                   MAX(sp.proficiency_date)
                FROM student_proficiency sp
                WHERE sp.student_id = :student_id AND sp.proficiency = "Proficiency"
                GROUP BY sp.lesson_id, sp.student_id) AS proficiencies;';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);

        return $stmt->fetch();
    }
}
