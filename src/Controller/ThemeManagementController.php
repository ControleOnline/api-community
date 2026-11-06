<?php

namespace App\Controller;

use App\Service\ThemeManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_HUMAN')]
class ThemeManagementController extends AbstractController
{
    public function __construct(private ThemeManagementService $themes) {}

    #[Route('/theme-management', methods: ['GET'])]
    public function catalog(Request $request): JsonResponse
    {
        return $this->json($this->themes->catalog(trim($request->query->getString('domain')), max(1, $request->query->getInt('page', 1)), $request->query->getString('scope', 'companies') === 'global'));
    }

    #[Route('/theme-management/domains/{id}/theme', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function apply(int $id, Request $request): JsonResponse
    {
        $body = $request->toArray();
        if (!isset($body['themeId']) || !is_int($body['themeId']) || $body['themeId'] < 1
            || !array_key_exists('expectedThemeId', $body)
            || ($body['expectedThemeId'] !== null && (!is_int($body['expectedThemeId']) || $body['expectedThemeId'] < 1))) {
            throw new BadRequestHttpException('Informe o tema e o vínculo anterior para confirmar a alteração.');
        }
        return $this->json($this->themes->apply($id, $body['themeId'], $body['expectedThemeId']));
    }

    #[Route('/theme-management/themes/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->themes->delete($id);
        return $this->json(['deletedThemeId' => $id]);
    }
}
