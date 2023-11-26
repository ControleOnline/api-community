<?php

namespace App\Controller;

use App\Entity\SalesOrder;
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
                 * @var \App\Entity\SalesOrder
                 */
                $salesOrder     = $manager->getRepository(SalesOrder::class)->find(243);
                $receiveInvoice = $salesOrder->getInvoice()->first() ? $salesOrder->getInvoice()->first()->getInvoice() : null;
                $invoiceNumber  = null;
                $invoiceOrders  = [];

                if ($receiveInvoice != null && $receiveInvoice->getServiceInvoiceTax()->first()) {
                    if ($receiveInvoice->getServiceInvoiceTax()->first())
                        $invoiceNumber = $receiveInvoice->getServiceInvoiceTax()->first()
                            ->getServiceInvoiceTax()->getInvoiceNumber();
                }

                if ($receiveInvoice != null) {
                    /**
                     * @var \App\Entity\SalesOrderInvoice $orderInvoice
                     */
                    foreach ($receiveInvoice->getOrder() as $orderInvoice) {
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
                    'order_id'        => $salesOrder->getId(),
                    'invoice_id'      => $receiveInvoice != null ? $receiveInvoice->getId() : 0,
                    'invoice_number'  => $invoiceNumber,
                    'invoice_price'   => $receiveInvoice != null ? 'R$' . number_format($receiveInvoice->getPrice(), 2, ',', '.') : 0,
                    'invoice_duedate' => $receiveInvoice != null ? $receiveInvoice->getDueDate()->format('d/m/Y') : '',
                    'invoice_orders'  => $invoiceOrders,
                ];
            break;

            case 'email/retrieve-request':
                /**
                 * @var \App\Entity\SalesOrder
                 */
                $salesOrder   = $manager->getRepository(SalesOrder::class)->find(222);
                $provider     = $salesOrder->getProvider();
                $providerDoc  = '';
                $retrieveData = [                    
                    'people_type'    => $salesOrder->getRetrievePeople()->getPeopleType(),
                    'people_name'    => $salesOrder->getRetrievePeople()->getName(),
                    'people_alias'   => $salesOrder->getRetrievePeople()->getAlias(),
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
                    'people_type'  => $salesOrder->getDeliveryPeople()->getPeopleType(),
                    'people_name'  => $salesOrder->getDeliveryPeople()->getName(),
                    'people_alias' => $salesOrder->getDeliveryPeople()->getAlias(),
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

                if ($salesOrder->getProvider() && $salesOrder->getProvider()->getDocument()) {
                    foreach ($salesOrder->getProvider()->getDocument() as $document) {
                        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                            $providerDoc = Formatter::document($document->getDocument());
                        }
                    }
                }

                // retrieve

                if ($salesOrder->getRetrievePeople()->getPeopleType() == 'J')
                    if ($salesOrder->getRetrievePeople() && $salesOrder->getRetrievePeople()->getDocument()) {
                        foreach ($salesOrder->getRetrievePeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                                $retrieveData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($salesOrder->getRetrievePeople()->getPeopleType() == 'F')
                    if ($salesOrder->getRetrievePeople() && $salesOrder->getRetrievePeople()->getDocument()) {
                        foreach ($salesOrder->getRetrievePeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CPF') {
                            $retrieveData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($salesOrder->getRetrieveContact()) {
                    $retrieveData['people_contact']['name' ] = $salesOrder->getRetrieveContact()->getName();
                    $retrieveData['people_contact']['alias'] = $salesOrder->getRetrieveContact()->getAlias();

                    /**
                     * @var \App\Entity\Email $email
                     */
                    foreach ($salesOrder->getRetrieveContact()->getEmail() as $email) {
                        $retrieveData['people_contact']['emails'][] = $email->getEmail();
                    }

                    /**
                     * @var \App\Entity\Phone $phone
                     */
                    foreach ($salesOrder->getRetrieveContact()->getPhone() as $phone) {
                        $retrieveData['people_contact']['phones'][] = [
                            'ddd'   => $phone->getDdd(),
                            'phone' => $phone->getPhone(),
                        ];
                    }
                }

                if ($oaddress = $salesOrder->getAddressOrigin()) {
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

                if ($salesOrder->getDeliveryPeople()->getPeopleType() == 'J')
                    if ($salesOrder->getDeliveryPeople() && $salesOrder->getDeliveryPeople()->getDocument()) {
                        foreach ($salesOrder->getDeliveryPeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                                $deliveryData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($salesOrder->getDeliveryPeople()->getPeopleType() == 'F')
                    if ($salesOrder->getDeliveryPeople() && $salesOrder->getDeliveryPeople()->getDocument()) {
                        foreach ($salesOrder->getDeliveryPeople()->getDocument() as $document) {
                            if ($document->getDocumentType()->getDocumentType() == 'CPF') {
                                $deliveryData['people_doc'] = Formatter::document($document->getDocument());
                            }
                        }
                    }

                if ($salesOrder->getDeliveryContact()) {
                    $deliveryData['contact']['name' ] = $salesOrder->getDeliveryContact()->getName();
                    $deliveryData['contact']['alias'] = $salesOrder->getDeliveryContact()->getAlias();

                    /**
                     * @var \App\Entity\Email $email
                     */
                    foreach ($salesOrder->getDeliveryContact()->getEmail() as $email) {
                        $deliveryData['contact']['emails'][] = $email->getEmail();
                    }

                    /**
                     * @var \App\Entity\Phone $phone
                     */
                    foreach ($salesOrder->getDeliveryContact()->getPhone() as $phone) {
                        $deliveryData['contact']['phones'][] = [
                            'ddd'   => $phone->getDdd(),
                            'phone' => $phone->getPhone(),
                        ];
                    }
                }

                if ($daddress = $salesOrder->getAddressDestination()) {
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

                $orderProduct['cubage'] = number_format($salesOrder->getCubage(), 3, ',', '.');
                $orderProduct['type'  ] = $salesOrder->getProductType();
                $orderProduct['total' ] = 'R$' . number_format($salesOrder->getInvoiceTotal(), 2, ',', '.');

                // order package

                /**
                 * @var \App\Entity\OrderPackage $package
                 */
                foreach ($salesOrder->getOrderPackage() as $package) {
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
                 * @var \App\Entity\SalesOrder
                 */
                $salesOrder = $manager->getRepository(SalesOrder::class)->find(222);
                $provider   = [
                    'name'     => $salesOrder->getProvider()->getName(),
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
                 * @var \App\Entity\People $_carrier
                 */
                if ($salesOrder->getQuote() && ($_carrier = $salesOrder->getQuote()->getCarrier())) {
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

                if ($salesOrder->getProvider() && $salesOrder->getProvider()->getDocument()) {
                    foreach ($salesOrder->getProvider()->getDocument() as $document) {
                        if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                            $provider['document'] = Formatter::document($document->getDocument());
                        }
                    }
                }

                $params   =  [
                    'order_id' => $salesOrder->getId(),
                    'provider' => $provider,
                    'carrier'  => $carrier,
                ];
            break;
        }

        return $params;
    }
}
