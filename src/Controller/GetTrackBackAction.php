<?php

namespace App\Controller;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderTracking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Entity\Quotation;

class GetTrackBackAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->manager     = $entityManager;
        $this->quotation = $this->manager->getRepository(Quotation::class);
    }

    public function __invoke(Request $request): JsonResponse
    {

        try {

            /**
             * @var string $orderId
             */
            if (!($orderId = $request->get('orderId')))
                throw new BadRequestHttpException('csv orderId is required');

            /**
             * @var string $document
             */
            if (!($document = $request->get('document', null)))
                throw new BadRequestHttpException('document is required');


            /**
             * @var Order $order
             */
            $order = $this->manager->getRepository(Order::class)
                ->findOneBy([
                    "id" => $orderId
                ]);

            if (empty($order))
                throw new BadRequestHttpException('order is not found');

            $tracking = $this->manager->getRepository(OrderTracking::class)
                ->findBy([
                    "order" => $order
                ]);

            $trackingList = [];

            /**
             * @var OrderTracking $tr
             */
            foreach ($tracking as $tr) {
                $trackingList[] = [
                    "id" => $tr->getId(),
                    "city" => $tr->getCidade(),
                    "dateTime" => $tr->getDataHora(),
                    "effectiveDateTime" => $tr->getDataHoraEfetiva(),
                    "description" => $tr->getDescricao(),
                    "type" => $tr->getTipo(),
                    "details" => $tr->getOcorrencia()
                ];
            }

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        "order" => [
                            "id"        => $order->getId(),
                            'origin' => [
                                'city' => $order->getQuotes()->first()->getCityOrigin()->getCity(),
                                'state' => $order->getQuotes()->first()->getCityOrigin()->getState()->getUf()
                            ],
                            'destination' => [
                                'city' => $order->getQuotes()->first()->getCityDestination()->getCity(),
                                'state' => $order->getQuotes()->first()->getCityDestination()->getState()->getUf()
                            ],
                            'orderDate' => $order->getOrderDate(),
                            'alterDate' => $order->getAlterDate(),
                            'deliveryDueDate' => $this->getDeliveryDueDate($order),
                            'status' => [
                                "status" => $order->getStatus()->getStatus(),
                                "real"   => $order->getStatus()->getRealStatus()
                            ]
                        ],
                        "tracking"      => $trackingList,
                        "trackingCount" => count($tracking)
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ]
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

    private function getDeliveryDueDate(Order $order)
    {
        $deliveryDate  = null;

        /**
         * @var \DateTime $lastAlterDate
         */
        $lastAlterDate = new \DateTime($order->getAlterDate()->format('Y-m-d'));

        $totalDays    = 0;
        $retrieveDays = 0;
        $deliveryDays = 0;

        if ($order->getQuote() === null)
            return $deliveryDate;

        $retrieveDays = $this->quotation->getRetrieveDeadline($order->getQuote());
        $deliveryDays = $order->getQuote()->getDeadline();
        $totalDays    = $retrieveDays + $deliveryDays;

        if ($order->getStatus()->getStatus() == 'on the way' || $order->getStatus()->getStatus() ==  'retrieved') {
            $lastAlterDate = new \DateTime(date('Y-m-d', strtotime($lastAlterDate->format('Y-m-d') . ' +' . $deliveryDays . ' days')));
        } else {
            $today = new \DateTime('now');
            $lastAlterDate = new \DateTime(date('Y-m-d', strtotime($lastAlterDate->format('Y-m-d') . ' +' . $totalDays . ' weekdays')));
            if (strtotime($lastAlterDate->format('Y-m-d')) < strtotime($today->format('Y-m-d'))) {
                $lastAlterDate = new \DateTime(date('Y-m-d', strtotime('+' . $totalDays . ' weekdays')));
            }
        }

        $deliveryDate = $lastAlterDate->format('d/m/Y');

        return $deliveryDate;
    }
}
