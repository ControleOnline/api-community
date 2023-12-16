<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\PeopleSalesman;


class SearchCustomerSalesmanAction
{
    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $manager   = null;

    /**
     * User entity
     *
     * @var \ControleOnline\Entity\User
     */
    private $user = null;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->manager = $entityManager;
        $this->user    = $security->getUser();
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $document = $request->query->get('document', null);
            if (empty($document)) {
                throw new \InvalidArgumentException('Document param was not defined');
            }

            if (($salesman = $this->manager->getRepository(PeopleSalesman::class)->retrieveSalesmanById($document)) === null) {
                throw new \InvalidArgumentException('O vendedor não foi encontrado');
            }

            return new JsonResponse([
                'response' => [
                    'data'    => $salesman,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);

        } catch (\Exception $e) {

            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);

        }
    }
}
