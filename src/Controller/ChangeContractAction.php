<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Contract;

class ChangeContractAction
{
    private $manager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(Contract $data, Request $request): JsonResponse
    {
        try {
            // Iniciar uma transação
            $this->manager->getConnection()->beginTransaction();

            // Validar se o pedido é do tipo PUT
            if (!$request->isMethod('PUT')) {
                throw new \InvalidArgumentException('Invalid request method. Expected PUT.');
            }

            // Analisar os dados da solicitação JSON
            $requestData = json_decode($request->getContent(), true);

            if (empty($requestData) || !isset($requestData['htmlContent'])) {
                throw new \InvalidArgumentException('Invalid request data. Missing htmlContent.');
            }

            // Atualizar o campo htmlContent no contrato
            $data->setHtmlContent($requestData['htmlContent']);

            // Persistir o contrato
            $this->manager->persist($data);
            $this->manager->flush();

            // Confirmar a transação
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data' => ['contractId' => $data->getId()],
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            // Se ocorrer uma exceção, reverter a transação
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }

            return new JsonResponse([
                'response' => [
                    'data' => null,
                    'error' => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }
}
