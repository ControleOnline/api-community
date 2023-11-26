<?php

namespace App\Controller;

use App\Entity\Address;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Quotation;
use App\Entity\SalesOrder as Order;
use App\Entity\People;

class GetSalesOrderSummaryAction
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
        $order      = [];
        $quotations = [];
        $provider   = $data->getProvider();
        
        $otherInfo = $data->getOtherInformations(true);
        $carNumber = property_exists($otherInfo, 'carNumber') ? $otherInfo->carNumber : null;

        /**
         * @var Quotation $quote
         */
        foreach ($data->getQuotes() as $quote) {
            $quotation = [
                "id"            => $quote->getId(),
                "carrier_name"  => $quote->getCarrier()->getName(),
                "carrier_alias" => $quote->getCarrier()->getAlias(),
                "total"         => $quote->getTotal()
            ];

            $quotations[] = $quotation;
        }

        $order = [
            'id'     => $data->getId(),
            'client' => [
                'id'    => $data->getClient()->getId(),
                'name'  => $data->getClient()->getName(),
                'alias' => $data->getClient()->getAlias(),
            ],
            'orderDate'      => $data->getOrderDate(),
            'alterDate'      => $data->getAlterDate(),
            'parkingDate'    => $data->getParkingDate(),
            'status'    => [
                'id'     => $data->getStatus()->getId(),
                'status' => $data->getStatus()->getStatus(),
                'real'   => $data->getStatus()->getRealStatus(),
            ],
            'retrievePeople'   => $this->getPeople('retrieve', $data),
            'deliveryPeople'   => $this->getPeople('delivery', $data),
            'payer'            => $this->getPeople('payer', $data),
            'price'            => $data->getPrice(),
            'invoiceTotal'     => $data->getInvoiceTotal(),
            'cubage'           => $data->getCubage(),
            'productType'      => $data->getProductType(),
            'carNumber'      => $carNumber,
            'comments'         => $data->getComments(),
            'packages'         => $this->getPackages($data),
            'quote'            => $this->getQuoteData($data),
            'providerId'       => $provider->getId(),
            'providerAlias'    => $provider->getAlias(),
            'providerDocument' => empty($provider->getOneDocument()) ? null : $provider->getOneDocument()->getDocument(),
            'contractId'       => $data->getContract() ? $data->getContract()->getId() : null,
            'quotations'       => $quotations
        ];

        return new JsonResponse([
            'response' => [
                'data'    => $order,
                'count'   => 1,
                'error'   => '',
                'success' => true,
            ],
        ]);
    }

    private function getQuoteData(Order $order): ?array
    {
        /**
         * @var \App\Entity\Quotation
         */
        if (($quote = $order->getQuotes()->first()) === false)
            return null;

        return [
            'origin'      => [
                'city'  => $quote->getCityOrigin()->getCity(),
                'state' => $quote->getCityOrigin()->getState()->getUF(),
            ],
            'destination' => [
                'city'  => $quote->getCityDestination()->getCity(),
                'state' => $quote->getCityDestination()->getState()->getUF(),
            ],
        ];
    }

    private function getPackages(Order $order): array
    {
        if ($order->getOrderPackage()->count() == 0)
            return [];

        $packages = [];

        /**
         * @var \App\Entity\OrderPackage $package
         */
        foreach ($order->getOrderPackage() as $package) {
            $packages[] = [
                'qtd' => $package->getQtd(),
                'height' => $package->getHeight(),
                'width'  => $package->getWidth(),
                'depth'  => $package->getDepth(),
                'weight' => $package->getWeight(),
            ];
        }

        return $packages;
    }

    private function getPeople(string $type, Order $order): ?array
    {
        switch ($type) {
            case 'retrieve':
                $people  = $order->getRetrievePeople();
                $address = $order->getAddressOrigin();
                $contact = $order->getRetrieveContact();
                break;
            case 'delivery':
                $people  = $order->getDeliveryPeople();
                $address = $order->getAddressDestination();
                $contact = $order->getDeliveryContact();
                break;
            case 'payer':
                $people  = $order->getPayer();
                $address = $order->getAddressOrigin();
                $contact = $people;
                break;
        }

        if ($people  === null)
            return null;

        /*
        if ($address === null)
            return null;

        if ($contact === null)
            return null;
        */

        return [
            'id'       => $people->getId(),
            'name'     => $people->getName(),
            'alias'    => $people->getAlias(),
            'type'     => $people->getPeopleType(),
            'document' => $this->getDocument($people),
            'contact'  => $this->getContact($contact),
            'address'  => $this->getAddress($address),
        ];
    }

    private function getAddress(?Address $address): ?array
    {
        if ($address === null)
            return null;

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

    private function getContact(?People $people): ?array
    {
        if ($people === null)
            return null;

        $email  = '';
        $code   = '';
        $number = '';

        if ($people->getEmail()->count() > 0)
            $email = $people->getEmail()->first()->getEmail();

        if ($people->getPhone()->count() > 0) {
            $phone  = $people->getPhone()->first();
            $code   = $phone->getDdd();
            $number = $phone->getPhone();
        }

        return [
            'name'  => $people->getName(),
            'alias' => $people->getAlias(),
            'email' => $email,
            'phone' => sprintf('%s%s', $code, $number),
        ];
    }

    private function getDocument(?People $people): ?array
    {
        if ($people === null)
            return null;

        /**
         * @var \App\Entity\Document $document
         */
        if (($document = $people->getDocument()->first()) === false)
            return null;

        return [
            'document' => $document->getDocument(),
            'type'     => $document->getDocumentType()->getDocumentType(),
        ];
    }

    private function fixPostalCode(int $postalCode): string
    {
        $code = (string)$postalCode;
        return strlen($code) == 7 ? '0' . $code : $code;
    }
}
