<?php
namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\People;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchLessonsPeopleAction extends AbstractController
{
    public function __invoke(People $data, Request $request): JsonResponse
    {
        try {
            $repository = $this->getDoctrine()->getRepository(Lesson::class);

            $classes = $repository->findLessonsByPeople($data->getId());

            return $this->json([
                'response' => [
                    'data' => $classes,
                    'count' => count($classes),
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return $this->json($e->getMessage());
        } catch (Exception $e) {
            return $this->json($e->getMessage());
        }
    }
}
