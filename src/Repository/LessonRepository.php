<?php

namespace App\Repository;

use ControleOnline\Entity\Lesson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    /**
     * @param $peopleId
     *
     * @return Lesson[]
     *
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function findLessonsByPeople($peopleId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'SELECT l.*, lc.category
            FROM lessons l
            INNER JOIN people_team pt ON pt.people_id=:people_id

            INNER JOIN school_class sc ON sc.team_id=pt.team_id

            INNER JOIN school_class_lessons scl on sc.id = scl.school_class_id AND l.id = scl.lesson_id

            INNER JOIN lesson_category lc on l.lesson_category_id = lc.id

            INNER JOIN people_team pt_professional ON pt_professional.team_id=sc.team_id AND pt_professional.people_type="professional"
            INNER JOIN people p_professional ON p_professional.id=pt_professional.people_id

            INNER JOIN people_team pt_student ON pt_student.team_id=sc.team_id AND pt_student.people_type="student"
            INNER JOIN people p_student ON p_student.id=pt_student.people_id

            INNER JOIN school_class_status scs ON scs.id=sc.school_class_status_id
            WHERE sc.team_id = pt.team_id
            GROUP BY l.id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['people_id' => $peopleId]);

        return $stmt->fetchAll();
    }
}
