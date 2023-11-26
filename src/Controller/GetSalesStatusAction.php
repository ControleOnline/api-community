<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\SalesOrder as Order;
use App\Entity\Quotation;
use \App\Entity\People;
use App\Entity\QuoteDetail;
use App\Entity\CarrierIntegration;

class GetSalesStatusAction
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
     * @var \App\Repository\QuotationRepository
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

        $productType = $data->getProductType();

        /**
         * @var \App\Entity\SalesOrderInvoiceTax $invoice
         */

        $invoiceTax = [];
        foreach ($data->getInvoiceTax() as $invoice) {
            $invoiceTax[] = [
                'id'            => $invoice->getInvoiceTax()->getId(),
                'invoiceNumber' => $invoice->getInvoiceTax()->getInvoiceNumber(),
                'invoiceType'   => $invoice->getInvoiceType(),
            ];
        }

        $purchasingOrderPrice = null;
        $purchasingOrderId = null;

        $purchasingOrder = $this->manager->getRepository(Order::class)
            ->findBy([
                'mainOrder' => $orderId,
                'orderType' => 'purchase'
            ]);

        if (!empty($purchasingOrder)) {
            $purchasingOrderPrice = $purchasingOrder[0]->getPrice();
            $purchasingOrderId = $purchasingOrder[0]->getId();
        }




        $childOrders = [];
        foreach ($this->manager->getRepository(Order::class)
            ->findBy([
                'mainOrder' => $orderId,
                'orderType' => 'sale'
            ]) as $childOrder) {
            $childOrders[] = [
                'id'            => $childOrder->getId()
            ];
        }


        $comissionOrders = [];
        foreach ($this->manager->getRepository(Order::class)
            ->findBy([
                'mainOrder' => $orderId,
                'orderType' => 'comission'
            ]) as $comissionOrder) {
            $comissionOrders[] = [
                'id'            => $comissionOrder->getId()
            ];
        }
        $royaltiesOrders = [];
        foreach ($this->manager->getRepository(Order::class)
            ->findBy([
                'mainOrder' => $orderId,
                'orderType' => 'royalties'
            ]) as $royaltiesOrder) {
            $royaltiesOrders[] = [
                'id'            => $royaltiesOrder->getId()
            ];
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

            if ($details && !empty($details) && $purchasingOrderPrice) {
                $correctPercentage = $details[0]->getPrice() > 0 ? $details[0]->getPrice() : 25;
                $correctMinimum = $details[0]->getMinimumPrice();
            }
            /**
             * Valor por fora
             */
            $correctValue =  $correctPercentage > 0 ? ($data->getPrice() * ($correctPercentage / 100)) : 0;
            $realPecentage =  $data->getPrice() > 0 ? ((($data->getPrice() -  $purchasingOrderPrice) / $data->getPrice()) * 100) : 0;

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

        // order package

        /**
         * @var \App\Entity\OrderPackage $package
         */

        $pkgTotal = $data->getOrderPackage() ? 0 : 1;
        $orderPackages = [];
        foreach ($data->getOrderPackage() as $package) {
            $pkgTotal += $package->getQtd();
            $orderPackages[] = [
                'qtd'    => $package->getQtd(),
                'weight' => str_replace('.', ',', $package->getWeight()) . ' kg',
                'height' => str_replace('.', ',', $package->getHeight() * 100) . ' Centímetros',
                'width'  => str_replace('.', ',', $package->getWidth()  * 100) . ' Centímetros',
                'depth'  => str_replace('.', ',', $package->getDepth()  * 100) . ' Centímetros',
            ];
        }

        $order = [
            '@context'  => '/contexts/SalesOrder',
            '@id'       => sprintf('/sales/orders/%s', $orderId),
            'id'        => $orderId,
            '@type'     => 'SalesOrder',
            'app'    => $data->getApp(),
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
            'carrier' => [
                'id' => $data->getQuote() ? $data->getQuote()->getCarrier()->getId() : null,
                'name' => $data->getQuote() ? $data->getQuote()->getCarrier()->getName() : null,
                'alias' => $data->getQuote() ? $data->getQuote()->getCarrier()->getAlias() : null
            ],
            'status' => [
                '@id'        => sprintf('/statuses/%s', $data->getStatus()->getId()),
                'id'         => $data->getStatus()->getId(),
                'status'     => $data->getStatus()->getStatus(),
                'realStatus' => $data->getStatus()->getRealStatus(),
                'color'      => $data->getStatus()->getColor(),
            ],
            'contract'           => $data->getContract() ? $data->getContract()->getId() : null,
            'order_packages'     => $orderPackages,
            'total_packages'     => $pkgTotal,
            'price'              => $data->getPrice(),
            'other_informations' => $data->getOtherInformations(true),
            'realPecentage'      => $realPecentage ?: 0,
            'purchasingPrice'    => $purchasingOrderPrice,
            'correctValue'       => $correctValue, //$correctMinimum > $correctValue ? $correctMinimum : $correctValue,
            'correctPercentage'  => $correctPercentage ?: 0,
            'purchasingOrderId'  => $purchasingOrderId,
            'mainOrderId'        => $data->getMainOrderId(),
            'childOrders'        => $childOrders,
            'invoiceTax'         => $invoiceTax,
            'comissionOrders'    => $comissionOrders,
            'royaltiesOrders'    => $royaltiesOrders,
            'integrationType'    => $integrationType,
            'productType'        => $productType,
            'deliveryDueDate'    => $this->getDeliveryDueDate($data),
            'alterDate'          => $data->getAlterDate()->format('Y-m-d H:m:i'),
            'orderDate'          => $data->getOrderDate()->format('Y-m-d H:m:i'),
            'estimatedParkingDate'          => $data->getEstimatedParkingDate(),
            'parkingDate'          => $data->getParkingDate(),
        ];
        return new JsonResponse($order);
    }

    private function getDeliveryDueDate(Order $order)
    {
        $deliveryDate  = null;
        /**
         * @var \DateTime $lastAlterDate
         */
        if (!$order->getParkingDate())
            return;

        
        $lastAlterDate = new \DateTime($order->getParkingDate()->format('Y-m-d'));
        
        $totalDays    = 0;
        $retrieveDays = 0;
        $deliveryDays = 0;
        
        if ($order->getQuote() === null)
        return $deliveryDate;
        
        // $retrieveDays = $this->quotation->getRetrieveDeadline($order->getQuote());
        $retrieveDays = 5;
        $shippingDays = 5;
        $deliveryDays = $order->getQuote()->getDeadline();
        $totalDays = $retrieveDays + $shippingDays + $deliveryDays;

        $lastAlterDate = new \DateTime(date('Y-m-d', strtotime($lastAlterDate->format('Y-m-d') . ' +' . $totalDays . ' weekdays')));
        // if ($order->getStatus()->getStatus() == 'on the way' || $order->getStatus()->getStatus() ==  'retrieved') {
        //     $lastAlterDate = new \DateTime(date('Y-m-d', strtotime($lastAlterDate->format('Y-m-d') . ' +' . $deliveryDays . ' days')));
        // } else {
        //     $today = new \DateTime('now');
        //     $lastAlterDate = new \DateTime(date('Y-m-d', strtotime($lastAlterDate->format('Y-m-d') . ' +' . $totalDays . ' work days')));
        //     if (strtotime($lastAlterDate->format('Y-m-d')) < strtotime($today->format('Y-m-d'))) {
        //         dd($lastAlterDate);
        //         $lastAlterDate = new \DateTime(date('Y-m-d', strtotime('+' . $totalDays . ' weekdays')));
        //     }
        // }
        
        $deliveryDate = $lastAlterDate->format('d/m/Y');

        return $deliveryDate;
    }
}
