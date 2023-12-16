<?php

namespace App\Controller;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\DeliveryTax;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\PurchasingOrder as Order;
use ControleOnline\Entity\Quotation;
use ControleOnline\Entity\Status;
use Symfony\Component\Security\Core\Security;

class GetPurchasingOrderQuotationAction
{

    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    private $security;

    /**
     * Quotation repository
     *
     * @var \ControleOnline\Repository\QuotationRepository
     */
    private $quotation = null;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->manager   = $entityManager;
        $this->security  = $security;
        $this->quotation = $this->manager->getRepository(Quotation::class);
    }

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        $order = [
            'id'          => $data->getId(),
            'quote'       => $this->getQuote($data->getQuote()),
            'status'      => $this->getStatus($data->getStatus()),
            'quotes'      => $this->getQuotes($data),
            'contact'     => $this->getContact($data),
            'product'     => $this->getProduct($data),
            'origin'      => $this->getAddress($data->getAddressOrigin()),
            'destination' => $this->getAddress($data->getAddressDestination()),
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

    private function getQuote(?Quotation $quote): ?array
    {
        if ($quote === null)
            return null;
        return [
            'id' => $quote->getId(),
            'origin' => [
                'city' => $quote->getCityOrigin()->getCity(),
                'state' => $quote->getCityOrigin()->getState()->getUf(),
                'country' => $quote->getCityOrigin()->getState()->getCountry()->getCountryname(),
                'district'   => '',
                'postalCode' => '',
                'street'     => '',
                'number'     => '',
                'complement' => '',

            ],
            'destination' => [
                'city' => $quote->getCityDestination()->getCity(),
                'state' => $quote->getCityDestination()->getState()->getUf(),
                'country' => $quote->getCityDestination()->getState()->getCountry()->getCountryname(),
                'district'   => '',
                'postalCode' => '',
                'street'     => '',
                'number'     => '',
                'complement' => '',
            ]
        ];
    }

    private function getStatus(Status $status)
    {
        $id          = $status->getId();
        $s      = $status->getStatus();
        $real_status = $status->getRealStatus();

        return [
            'id'          => $id,
            'status'      => $s,
            'real_status' => $real_status
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
            'country'    => 'Brasil',
            'state'      => $state->getUF(),
            'city'       => $city->getCity(),
            'district'   => $district->getDistrict(),
            'address'    => $street->getStreet(),
            'postalCode' => $this->fixPostalCode($street->getCep()->getCep()),
            'street'     => $street->getStreet(),
            'number'     => $address->getNumber(),
            'complement' => $address->getComplement(),
        ];
    }

    private function getProduct(Order $order): ?array
    {
        return [
            'type'       => $order->getProductType(),
            'totalPrice' => $order->getInvoiceTotal(),
            'packages'   => $this->getPackages($order),
            'cubage'     => '0,00',
            'sumCubage'  => $order->getCubage(),
        ];
    }

    private function getPackages(Order $order): array
    {
        if ($order->getOrderPackage()->count() == 0)
            return [];

        $packages = [];

        /**
         * @var \ControleOnline\Entity\OrderPackage $package
         */
        foreach ($order->getOrderPackage() as $package) {
            $packages[] = [
                'qtd'    => $package->getQtd(),
                'height' => $package->getHeight(),
                'width'  => $package->getWidth(),
                'depth'  => $package->getDepth(),
                'weight' => $package->getWeight(),
            ];
        }

        return $packages;
    }

    private function getContact(Order $data): ?array
    {
        $people = $data->getRetrievePeople();
        if (!$people || $people->getEmail()->count() == 0) {
            $employee = $data->getClient()->getPeopleEmployee();
            $people = $employee->count() > 0 ? $employee->first()->getEmployee() : null;
        }

        if (!$people || $people->getEmail()->count() == 0) {
            /**
             * @var \ControleOnline\Entity\User
             */
            $user   = $this->security->getUser();
            $people = $user->getPeople();
        }

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
            'id'    => $people->getId(),
            'name'  => $people->getName(),
            'alias' => $people->getAlias(),
            'email' => $email,
            'phone' => sprintf('%s%s', $code, $number),
        ];
    }

    private function getQuotes(Order $order): array
    {
        if ($order->getQuotes()->count() == 0)
            return [];

        $quotes = [];

        /**
         * @var Quotation $quote
         */
        foreach ($order->getQuotes() as $quote) {
            $quotes[] = [
                'id'               => $quote->getId(),
                'group'            => [
                    'name' => $this->getGroupName($quote),
                ],
                'carrier'          => [
                    'name'  => $quote->getCarrier()->getName(),
                    'image' => $quote->getCarrier()->getFile() ? $_SERVER['HTTP_HOST'] .  '/files/download/' . $quote->getCarrier()->getFile()->getId() : null,
                ],
                'retrieveDeadline' => $this->quotation->getRetrieveDeadline($quote),
                'deliveryDeadline' => $quote->getDeadline(),
                'total'            => $quote->getTotal(),
                'carrierRating'    => 4,
                'taxes'            => $this->getTaxes($quote)
            ];
        }

        return $quotes;
    }

    private function getTaxes(Quotation $quote): array
    {
        if ($quote->getQuoteDetail()->count() == 0)
            return [];

        $taxes = [];

        /**
         * @var \ControleOnline\Entity\QuoteDetail $quoteDetail
         */
        foreach ($quote->getQuoteDetail() as $quoteDetail) {
            $taxes[] = [
                'id'       => $quoteDetail->getId(),
                'name'     => $quoteDetail->getTaxName(),
                'subtotal' => $quoteDetail->getPriceCalculated(),
            ];
        }

        return $taxes;
    }

    private function getGroupName(Quotation $quote): string
    {
        $group = 'Fracionado';
        $dtax  = null;

        /**
         * @var \ControleOnline\Entity\QuoteDetail $quoteDetail
         */
        foreach ($quote->getQuoteDetail() as $quoteDetail) {
            if ($quoteDetail->getDeliveryTax() !== null) {
                $dtax = $quoteDetail->getDeliveryTax();
                break;
            }
        }

        /**
         * @var DeliveryTax $dtax
         */
        if ($dtax instanceof DeliveryTax) {
            $group = $dtax->getGroupTax() ? $dtax->getGroupTax()->getGroupName() : $group;
        }

        return $group;
    }

    private function fixPostalCode(int $postalCode): string
    {
        $code = (string)$postalCode;
        return strlen($code) == 7 ? '0' . $code : $code;
    }
}
