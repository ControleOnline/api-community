<?php


namespace App\Controller;

use ControleOnline\Entity\People;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class EadDashboardAction
 * @package App\Controller
 * @Route("/lesson")
 */
class GetMyLessonsAction extends AbstractController
{
    public $em;

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/my")
     */
    public function getUpcomingLesson(Request $request): JsonResponse
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

            $People = $this->em->getRepository(People::class)->findOneBy(['id' => $peopleId]);

            $lessons = $People->getLessons();
            return $this->json([
                'response' => [
                    'data' => $lessons,
                    'count' => count($lessons),
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->json($e->getMessage());
        }
    }
}
