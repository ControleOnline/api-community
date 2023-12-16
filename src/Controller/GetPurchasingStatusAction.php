<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\PurchasingOrder as Order;
use ControleOnline\Entity\Quotation;
use \ControleOnline\Entity\People;
use ControleOnline\Entity\QuoteDetail;
use ControleOnline\Entity\CarrierIntegration;

class GetPurchasingStatusAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Quotation repository
     *
     * @var \ControleOnline\Repository\QuotationRepository
     */
    private $quotation = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager   = $entityManager;
        $this->quotation = $this->manager->getRepository(Quotation::class);
    }

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        $orderId = $data->getId();
        $order = [];
        $integrationType = null;

        /**
         * @var \ControleOnline\Entity\SalesOrderInvoiceTax $invoice
         */

        $invoiceTax = [];
        foreach ($data->getInvoiceTax() as $invoice) {
            $invoiceTax[] = [
                'id'            => $invoice->getInvoiceTax()->getId(),
                'invoiceNumber' => $invoice->getInvoiceTax()->getInvoiceNumber(),
                'invoiceType'   => $invoice->getInvoiceType(),
            ];
        }

        $mainOrderPrice = null;
        $mainOrderId = null;

        if ($data->getMainOrder()) {
            $maindOrder = $this->manager->getRepository(Order::class)
                ->findBy(array("id" => $data->getMainOrder()->getId()));
        }

        if (!empty($maindOrder)) {
            $mainOrderPrice = $maindOrder[0]->getPrice();
            $mainOrderId = $maindOrder[0]->getId();
        }

        /**
         * @var Quotation quote
         */
        $quote = $data->getQuote();

        /**
         * @var CarrierIntegration carrierIntegration
         */
        $carrierIntegration = null;
        $correctPercentage = 25;
        $correctValue = 0;
        $correctMinimum = 0;
        $realPecentage = 0;

        if (!empty($quote)) {

            $details = $this->manager->getRepository(QuoteDetail::class)->createQueryBuilder('QD')
                ->where('QD.tax_name LIKE :tax_name OR QD.tax_name LIKE :t_n')
                ->andWhere('QD.quote = :quote')
                ->setParameter('tax_name', '%conveniencia%')
                ->setParameter('t_n', '%conveniência%')
                ->setParameter('quote', $quote)
                ->getQuery()
                ->getResult();

            if ($details && !empty($details) && $mainOrderPrice) {
                $correctPercentage = $details[0]->getPrice() > 0 ? $details[0]->getPrice() : 25;
                $correctMinimum = $details[0]->getMinimumPrice();
            }

            if ($mainOrderPrice > 0) {

                /**
                 * Valor por fora
                 */
                $correctValue =  $correctPercentage > 0 ? ($mainOrderPrice * ($correctPercentage / 100)) : 0;
                $realPecentage = $data->getPrice() > 0 ? ((($mainOrderPrice -  $data->getPrice()) / $mainOrderPrice) * 100) : 0;
            }


            /**
             * @var People carrier
             */
            $carrier = $quote->getCarrier();

            /**
             * @var CarrierIntegration carrierIntegration
             */
            $carrierIntegration = $this->manager->getRepository(CarrierIntegration::class)
                ->findOneBy(['carrier' => $carrier]);
        }

        if (!empty($carrierIntegration)) {
            $integrationType = $carrierIntegration->getIntegrationType();
        }
        try {
            $order = [
                '@context'  => '/contexts/PurchasingOrder',
                '@id'       => sprintf('/purchasing/orders/%s', $orderId),
                'id'        => $orderId,
                '@type'     => 'PurchasingOrder',
                'client'    => [
                    '@id'   => sprintf('/people/%s', $data->getClient()->getId()),
                    'id'    => $data->getClient()->getId(),
                    'name'  => $data->getClient()->getName(),
                    'alias' => $data->getClient()->getAlias(),
                ],
                'provider'  => [
                    '@id'   => sprintf('/people/%s', $data->getProvider()->getId()),
                    'id'    => $data->getProvider()->getId(),
                    'name'  => $data->getProvider()->getName(),
                    'alias' => $data->getProvider()->getAlias(),
                ],
                'status' => [
                    '@id'        => sprintf('/statuses/%s', $data->getStatus()->getId()),
                    'id'         => $data->getStatus()->getId(),
                    'status'     => $data->getStatus()->getStatus(),
                    'realStatus' => $data->getStatus()->getRealStatus(),
                    'color'      => $data->getStatus()->getColor(),
                ],
                'other_informations' => $data->getOtherInformations(true),
                'price'           => $data->getPrice(),
                'realPecentage'   => $realPecentage ?: 0,
                'mainPrice'       => $mainOrderPrice,
                'correctValue'    => $correctMinimum > $correctValue ? $correctMinimum : $correctValue,
                'correctPercentage' => $correctPercentage ?: 0,
                'mainOrderId'     => $mainOrderId,
                'invoiceTax'      => $invoiceTax,
                'integrationType' => $integrationType,
                'deliveryDueDate' => $this->getDeliveryDueDate($data),
                'orderDate'       => $data->getOrderDate()->format('Y-m-d H:m:i'),
            ];

            return new JsonResponse($order);
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
