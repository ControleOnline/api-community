<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use App\Entity\MyContract;

class ChangeContractStatusAction {

    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;
        
        try {
            $this->manager->getConnection()->beginTransaction();
            
            /**
             * @var string $id
             */
            if (!($id = $request->get('id', null)))
              throw new BadRequestHttpException('id is required');
              
            /**
             * @var string $status
             */
            if (!($status = $request->get('status', null)))
            throw new BadRequestHttpException('status is required');
            
            /**
             * @var MyContract $contract
             */
            $contract = $this->manager->getRepository(MyContract::class)
                ->findOneBy(array('id' => $id));

            if (empty($contract))
                throw new BadRequestHttpException('contract not found');

            $newStatus = ucfirst($status);

            $contract->setContractStatus($newStatus);
            
            $this->manager->persist($contract);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        "contractId" => $contract->getId(),
                        "newStatus"  => $contract->getContractStatus()
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

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