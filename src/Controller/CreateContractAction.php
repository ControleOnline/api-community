<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use ControleOnline\Entity\People;
use ControleOnline\Entity\MyContract;
use ControleOnline\Entity\Contract;
use ControleOnline\Entity\MyContractModel;
use ControleOnline\Entity\MyContractPeople;
use ControleOnline\Entity\Order;
use ControleOnline\Repository\ContractRepository;

class CreateContractAction
{

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
    private $request = null;

    /**
     * 
     * @var ContractRepository $contracts
     */
    private $contracts = null;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager   = $manager;
        $this->contracts = $this->manager->getRepository(MyContract::class);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;

        try {
            $payload = json_decode($request->getContent(), true);

            if (!isset($payload["payerId"])) {
                throw new BadRequestHttpException('payerId is required');
            }

            $payerId = $payload["payerId"];

            if (!($providerId = $request->get('provider', null)))
                throw new BadRequestHttpException('providerId is required');

            if (!($orderId = $request->get('order', null)))
                throw new BadRequestHttpException('orderId is required');

            /**
             * @var People $peopleProvider
             */
            $peopleProvider = $this->manager->getRepository(People::class)
                ->findOneBy(array(
                    "id" => $providerId
                ));

            if (empty($peopleProvider))
                throw new BadRequestHttpException('peopleProvider not found');

            /**
             * @var People $peoplePayer
             */
            $peoplePayer = $this->manager->getRepository(People::class)
                ->findOneBy(array(
                    "id" => $payerId
                ));

            if (empty($peoplePayer))
                throw new BadRequestHttpException('peoplePayer not found');

            /**
             * @var Order $order
             */
            $order = $this->manager->getRepository(Order::class)->findOneBy(array(
                "id" => $orderId
            ));

            if (empty($order))
                throw new BadRequestHttpException('order not found');

            $contractModel = $this->manager->getRepository(MyContractModel::class)
                ->findOneBy(array());

            if (empty($contractModel))
                throw new BadRequestHttpException('contractModel not found');

            $newContract = new MyContract();

            $newContract->setContractModel($contractModel);

            $this->manager->getConnection()->beginTransaction();

            $this->manager->persist($newContract);
            $this->manager->flush();
            
            $newContractPeople = new MyContractPeople();

            $newContractPeople->setContract($newContract);
            $newContractPeople->setPeople($peopleProvider);
            $newContractPeople->setPeopleType('Provider');

            $this->manager->persist($newContractPeople);
            $this->manager->flush();

            $newContractPayer = new MyContractPeople();

            $newContractPayer->setContract($newContract);
            $newContractPayer->setPeople($peoplePayer);
            $newContractPayer->setPeopleType('Payer');
            $newContractPayer->setContractPercentage(100);

            $this->manager->persist($newContractPayer);
            $this->manager->flush();
            

            $order->setContract(
                $this->manager->getRepository(Contract::class)
                    ->find($newContract->getId())
            );

            $this->manager->persist($order);
            $this->manager->flush();

            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        "contractId" => $newContract->getId(),
                        "providerId" => $peopleProvider->getId()
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
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
