<?php

namespace App\Controller;

use ControleOnline\Entity\InvoiceTax AS InvoiceTax;
use ControleOnline\Entity\Order AS Order;
use ControleOnline\Entity\OrderInvoiceTax;
use ControleOnline\Entity\Status;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @todo validar se order pertence ao usuario logado
 */
class UploadOrderDACTEAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security;

    /**
     * Document type
     *
     * @var integer
     */
    private $invoiceType = 57;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->manager  = $entityManager;
    }

    public function __invoke(Request $request): InvoiceTax
    {
        // validate file from request

        /**
         * @var UploadedFile $uploadedFile
         */
        if (!($uploadedFile = $request->files->get('file')))
            throw new BadRequestHttpException('"DACTE" file is required');

        // verify param orderId

        if ($request->query->get('orderId', null) === null)
            throw new BadRequestHttpException('Order Id is not defined');

        /**
         * @var Order $order
         */
        $order = $this->manager->find(Order::class, $request->query->get('orderId', null));
        if ($order === null)
            throw new BadRequestHttpException('Order not found');

        // verify order status, upload is allowed in "waiting retrieve"

        if (!in_array($order->getStatus()->getStatus(), ['waiting retrieve', 'retrieved'])) {
          throw new \InvalidArgumentException('Order status must be "waiting retrieve" or "retrieved"');
        }

        // get DACTE file content

        $invoiceFile = $this->getDacteFileContent($uploadedFile);

        // verify if DACTE belongs to order

        $invoiceTaxs = $order->getInvoiceTax()->filter(
            function (OrderInvoiceTax $invoiceOrder) {
                return $invoiceOrder->getInvoiceType() == 55;
            }
        );
        /**
         * @var OrderInvoiceTax $invoiceTax
         */
        if (($invoiceTax = $invoiceTaxs->first()) === false)
            throw new \Exception('Este pedido não tem nota fiscal associada');

        $invoiceTax = $invoiceTax->getInvoiceTax();

        if (!in_array($invoiceTax->getInvoiceNumber(), $invoiceFile['danfes']))
            throw new \Exception('Este DACTE não pertence ao pedido informado');

        // create invoice tax

        $invoiceTax = new InvoiceTax();

        $invoiceTax->setInvoiceNumber($invoiceFile['number' ]);
        $invoiceTax->setInvoice      ($invoiceFile['invoice']);

        // create invoice order relationship

        $OrderInvoiceTax = new OrderInvoiceTax();

        $OrderInvoiceTax->setOrder      ($order);
        $OrderInvoiceTax->setInvoiceTax ($invoiceTax);
        $OrderInvoiceTax->setInvoiceType($this->invoiceType);
        $OrderInvoiceTax->setIssuer     ($order->getQuote()->getCarrier());

        $this->manager->persist($OrderInvoiceTax);

        // validate duplicity

        $_orderInvoiceTax = $this->manager->getRepository(OrderInvoiceTax::class)
            ->findOneBy([
                'issuer'      => $order->getQuote()->getCarrier(),
                'invoiceType' => $this->invoiceType,
                'order'       => $order,
            ]);
        if ($_orderInvoiceTax instanceof OrderInvoiceTax)
            throw new BadRequestHttpException('Já existe um DACTE registrado');

        // change order status

        $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'on the way']);
        if ($status instanceof Status)
            $order->setStatus($status);

        return $invoiceTax;
    }

    private function getDacteFileContent(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());

        if (mb_detect_encoding($content, 'UTF-8,ISO-8859-1', true) == 'ISO-8859-1') {
            $content = utf8_encode($content);

            // fix xml content

            $tag_pattern = '/\<\?xml\s?version="1.0"\s?encoding="UTF-8"\?\>/';
            if (preg_match($tag_pattern, $content) === 0) {
              $content = '﻿<?xml version="1.0" encoding="UTF-8"?>' . $content;
            }
        }

        /**
         * @var \SimpleXMLElement $xml
         */
        if (($xml = @simplexml_load_string($content)) === false)
            throw new BadRequestHttpException('Não foi possivel ler o arquivo xml');

        if ($xml->CTe->infCte->ide->mod != $this->invoiceType)
            throw new BadRequestHttpException('O modelo do DACTE não é válido');

        // get danfes keys

        $danfes = [];
        if (isset($xml->CTe->infCte->infCTeNorm->infDoc->infNF)) {
          $danfes[] = (int) $xml->CTe->infCte->infCTeNorm->infDoc->infNF->nDoc;
        }
        else {
            if (isset($xml->CTe->infCte->infCTeNorm->infDoc->infNFe)) {
                foreach ($xml->CTe->infCte->infCTeNorm->infDoc->infNFe as $danfeKey) {
                  $danfes[] = (int) substr((string) $danfeKey->chave, 25, 9);
                }
            }
            else if (isset($xml->CTe->infCte->infCTeNorm->infDoc->infOutros)) {
                $danfes[] = (int) $xml->CTe->infCte->infCTeNorm->infDoc->infOutros->nDoc;
            }
        }

        if (empty($danfes)) {
          throw new BadRequestHttpException('Não foram encontradas chaves de notas fiscais');
        }

        return [
            'number'  => $xml->CTe->infCte->ide->nCT,
            'invoice' => $content,
            'danfes'  => $danfes,
        ];
    }
}
