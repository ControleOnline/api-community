<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\SalesOrder as Order;
use App\Entity\SalesOrderInvoiceTax as OrderInvoiceTax;
use App\Entity\SalesInvoiceTax as InvoiceTax;
use ControleOnline\Entity\Status;
use App\Entity\People;
use App\Entity\Address;
use App\Service\AddressService;

class UpdateSalesOrderDacteAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    private $address = null;

    public function __construct(EntityManagerInterface $entityManager, AddressService $address)
    {
      $this->manager = $entityManager;
      $this->address = $address;
    }

    public function __invoke(Order $data, Request $request): Order
    {
      $payload = json_decode($request->getContent(), true);

      if (!isset($payload['total']) || !is_numeric($payload['total'])) {
        throw new \InvalidArgumentException('DACTE total was not defined', 400);
      }

      if (!isset($payload['dacte'])) {
        throw new \InvalidArgumentException('DACTE number was not defined', 400);
      }
      else {
          if (preg_match('/^\d+$/', $payload['dacte']) !== 1) {
            throw new \InvalidArgumentException('DACTE number is not valid', 400);
          }
      }

      $order = $data;

      // validate if DACTE was not uploaded

      $orderInvoiceTax = $this->manager->getRepository(OrderInvoiceTax::class)
          ->findOneBy([
              'issuer'      => $order->getQuote()->getCarrier(),
              'invoiceType' => 57,
              'order'       => $order,
          ]);
      if ($orderInvoiceTax instanceof OrderInvoiceTax) {
        throw new \InvalidArgumentException('O DACTE já foi registrado');
      }

      // validate order status, upload is allowed in "waiting retrieve"

      if (!in_array($order->getStatus()->getStatus(), ['waiting retrieve', 'retrieved'])) {
        throw new \InvalidArgumentException('Order status must be "waiting retrieve" or "retrieved"');
      }

      // validate order origin and destiny must be the same

      if ($order->isOriginAndDestinationTheSame() === false && $order->isOriginAndDestinationTheSameState() === false) {
        throw new \InvalidArgumentException('Order origin and destiny must be the same');
      }

      // create invoice tax

      $invoiceTax = new InvoiceTax();

      $invoiceTax->setInvoiceNumber($payload['dacte']);
      $invoiceTax->setInvoice      ($this->getFakeDACTEContent($order, $payload['dacte'], $payload['total']));

      $this->manager->persist($invoiceTax);

      // create invoice order relationship

      $purchasingOrderInvoiceTax = new OrderInvoiceTax();

      $purchasingOrderInvoiceTax->setOrder      ($order);
      $purchasingOrderInvoiceTax->setInvoiceTax ($invoiceTax);
      $purchasingOrderInvoiceTax->setInvoiceType(57);
      $purchasingOrderInvoiceTax->setIssuer     ($order->getQuote()->getCarrier());

      $this->manager->persist($purchasingOrderInvoiceTax);

      // change order status

      $status = $this->manager->getRepository(Status::class)
        ->findOneBy(['status' => 'on the way']);
      if ($status instanceof Status) {
        $order->setStatus($status);
      }

      return $order;
    }

    private function getFakeDACTEContent(Order $order, $dacteNum, $total): string
    {
      $invoiceDacte = $this->manager->getRepository(OrderInvoiceTax::class)->findOneBy(['invoiceType' => 57]);
      if ($invoiceDacte === null) {
        throw new \InvalidArgumentException('DACTE model was not found', 404);
      }

      $invoiceTax = $invoiceDacte->getInvoiceTax();
      if (empty($invoiceTax->getInvoice())) {
        throw new \InvalidArgumentException('DACTE model is empty', 400);
      }

      if (($dacte = simplexml_load_string($invoiceTax->getInvoice())) === false) {
        throw new \InvalidArgumentException('DACTE model is corrupted', 400);
      }

      // modify dacte model content

      $dacte->CTe->infCte->ide->nCT        = $dacteNum;
      $dacte->CTe->infCte->compl->xObs     = 'DACTE GERADO PELO SISTEMA';
      $dacte->CTe->infCte->vPrest->vTPrest = $total;

      if (($quote = $order->getQuote()) !== null) {
        $carrier = $this->getPeopleInfo($quote->getCarrier());

        $dacte->CTe->infCte->emit->CNPJ  = $carrier['cnpj'];
        $dacte->CTe->infCte->emit->IE    = $carrier['ie'];
        $dacte->CTe->infCte->emit->xNome = $carrier['name'];

        $dacte->CTe->infCte->emit->enderEmit->xLgr    = $carrier['address']['street'];
        $dacte->CTe->infCte->emit->enderEmit->nro     = $carrier['address']['number'];
        $dacte->CTe->infCte->emit->enderEmit->xBairro = $carrier['address']['district'];
        $dacte->CTe->infCte->emit->enderEmit->xMun    = $carrier['address']['city'];
        $dacte->CTe->infCte->emit->enderEmit->CEP     = $carrier['address']['cep'];
        $dacte->CTe->infCte->emit->enderEmit->UF      = $carrier['address']['state'];
        $dacte->CTe->infCte->emit->enderEmit->fone    = $carrier['phone'];
      }

      if (($retrievePeople = $order->getRetrievePeople()) !== null) {
        $retrieve = $this->getPeopleInfo($retrievePeople, $order->getAddressOrigin());

        if ($retrievePeople->getPeopleType() == 'J') {
          $dacte->CTe->infCte->rem->CNPJ = $retrieve['cnpj'];
        }
        else {
          if ($retrievePeople->getPeopleType() == 'F') {
            $dacte->CTe->infCte->rem->CPF = $retrieve['cpf'];
          }
        }

        $dacte->CTe->infCte->rem->IE    = $retrieve['ie'];
        $dacte->CTe->infCte->rem->xNome = $retrieve['name'];
        $dacte->CTe->infCte->rem->xFant = $retrieve['alias'];
        $dacte->CTe->infCte->rem->fone  = $retrieve['phone'];

        $dacte->CTe->infCte->rem->enderReme->xLgr    = $retrieve['address']['street'];
        $dacte->CTe->infCte->rem->enderReme->nro     = $retrieve['address']['number'];
        $dacte->CTe->infCte->rem->enderReme->xBairro = $retrieve['address']['district'];
        $dacte->CTe->infCte->rem->enderReme->xMun    = $retrieve['address']['city'];
        $dacte->CTe->infCte->rem->enderReme->CEP     = $retrieve['address']['cep'];
        $dacte->CTe->infCte->rem->enderReme->UF      = $retrieve['address']['state'];
      }

      if (($deliveryPeople = $order->getDeliveryPeople()) !== null) {
        $delivery = $this->getPeopleInfo($deliveryPeople, $order->getAddressDestination());

        if ($deliveryPeople->getPeopleType() == 'J') {
          $dacte->CTe->infCte->dest->CNPJ  = $delivery['cnpj'];
        }
        else {
          if ($deliveryPeople->getPeopleType() == 'F') {
            $dacte->CTe->infCte->dest->CPF = $delivery['cpf'];
          }
        }

        $dacte->CTe->infCte->dest->CNPJ  = $delivery['cnpj'];
        $dacte->CTe->infCte->dest->IE    = $delivery['ie'];
        $dacte->CTe->infCte->dest->xNome = $delivery['name'];
        $dacte->CTe->infCte->dest->xFant = $delivery['alias'];
        $dacte->CTe->infCte->dest->fone  = $delivery['phone'];

        $dacte->CTe->infCte->dest->enderDest->xLgr    = $delivery['address']['street'];
        $dacte->CTe->infCte->dest->enderDest->nro     = $delivery['address']['number'];
        $dacte->CTe->infCte->dest->enderDest->xBairro = $delivery['address']['district'];
        $dacte->CTe->infCte->dest->enderDest->xMun    = $delivery['address']['city'];
        $dacte->CTe->infCte->dest->enderDest->CEP     = $delivery['address']['cep'];
        $dacte->CTe->infCte->dest->enderDest->UF      = $delivery['address']['state'];
      }

      return $dacte->asXML();
    }

    private function getPeopleInfo(People $people, ?Address $address = null): array
    {
      $info = [
        'cnpj'    => '',
        'cpf'     => '',
        'ie'      => '',
        'name'    => $people->getName(),
        'alias'   => $people->getAlias(),
        'phone'   => '',
        'address' => [
          'street'   => '',
          'number'   => '',
          'district' => '',
          'city'     => '',
          'cep'      => '',
          'state'    => '',
        ],
      ];

      // get document

      if (!empty($people->getDocument())) {
        foreach ($people->getDocument() as $document) {
          if ($people->getPeopleType() == 'J') {
            if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
              $info['cnpj'] = $document->getDocument();
            }
          }

          if ($people->getPeopleType() == 'F') {
            if ($document->getDocumentType()->getDocumentType() == 'CPF') {
              $info['cpf'] = $document->getDocument();
            }
          }
        }
      }

      // get address

      if ($address instanceof Address) {
        $address = $this->address->addressToArray($address);
      }
      else {
        if (($address = $people->getAddress()->first()) !== false) {
          $address = $this->address->addressToArray($address);
        }
      }

      $info['address']['street']   = $address['street'];
      $info['address']['number']   = $address['number'];
      $info['address']['district'] = $address['district'];
      $info['address']['cep']      = $address['postalCode'];
      $info['address']['city']     = $address['city'];
      $info['address']['state']    = $address['state'];

      return $info;
    }
}
