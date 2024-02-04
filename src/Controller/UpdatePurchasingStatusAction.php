<?php

namespace App\Controller;

use ControleOnline\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\PurchasingOrder as Order;
use Symfony\Component\HttpFoundation\Request;

class UpdatePurchasingStatusAction
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
     * Update Order Status actions
     *
     * @var array
     */
    private $updates = [
        'add_retrieve'
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(Order $data, Request $request): Order
    {
        $this->request = $request;

        try {

            $payload = json_decode($this->request->getContent(), true);

            if (!isset($payload['update']) || empty($payload['update']))
                throw new \Exception('Update param is not defined');

            if (!in_array($payload['update'], $this->updates))
                throw new \Exception(sprintf('Update "%s" is not valid', $payload['update']));

            switch ($payload['update']) {
                case 'add_retrieve':
                    if ($data->getStatus()->getStatus() != 'waiting retrieve')
                        throw new \Exception('Order status can not be modified');                    

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'retrieved']);
                    if ($status === null)
                        throw new \Exception('Order status "retrieved" not found');

                    $data->setStatus  ($status);
                    $data->setNotified(0);
                break;                
            }

        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }

        return $data;
    }
}
