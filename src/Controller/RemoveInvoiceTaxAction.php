<?php

namespace App\Controller;

use ControleOnline\Entity\InvoiceTax;
use ControleOnline\Entity\Order;

use ControleOnline\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class RemoveInvoiceTaxAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['invoiceTax']) || !is_numeric($payload['invoiceTax']))
            throw new \Exception('Invoice Tax is not defined', 400);




        if ($data->getStatus()->getStatus() != 'delivered' && $data->getStatus()->getStatus() != 'on the way')
            throw new \Exception('Status is not valid', 400);

        try {
            $this->manager->getConnection()->beginTransaction();
            $puschasingOrder = $this->manager->getRepository(Order::class)->findOneBy(['mainOrder' => $data->getId()]);
            if ($puschasingOrder)
                $this->manager->remove($puschasingOrder);
            else
                throw new \Exception('Puschasing Order not found', 400);

            $InvoiceTax = $this->manager->getRepository(InvoiceTax::class)->find($payload['invoiceTax']);
            if ($InvoiceTax)
                $this->manager->remove($InvoiceTax);
            else
                throw new \Exception('Sales Invoice Tax not found', 400);


            $data->setStatus($this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting retrieve']));
            $this->manager->persist($data);
            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => ['id' => $data->getId()],
                    'count'   => 1,
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
        //echo $data->getId();
        /*
        // create invoice

        $invoice = $data instanceof Order ? (new Invoice) : (new Invoice);

        $invoice->setPrice  ($payload['price']);
        $invoice->setDueDate(\DateTime::createFromFormat('Y-m-d', $payload['dueDate']));
        $invoice->setStatus (
            $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting payment'])
        );
        $invoice->setNotified(false);

        $this->manager->persist($invoice);

        // create order invoice

        $orderInvoice = $data instanceof Order ? (new OrderInvoice) : (new OrderInvoice);

        $orderInvoice->setOrder  ($data);
        $orderInvoice->setInvoice($invoice);

        $this->manager->persist($orderInvoice);
*/
        return $data;
    }
}
