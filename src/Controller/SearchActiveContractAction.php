<?php

namespace App\Controller;

use App\Entity\ContractPeople;
use App\Entity\Quotation;
use ControleOnline\Entity\User;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\PurchasingOrder AS Order;
use App\Entity\People;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PrestaShopAction
 * @package App\Controller
 * @Route("/contract")
 */
class SearchActiveContractAction extends AbstractController
{
    public $em;

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/active")
     */
    public function getActive(Request $request): JsonResponse
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

            $activeContracts = [];

            foreach ($People->getContracts() as $contract) {
                if ($contract->getContractStatus() === 'Active') {
                    $activeContracts[] = $contract;
                }
            }

            return $this->json([
                'response' => [
                    'data' => $activeContracts,
                    'count' => count($activeContracts),
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
