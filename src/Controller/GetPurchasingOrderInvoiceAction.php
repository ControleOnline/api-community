<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\DocumentType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\PurchasingOrder AS Order;
use App\Entity\People;
use App\Entity\Quotation;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

class GetPurchasingOrderInvoiceAction
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
        $order = null;

        if ($data->getQuote() instanceof Quotation) {
            $invoiceTax = [];

            /**
             * @var \ControleOnline\Entity\PurchasingOrderInvoiceTax $invoice
             */
            foreach ($data->getInvoiceTax() AS $invoice) {
                $invoiceTax[] = [
                    'id'             => $invoice->getInvoiceTax()->getId(),
                    'invoice_number' => $invoice->getInvoiceTax()->getInvoiceNumber(),
                    'invoice'        => $invoice->getInvoiceTax()->getInvoice(),
                    'invoice_type'   => $invoice->getInvoiceType(),
                ];
            }

            $order = [
                'id'         => $data->getId(),
                'status'     => [
                    'id'     => $data->getStatus()->getId(),
                    'status' => $data->getStatus()->getStatus(),
                    'real'   => $data->getStatus()->getRealStatus(),
                ],
                'carrier'    => [
                    'id'       => $data->getQuote()->getCarrier()->getId(),
                    'name'     => $data->getQuote()->getCarrier()->getAlias(),
                    'document' => $this->getDocument($data->getQuote()->getCarrier()),
                    'address'  => $this->getAddress ($data->getQuote()->getCarrier()),
                ],
                'provider'   => [
                    'id'       => $data->getQuote()->getProvider()->getId(),
                    'name'     => $data->getQuote()->getProvider()->getName(),
                    'document' => $this->getDocument($data->getQuote()->getProvider()),
                ],
                'invoiceTax' => $invoiceTax
            ];
        }

        return new JsonResponse([
            'response' => [
                'data'    => $order,
                'count'   => 1,
                'error'   => '',
                'success' => true,
            ],
        ]);
    }

    private function getAddress(People $people): ?array
    {
        if ($people->getAddress()->count() == 0)
            return null;

        $address  = $people->getAddress()->first();

        $street   = $address->getStreet();
        $district = $street->getDistrict();
        $city     = $district->getCity();
        $state    = $city->getState();

        return [
            'id'         => $address->getId(),
            'state'      => $state->getUF(),
            'city'       => $city->getCity(),
            'district'   => $district->getDistrict(),
            'postalCode' => $this->fixPostalCode($street->getCep()->getCep()),
            'street'     => $street->getStreet(),
            'number'     => $address->getNumber(),
            'complement' => $address->getComplement(),
        ];
    }

    private function getDocument(People $people): ?string
    {
        if ($people->getDocument()->count() == 0)
            return null;

        $doctype   = $this->manager->getRepository(DocumentType::class)
            ->findOneBy([
                'peopleType'   => 'J',
                'documentType' => 'CNPJ'
            ]);
        
        if ($doctype === null)
            return null;

        /**
         * @var \Doctrine\Common\Collections\ArrayCollection
         */
        $documents = $people->getDocument();

        $documents->matching((new Criteria())->where(new Comparison('documentType', '=', $doctype)));

        if (($document = $documents->first()) === false)
            return null;

        return $document->getDocument();
    }

    private function fixPostalCode(int $postalCode): string
    {
        $code = (string)$postalCode;
        return strlen($code) == 7 ? '0' . $code : $code;
    }
}
