<?php

namespace App\Controller;

use ControleOnline\Entity\DeliveryTax;
use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Quotation;
use ControleOnline\Entity\QuoteDetail;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\SalesOrderInvoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Library\Itau\ItauClient;
use App\Library\Quote\View\Group   as TaxesView;
use App\Library\Quote\Core\DataBag as TaxesData;

class UpdateQuotationAddTaxAction
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

    private $total = 0;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(Quotation $data, Request $request): Quotation
    {

        try {
            $this->manager->getConnection()->beginTransaction();

            $payload = json_decode($request->getContent(), true);

            if (!isset($payload['id']) || empty($payload['id']))
                throw new \Exception('Tax ID param is not defined');

            if (!isset($payload['value']) &&  !isset($payload['price'])) {
                throw new \Exception('Tax value param is not defined');
            } else {
                if (!is_numeric($payload['value']) && !is_numeric($payload['price']))
                    throw new \Exception('Tax value is not a valid number');
            }

            $deliveryTax = $this->manager->getRepository(DeliveryTax::class)->find($payload['id']);
            if ($deliveryTax === null)
                throw new \Exception('Tax was not found');

            $quoteDetail = $this->manager->getRepository(QuoteDetail::class)
                ->findOneBy([
                    'quote'       => $data,
                    'deliveryTax' => $deliveryTax
                ]);

            $this->createQuotationDetails($data, $deliveryTax, $payload['value'], $quoteDetail, $payload['taxProfit'], $payload['price']);
            $this->recalculateOrderPrice($data);
            $this->recalculateInvoicePrice($data, $payload['value']);
            $this->manager->flush();
            $this->manager->getConnection()->commit();
        } catch (\Exception $e) {

            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }
            echo $e->getMessage();
        }



        return   $this->manager->getRepository(Quotation::class)->find($data->getId());
    }

    /**
     * Verify if invoice is paid or has billet
     *
     * @param  \ControleOnline\Entity\ReceiveInvoice $invoice
     * @param  \ControleOnline\Entity\SalesOrder     $order
     * @return boolean
     */
    private function orderInvoiceIsPaidOrHasBillet(Invoice $invoice, Order $order): bool
    {
        if ($invoice->getStatus()->getStatus() == 'paid')
            return true;

        if ($this->isBilletCreated($invoice, $order))
            return true;

        return false;
    }


    private function createNewInvoice(\ControleOnline\Entity\SalesOrder $order, float $value)
    {
        if (!in_array($order->getStatus()->getStatus(), [
            'automatic analysis', 'analysis', 'waiting client invoice tax', 'quote', 'canceled', 'expired'
        ])) {
            $newInvoice   = new \ControleOnline\Entity\ReceiveInvoice();
            $newInvoice->setPrice($value);
            $newInvoice->setDueDate(\DateTime::createFromFormat('Y-m-d', date('Y-m-d', strtotime(' +2 Weekdays'))));
            $newInvoice->setStatus(
                $this->manager->getRepository(Status::class)->findOneBy(['status' => 'open'])
            );
            $newInvoice->setNotified(0);
            $this->manager->persist($newInvoice);
            $this->manager->flush($newInvoice);

            $orderInvoice = new SalesOrderInvoice();
            $orderInvoice->setOrder($order);
            $orderInvoice->setInvoice($newInvoice);
            $orderInvoice->setRealPrice($value);
            $this->manager->persist($orderInvoice);
            $this->manager->flush($orderInvoice);
        }
    }

    private function recalculateInvoicePrice(Quotation $quotation, float $value): void
    {
        $quotation =  $this->manager->getRepository(Quotation::class)->find($quotation->getId());
        /**
         * @var \ControleOnline\Entity\SalesOrder $order
         */
        $order   = $quotation->getOrder();

        /**
         * @var \ControleOnline\Entity\ReceiveInvoice $invoice
         */
        $invoice = $order->getInvoice()->first() ? $order->getInvoice()->first()->getInvoice() : null;
        if (count($order->getInvoice()) == 1) {
            if ($invoice->getStatus()->getStatus() == 'open') {
                $order->getInvoice()->first()->setRealPrice($quotation->getTotal());
                $this->manager->persist($order);
                $this->manager->flush($order);
            } else {
                $this->createNewInvoice($order, $value);
            }
        } else {
            $this->createNewInvoice($order, $value);
        }
    }

    /**
     * Recalculate order price
     *
     * @param  Quotation $data
     * @return void
     */
    private function recalculateOrderPrice(Quotation $quotation): void
    {
        $quotation =  $this->manager->getRepository(Quotation::class)->find($quotation->getId());
        /**
         * @var \ControleOnline\Entity\SalesOrder $order
         */
        $order = $quotation->getOrder();

        if ($order->getQuote() === $quotation) {
            $order->setPrice($quotation->getTotal());
            $this->manager->persist($order);
            $this->manager->flush($order);
        }
    }

    private function createQuotationDetails(Quotation $quotation, DeliveryTax $deliveryTax, float $value, $quoteDetail = null, $taxProfit = false, $price = 0)
    {
        $new = false;
                if (!($quoteDetail instanceof QuoteDetail)){
                    $quoteDetail = new QuoteDetail();
                    $new = true;
        }

        $quoteDetail->setQuote($quotation);
        $quoteDetail->setDeliveryTax($deliveryTax);
        $quoteDetail->setTaxName($deliveryTax->getTaxName());
        $quoteDetail->setTaxDescription($deliveryTax->getTaxDescription());
        $quoteDetail->setTaxType($deliveryTax->getTaxType());
        $quoteDetail->setTaxSubtype($deliveryTax->getTaxSubtype());
        $quoteDetail->setMinimumPrice($deliveryTax->getMinimumPrice());
        $quoteDetail->setFinalWeight($deliveryTax->getFinalWeight());
        $quoteDetail->setRegionOrigin($deliveryTax->getRegionOrigin());
        $quoteDetail->setRegionDestination($deliveryTax->getRegionDestination());
        $quoteDetail->setTaxOrder($deliveryTax->getTaxOrder());
        $quoteDetail->setOptional($deliveryTax->getOptional());

        // Usado para desconto
        if ($price != 0) {
            $quoteDetail->setPriceCalculated($price);
        } else {
            $quoteDetail->setPriceCalculated($value);
        }            

        $quoteDetail->setPrice($price > 0 ? $price : $deliveryTax->getPrice());

        $this->manager->persist($quoteDetail);
        $this->manager->flush($quoteDetail);
        if ($new){
                $quotation->addQuoteDetail($quoteDetail);

                $this->manager->persist($quotation);
                $this->manager->flush($quotation);
        }
        // recalculate quotation total        
        $total = 0;

        $taxes = [
            'perce' => [],
            'icms'  => [],
            'ryt'   => [],
            'impt'  => [],
            'mkt'   => [],
            'conv'  => []
        ];
        
        //$quotation =  $this->manager->getRepository(Quotation::class)->find($quotation->getId());
        /**
         * @var \ControleOnline\Entity\QuoteDetail $detail
         */

        foreach ($quotation->getQuoteDetail() as $detail) {
            if ($detail->getTaxType() == 'percentage' && $detail->getTaxSubtype() == 'order') {
                if (in_array(preg_replace('/\s+/', ' ', trim($detail->getTaxName())), ['TAXA DE CONVENIENCIA', 'TAXA DE CONVENIÊNCIA', 'CONVENIÊNCIA', 'CONVENIENCIA'])) {
                    $taxes['conv'][]  = $detail;
                } elseif (in_array($detail->getTaxName(), ['ICMS'])) {
                    $taxes['icms'][]  = $detail;
                } elseif (in_array($detail->getTaxName(), ['IMPOSTO'])) {
                    $taxes['impt'][]  = $detail;
                } elseif (in_array($detail->getTaxName(), ['ROYALTY',])) {
                    $taxes['ryt'][]  = $detail;
                } elseif (in_array($detail->getTaxName(), ['MARKETING'])) {
                    $taxes['mkt'][]  = $detail;
                } else {
                    $taxes['perce'][] = $detail;
                }
            } else {
                $total += $detail->getPriceCalculated();
            }
        }


        /**
         * @var \ControleOnline\Entity\QuoteDetail $detail
         */
        foreach ($taxes['perce'] as $detail) {
            if ($taxProfit) {
                $pc = $this->getPercentageTaxTotal($total, $detail);
                $total += $pc;
                $detail->setPriceCalculated($pc);
                $this->manager->persist($detail);
                $this->manager->flush($detail);
            } else {
                $total += $detail->getPriceCalculated();
            }
        }

        $ntotal = $total;

        /**
         * @var \ControleOnline\Entity\QuoteDetail $detail
         */
        foreach ($taxes['ryt'] as $detail) {
            if ($taxProfit) {
                $pc = $this->getIcmsTaxTotal($ntotal, $detail);
                $total += $pc;
                $detail->setPriceCalculated($pc);
                $this->manager->persist($detail);
                $this->manager->flush($detail);
            } else {
                $total += $detail->getPriceCalculated();
            }
        }

        /**
         * @var \ControleOnline\Entity\QuoteDetail $detail
         */
        foreach ($taxes['mkt'] as $detail) {
            if ($taxProfit) {
                $pc = $this->getIcmsTaxTotal($ntotal, $detail);
                $total += $pc;
                $detail->setPriceCalculated($pc);
                $this->manager->persist($detail);
                $this->manager->flush($detail);
            } else {
                $total += $detail->getPriceCalculated();
            }
        }
        /**
         * @var \ControleOnline\Entity\QuoteDetail $detail
         */
        foreach ($taxes['impt'] as $detail) {
            if ($taxProfit) {
                $pc = $this->getIcmsTaxTotal($total, $detail);
                $total += $pc;
                $detail->setPriceCalculated($pc);
                $this->manager->persist($detail);
                $this->manager->flush($detail);
            } else {
                $total += $detail->getPriceCalculated();
            }
        }

        /**
         * @var \ControleOnline\Entity\QuoteDetail $detail
         */
        foreach ($taxes['icms'] as $detail) {
            if ($taxProfit) {
                $pc = $this->getIcmsTaxTotal($total, $detail);
                $total += $pc;
                $detail->setPriceCalculated($pc);
                $this->manager->persist($detail);
                $this->manager->flush($detail);
            } else {
                $total += $detail->getPriceCalculated();
            }
        }


        /**
         * @var \ControleOnline\Entity\QuoteDetail $detail
         */
        foreach ($taxes['conv'] as $detail) {
            if ($taxProfit) {
                $pc = $this->getIcmsTaxTotal($total, $detail);
                $total += $pc;
                $detail->setPriceCalculated($pc);
                $this->manager->persist($detail);
                $this->manager->flush($detail);
            } else {
                $total += $detail->getPriceCalculated();
            }
        }

        $quotation->setTotal($total);

        $this->total = $total;


        $this->manager->persist($quotation);
        $this->manager->flush($quotation);
    }

    private function getPercentageTaxTotal($total, QuoteDetail $quotation)
    {
        // PERCENTUAL TAXES

        $txTot = ($total / 100) * $quotation->getPrice();
        $txTot = $txTot > $quotation->getMinimumPrice() ? $txTot : $quotation->getMinimumPrice();
        return $txTot;
    }
    private function getIcmsTaxTotal($total, QuoteDetail $quotation)
    {
        $txTot = ($total / ((100 - $quotation->getPrice()) / 100)) - $total;
        return $txTot;
    }

    /**
     *
     * @param  \ControleOnline\Entity\ReceiveInvoice $invoice
     * @param  \ControleOnline\Entity\SalesOrder     $order
     * @return boolean
     */
    private function isBilletCreated(Invoice $invoice, Order $order): bool
    {
        if ($invoice->getOrder()->isEmpty())
            throw new \Exception('Invoice orders not found');

        $configs = $this->getItauConfig($order->getProvider());
        $payment = (new ItauClient($invoice, $configs))->getPayment();

        return $payment->getPaymentType() === 'billet' && $payment->getStatus() === 'created';
    }

    private function getItauConfig(People $people): array
    {
        /**
         * @var \ControleOnline\Repository\ConfigRepository
         */
        $confrepo = $this->manager->getRepository(Config::class);
        $configs  = $confrepo->getItauConfigByPeople($people);

        if ($configs === null)
            return [];

        return $configs;
    }
}
