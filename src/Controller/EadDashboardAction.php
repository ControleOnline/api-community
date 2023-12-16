<?php

namespace App\Controller;

use ControleOnline\Entity\SchoolClass;
use ControleOnline\Entity\StudentProficiency;
use DateTime;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class EadDashboardAction.
 *
 * @Route("/ead/dashboard")
 */
class EadDashboardAction extends AbstractController
{
    public $em;

    /**
     * @Route("/upcoming")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws DBALException
     */
    public function getUpcomingClasses(Request $request): JsonResponse
    {
        try {
            $peopleId = $request->query->get('people_id');
            $date = $request->query->get('date');

            if (!is_numeric($peopleId) || $peopleId <= 0) {
                return $this->json([
                    'response' => [
                        'data' => [],
                        'count' => 0,
                        'error' => 'Invalid people id',
                        'success' => false,
                    ],
                ]);
            }

            $this->em = $this->getDoctrine()->getManager();

            $upcomingClasses = $this->getDoctrine()
                ->getRepository(SchoolClass::class)
                ->findUpcomingClasses($peopleId, $date);

            return $this->json([
                'response' => [
                    'data' => $upcomingClasses,
                    'count' => count($upcomingClasses),
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (Exception $e) {
            return $this->json($e->getMessage());
        }
    }

    /**
     * @Route("/average")
     *
     * @throws DBALException
     */
    public function getAverage(Request $request): JsonResponse
    {
        try {
            $peopleId = $request->query->get('people_id');
            if (!is_numeric($peopleId) || $peopleId <= 0) {
                return $this->json([
                    'response' => [
                        'data' => [],
                        'count' => 0,
                        'error' => 'Invalid people id',
                        'success' => false,
                    ],
                ]);
            }

            $this->em = $this->getDoctrine()->getManager();

            $studentProficiencyDone = $this->getDoctrine()
                ->getRepository(StudentProficiency::class)
                ->findStudentProficiencyDone($peopleId);

            $status = $this->getDoctrine()
                ->getRepository(SchoolClass::class)
                ->findAverage($peopleId);

            $proficiency = $studentProficiencyDone['total'] ? (int) $studentProficiencyDone['done'] / (int) $studentProficiencyDone['total'] : 0;

            $missed = 0;
            $given = 0;

            foreach ($status as $item) {
                if ('Missed' === $item['lesson_real_status']) {
                    $missed += (int) $item['amount'];
                } elseif ('Given' === $item['lesson_real_status']) {
                    $given += (int) $item['amount'];
                }
            }

            $givenMissed = $given + $missed;
            $attendance = 0 === $givenMissed ? 0 : ($given / $givenMissed);
            $average = ($proficiency + $attendance) / 2;

            $response = [
                [
                    'description' => 'IP Language Proficiency',
                    'value' => $proficiency,
                    'percentage' => number_format($proficiency * 100, 0).'%',
                ],
                [
                    'description' => 'Attendance',
                    'value' => $attendance,
                    'percentage' => number_format($attendance * 100, 0).'%',
                ],
                [
                    'description' => 'Average Score',
                    'value' => $average,
                    'percentage' => number_format($average * 100, 0).'%',
                ],
            ];

            return $this->json([
                'response' => [
                    'data' => $response,
                    'count' => count($response),
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (Exception $e) {
            return $this->json($e->getMessage());
        }
    }

    /**
     * @Route("/presence")
     */
    public function getPresence(Request $request): JsonResponse
    {
        try {
            $peopleId = $request->query->get('people_id');
            if (!is_numeric($peopleId) || $peopleId <= 0) {
                return $this->json([
                    'response' => [
                        'data' => [],
                        'count' => 0,
                        'error' => 'Invalid people id',
                        'success' => false,
                    ],
                ]);
            }

            $this->em = $this->getDoctrine()->getManager();

            $presences = $this->getDoctrine()
                ->getRepository(SchoolClass::class)
                ->findDetailedPresence($peopleId);

            $total = 0;

            foreach ($presences as $presence) {
                $total += $presence['amount'];
            }

            for ($i = 0, $iMax = count($presences); $i < $iMax; ++$i) {
                $presences[$i]['percentage'] = $presences[$i]['amount'] / $total;
            }

            return $this->json([
                'response' => [
                    'data' => $presences,
                    'count' => count($presences),
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (Exception $e) {
            return $this->json($e->getMessage());
        }
    }

    /**
     * @Route("/checkin")
     */
    public function doCheckin(Request $request): JsonResponse
    {
        try {
            $req = json_decode($request->getContent(), false);

            $schoolClassId = $req->school_class_id;
            $lessonStart = new DateTime($req->lesson_start);

            if (!is_numeric($schoolClassId) || $schoolClassId <= 0) {
                return $this->json([
                    'response' => [
                        'data' => [],
                        'count' => 0,
                        'error' => 'Invalid people id',
                        'success' => false,
                    ],
                ]);
            }

            $this->em = $this->getDoctrine()->getManager();

            $SchoolClass = $this->em->getRepository(SchoolClass::class)->findOneBy(['id' => $schoolClassId]);

            $SchoolClass->setLessonStart($lessonStart);

            $this->em->persist($SchoolClass);

            $this->em->flush();

            return $this->json([
                'response' => [
                    'data' => $SchoolClass,
                    'count' => 0,
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json($e->getMessage());
        }
    }
}
