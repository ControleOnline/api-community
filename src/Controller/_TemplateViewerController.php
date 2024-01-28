<?php

namespace App\Controller;

use ControleOnline\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Library\Utils\Formatter;

class _TemplateViewerController extends AbstractController
{
    public function index(Request $request)
    {
        $templateName = $request->query->get('tmp', '/');

        $tempatePath  = $templateName . '.html.twig';

        return $this->render($tempatePath, $this->getTemplateParams($templateName));
    }

    private function getTemplateParams(string $templateName): array
    {
        $manager = $this->getDoctrine()->getManager();
        $params  = [];

        switch ($templateName) {
            case 'email/invoice-outdated':
                /**
                 * @var \ControleOnline\Entity\Order
                 */
                $Order     = $manager->getRepository(Order::class)->find(243);
                $Invoice = $Order->getInvoice()->first() ? $Order->getInvoice()->first()->getInvoice() : null;
                $invoiceNumber  = null;
                $invoiceOrders  = [];

                if ($Invoice != null && $Invoice->getServiceInvoiceTax()->first()) {
                    if ($Invoice->getServiceInvoiceTax()->first())
                        $invoiceNumber = $Invoice->getServiceInvoiceTax()->first()
                            ->getServiceInvoiceTax()->getInvoiceNumber();
                }

                if ($Invoice != null) {
                    /**
                     * @var \ControleOnline\Entity\OrderInvoice $orderInvoice
                     */
                    foreach ($Invoice->getOrder() as $orderInvoice) {
                        $order = $orderInvoice->getOrder();

                        $invoiceOrders[] = [
                            'id'      => $order->getId(),
                            'carrier' => $order->getQuote() ? $order->getQuote()->getCarrier()->getName() : '',
                            'invoice' => $order->getInvoiceTax()->count() > 0 ?
                                $order->getInvoiceTax()->first()->getInvoiceTax()->getInvoiceNumber() : '',
                            'price'   => 'R$' . number_format($order->getPrice(), 2, ',', '.'),
                        ];
                    }
                }

                $params  =  [
                    'api_domain'      => 'https://'.$_SERVER['HTTP_HOST'],
                    'app_domain'      => 'https://cotafacil.freteclick.com.br',
                    'order_id'        => $Order->getId(),
                    'invoice_id'      => $Invoice != null ? $Invoice->getId() : 0,
                    'invoice_number'  => $invoiceNumber,
                    'invoice_price'   => $Invoice != null ? 'R$' . number_format($Invoice->getPrice(), 2, ',', '.') : 0,
                    'invoice_duedate' => $Invoice != null ? $Invoice->getDueDate()->format('d/m/Y') : '',
                    'invoice_orders'  => $invoiceOrders,
                ];
            break;

            case 'email/retrieve-request':
                /**
                 * @var \ControleOnline\Entity\Order
                 */
                $Order   = $manager->getRepository(Order::class)->find(222);
                $provider     = $Order->getProvider();
                $providerDoc  = '';
                $retrieveData = [                    
                    'people_type'    => $Order->getRetrievePeople()->getPeopleType(),
                    'people_name'    => $Order->getRetrievePeople()->getName(),
                    'people_alias'   => $Order->getRetrievePeople()->getAlias(),
                    'people_doc'     => '',
                    'people_contact' => [
                        'name'   => '',
                        'alias'  => '',
                        'emails' => [],
                        'phones' => [],
                    ],
                    'address' => [
                        'postal_code' => '',
                        'street'      => '',
                        'number'      => '',
                        'complement'  => '',
                        'district'    => '',
                        'city'        => '',
                        'state'       => '',
                    ],
                ];
                $deliveryData = [                    
                    'people_type'  => $Order->getDeliveryPeople()->getPeopleType(),
                    'people_name'  => $Order->getDeliveryPeople()->getName(),
                    'people_alias' => $Order->getDeliveryPeople()->getAlias(),
                    'people_doc'   => '',
                    'contact'      => [
                        'name'   => '',
                        'alias'  => '',
                        'emails' => [],
                        'phones' => [],
                    ],
                    'address'      => [
                        'postal_code' => '',
                        'street'      => '',
                        'number'      => '',
                        'complement'  => '',
                        'district'    => '',
                        'city'        => '',
                        'state'       => '',
                    ],
                ];
                $orderProduct  = [
                    'cubage' => '',
                    'type'   => '',
                    'total'  => '',
                ];
                $orderPackages = [];

                // provider

                if ($Order->getProvider() && $Order->getProvider()->getDocument()) {
                    foreach ($Order->getProvider()->getDocument() as $document) {
                        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                            $providerDoc = Formatter::document($document->getDocument());
                        }
                    }
                }

                // retrieve

                if ($Order->getRetrievePeople()->getPeopleType() == 'J')
                    if ($Order->getRetrievePeople() && $Order->getRetrievePeople()->getDocument()) {
                        foreach ($Order->getRetrievePeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                                $retrieveData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($Order->getRetrievePeople()->getPeopleType() == 'F')
                    if ($Order->getRetrievePeople() && $Order->getRetrievePeople()->getDocument()) {
                        foreach ($Order->getRetrievePeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CPF') {
                            $retrieveData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($Order->getRetrieveContact()) {
                    $retrieveData['people_contact']['name' ] = $Order->getRetrieveContact()->getName();
                    $retrieveData['people_contact']['alias'] = $Order->getRetrieveContact()->getAlias();

                    /**
                     * @var \ControleOnline\Entity\Email $email
                     */
                    foreach ($Order->getRetrieveContact()->getEmail() as $email) {
                        $retrieveData['people_contact']['emails'][] = $email->getEmail();
                    }

                    /**
                     * @var \ControleOnline\Entity\Phone $phone
                     */
                    foreach ($Order->getRetrieveContact()->getPhone() as $phone) {
                        $retrieveData['people_contact']['phones'][] = [
                            'ddd'   => $phone->getDdd(),
                            'phone' => $phone->getPhone(),
                        ];
                    }
                }

                if ($oaddress = $Order->getAddressOrigin()) {
                    $street   = $oaddress->getStreet();
                    $district = $street->getDistrict();
                    $city     = $district->getCity();
                    $state    = $city->getState();                    
                    $retrieveData['address']['state']       = $state->getUF();
                    $retrieveData['address']['city']        = $city->getCity();
                    $retrieveData['address']['district']    = $district->getDistrict();
                    $retrieveData['address']['postal_code'] = strlen($street->getCep()->getCep()) == 7 ? '0' . $street->getCep()->getCep() : $street->getCep()->getCep();
                    $retrieveData['address']['street']      = $street->getStreet();
                    $retrieveData['address']['number']      = $oaddress->getNumber();
                    $retrieveData['address']['complement']  = $oaddress->getComplement();

                    if (!empty($retrieveData['address']['postal_code']))
                        $retrieveData['address']['postal_code'] = Formatter::mask('#####-###', $retrieveData['address']['postal_code']);
                }

                // delivery

                if ($Order->getDeliveryPeople()->getPeopleType() == 'J')
                    if ($Order->getDeliveryPeople() && $Order->getDeliveryPeople()->getDocument()) {
                        foreach ($Order->getDeliveryPeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                                $deliveryData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($Order->getDeliveryPeople()->getPeopleType() == 'F')
                    if ($Order->getDeliveryPeople() && $Order->getDeliveryPeople()->getDocument()) {
                        foreach ($Order->getDeliveryPeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CPF') {
                                $deliveryData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($Order->getDeliveryContact()) {
                    $deliveryData['contact']['name' ] = $Order->getDeliveryContact()->getName();
                    $deliveryData['contact']['alias'] = $Order->getDeliveryContact()->getAlias();

                    /**
                     * @var \ControleOnline\Entity\Email $email
                     */
                    foreach ($Order->getDeliveryContact()->getEmail() as $email) {
                        $deliveryData['contact']['emails'][] = $email->getEmail();
                    }

                    /**
                     * @var \ControleOnline\Entity\Phone $phone
                     */
                    foreach ($Order->getDeliveryContact()->getPhone() as $phone) {
                        $deliveryData['contact']['phones'][] = [
                            'ddd'   => $phone->getDdd(),
                            'phone' => $phone->getPhone(),
                        ];
                    }
                }

                if ($daddress = $Order->getAddressDestination()) {
                    $street   = $daddress->getStreet();
                    $district = $street->getDistrict();
                    $city     = $district->getCity();
                    $state    = $city->getState();

                    
                    $deliveryData['address']['state']       = $state->getUF();
                    $deliveryData['address']['city']        = $city->getCity();
                    $deliveryData['address']['district']    = $district->getDistrict();
                    $deliveryData['address']['postal_code'] = strlen($street->getCep()->getCep()) == 7 ? '0' . $street->getCep()->getCep() : $street->getCep()->getCep();
                    $deliveryData['address']['street']      = $street->getStreet();
                    $deliveryData['address']['number']      = $daddress->getNumber();
                    $deliveryData['address']['complement']  = $daddress->getComplement();

                    if (!empty($deliveryData['address']['postal_code']))
                        $deliveryData['address']['postal_code'] = Formatter::mask('#####-###', $deliveryData['address']['postal_code']);
                }

                // order product

                $orderProduct['cubage'] = number_format($Order->getCubage(), 3, ',', '.');
                $orderProduct['type'  ] = $Order->getProductType();
                $orderProduct['total' ] = 'R$' . number_format($Order->getInvoiceTotal(), 2, ',', '.');

                // order package

                /**
                 * @var \ControleOnline\Entity\OrderPackage $package
                 */
                foreach ($Order->getOrderPackage() as $package) {
                    $orderPackages[] = [
                        'qtd'    => $package->getQtd(),
                        'weight' => str_replace('.', ',', $package->getWeight()) . ' kg',
                        'height' => str_replace('.', ',', $package->getHeight() * 100). ' Centímetros',
                        'width'  => str_replace('.', ',', $package->getWidth()  * 100). ' Centímetros',
                        'depth'  => str_replace('.', ',', $package->getDepth()  * 100) . ' Centímetros',
                    ];
                }

                $params   =  [
                    'api_domain'     => 'https://'.$_SERVER['HTTP_HOST'],
                    'app_domain'     => 'https://cotafacil.freteclick.com.br',
                    'provider_name'  => $provider->getName(),
                    'provider_doc'   => $providerDoc,
                    'retrieve_data'  => $retrieveData,
                    'delivery_data'  => $deliveryData,
                    'order_product'  => $orderProduct,
                    'order_packages' => $orderPackages,
                ];
            break;

            case 'email/invoice-tax-instructions':
                /**
                 * @var \ControleOnline\Entity\Order
                 */
                $Order = $manager->getRepository(Order::class)->find(222);
                $provider   = [
                    'name'     => $Order->getProvider()->getName(),
                    'document' => '',
                ];
                $carrier    = [
                    'name'      => '',
                    'cnpj'      => '',
                    'inscricao' => '',
                    'address'   => [
                        'postal_code' => '',
                        'street'      => '',
                        'number'      => '',
                        'complement'  => '',
                        'district'    => '',
                        'city'        => '',
                        'state'       => '',
                    ],
                ];

                // carrier

                /**
                 * @var \ControleOnline\Entity\People $_carrier
                 */
                if ($Order->getQuote() && ($_carrier = $Order->getQuote()->getCarrier())) {
                    $carrier['name'] = $_carrier->getName();

                    foreach ($_carrier->getDocument() as $document) {
                        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                            $carrier['cnpj'] = Formatter::mask('##.###.###/####-##', $document->getDocument());
                        }

                        if ($document->getDocumentType()->getDocumentType() == 'Inscrição Estadual') {
                            $carrier['inscricao'] = Formatter::mask('###.###.###.###', $document->getDocument());
                        }
                    }

                    if (!$_carrier->getAddress()->isEmpty()) {
                        $address  = $_carrier->getAddress()->first();
                        $street   = $address->getStreet();
                        $district = $street->getDistrict();
                        $city     = $district->getCity();
                        $state    = $city->getState();

                        $carrier['address']['state']       = $state->getUF();
                        $carrier['address']['city']        = $city->getCity();
                        $carrier['address']['district']    = $district->getDistrict();
                        $carrier['address']['postal_code'] = strlen($street->getCep()->getCep()) == 7 ? '0' . $street->getCep()->getCep() : $street->getCep()->getCep();
                        $carrier['address']['street']      = $street->getStreet();
                        $carrier['address']['number']      = $address->getNumber();
                        $carrier['address']['complement']  = $address->getComplement();

                        if (!empty($carrier['address']['postal_code']))
                        $carrier['address']['postal_code'] = Formatter::mask('#####-###', $carrier['address']['postal_code']);
                    }
                }

                // provider

                if ($Order->getProvider() && $Order->getProvider()->getDocument()) {
                    foreach ($Order->getProvider()->getDocument() as $document) {
                        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                            $provider['document'] = Formatter::document($document->getDocument());
                        }
                    }
                }

                $params   =  [
                    'order_id' => $Order->getId(),
                    'provider' => $provider,
                    'carrier'  => $carrier,
                ];
            break;
        }

        return $params;
    }
}
