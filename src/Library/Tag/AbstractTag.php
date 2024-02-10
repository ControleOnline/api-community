<?php

namespace App\Library\Tag;

use ControleOnline\Entity\People;
use ControleOnline\Entity\SalesOrder;
use App\Library\Utils\Formatter;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTag
{

  /**
   * Twig render
   *
   * @var \Twig\Environment
   */
  protected $twig;


  /**
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  protected $project_dir;


  public function __construct(Environment $twig, Request $request, $project_dir)
  {
    $this->twig   = $twig;
    $this->project_dir = $project_dir;
    $this->request = $request;
  }

  protected function _getOrdersTemplateParams(SalesOrder $order): array
  {
    /**
     * @var \ControleOnline\Entity\SalesOrder
     */
    $salesOrder   = $order;
    $provider     = $salesOrder->getProvider();
    $providerDoc  = '';
    $retrieveData = [
      'people_type'    => $salesOrder->getRetrievePeople()->getPeopleType(),
      'people_name'    => $salesOrder->getRetrievePeople()->getName(),
      'people_alias'   => $salesOrder->getRetrievePeople()->getAlias(),
      'people_doc'     => '',
      'contact' => [
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
      $retrieveData['contact']['name'] = $salesOrder->getRetrieveContact()->getName();
      $retrieveData['contact']['alias'] = $salesOrder->getRetrieveContact()->getAlias();

      /**
       * @var \ControleOnline\Entity\Email $email
       */
      foreach ($salesOrder->getRetrieveContact()->getEmail() as $email) {
        $retrieveData['contact']['emails'][] = $email->getEmail();
      }

      /**
       * @var \ControleOnline\Entity\Phone $phone
       */
      foreach ($salesOrder->getRetrieveContact()->getPhone() as $phone) {
        $retrieveData['contact']['phones'][] = [
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

      $deliveryData['contact']['name'] = $salesOrder->getDeliveryContact()->getName();
      $deliveryData['contact']['alias'] = $salesOrder->getDeliveryContact()->getAlias();

      /**
       * @var \ControleOnline\Entity\Email $email
       */
      foreach ($salesOrder->getDeliveryContact()->getEmail() as $email) {
        $deliveryData['contact']['emails'][] = $email->getEmail();
      }

      /**
       * @var \ControleOnline\Entity\Phone $phone
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
    $orderProduct['type']   = $salesOrder->getProductType();
    $orderProduct['total']  = 'R$' . number_format($salesOrder->getInvoiceTotal(), 2, ',', '.');

    // order package

    /**
     * @var \ControleOnline\Entity\OrderPackage $package
     */

    $pkgTotal = $salesOrder->getOrderPackage() ? 0 : 1;
    foreach ($salesOrder->getOrderPackage() as $package) {
      $pkgTotal += $package->getQtd();

      $orderPackages[] = [
        'qtd'    => $package->getQtd(),
        'weight' => str_replace('.', ',', $package->getWeight()) . ' kg',
        'height' => str_replace('.', ',', $package->getHeight() * 100) . ' Centímetros',
        'width'  => str_replace('.', ',', $package->getWidth()  * 100) . ' Centímetros',
        'depth'  => str_replace('.', ',', $package->getDepth()  * 100) . ' Centímetros',
      ];
    }

    // added invoice number

    $carrier = $salesOrder->getQuote()->getCarrier();


    /**
     * @var \ControleOnline\Entity\SalesInvoiceTax $Invoice
     */
    $Invoice = $salesOrder->getClientInvoiceTax();
    $barCode = new BarcodeGeneratorPNG();
    $invoiceKey = $Invoice->getInvoiceKey();
    $invoiceKeyBarCode = base64_encode($barCode->getBarcode($invoiceKey, $barCode::TYPE_CODE_128));


    return [
      'hash'           => md5($salesOrder->getClient()->getId()),
      'secret'         => md5($salesOrder->getPayer()->getId()),
      'api_domain'     => 'https://' . $_SERVER['HTTP_HOST'],
      'provider_logo'  => '/files/download/' . $provider->getFile()->getId(),
      'carrier_logo'   => '/files/download/' . $carrier->getFile()->getId(),
      'carrier_alias'  => $carrier->getAlias(),
      'sales_order'    => $salesOrder->getId(),
      'provider_name'  => $provider->getName(),
      'pkg_total'      => $this->request->query->get('pkg-total', $pkgTotal),
      'invoice_key'    => $invoiceKey,
      'invoice_key_bar_code'    => $invoiceKeyBarCode,
      'provider_doc'   => $providerDoc,
      'retrieve_data'  => $retrieveData,
      'delivery_data'  => $deliveryData,
      'order_product'  => $orderProduct,
      'order_packages' => $orderPackages,
      'invoice_id'     => $Invoice->getId(),
      'invoice_number' => $Invoice->getInvoiceNumber(),
    ];
  }

  protected function getPeopleFilePath(?People $people): string
  {
    $root  = $this->project_dir;
    $pixel = sprintf('%s/data/files/users/white-pixel.jpg', $root);
    $path  = $pixel;

    if ($people === null)
      return $pixel;

    if (($file = $people->getFile()) !== null) {
      $path  = $root . '/' . $file->getPath();

      if (strpos($file->getPath(), 'data/') !== false)
        $path = $root . '/' . str_replace('data/', 'public/', $file->getPath());

      $parts = pathinfo($path);
      if ($parts['extension'] != 'jpg')
        return $pixel;
    }

    return $path;
  }

  abstract public function getPdf(SalesOrder $orderData);

  abstract protected function getPdfTagData(SalesOrder $orderData);
}
