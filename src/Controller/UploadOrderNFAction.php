<?php

namespace App\Controller;

use App\Entity\PurchasingInvoiceTax as InvoiceTax;
use ControleOnline\Entity\PurchasingOrder as Order;
use ControleOnline\Entity\PurchasingOrderInvoiceTax;
use ControleOnline\Entity\Status;
use App\Entity\People;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadOrderNFAction
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
    private $invoiceType = 55;

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
            throw new BadRequestHttpException('"NF" file is required');

        // verify param myCompany

        if ($request->get('myCompany', null) === null)
            throw new BadRequestHttpException('Issuer Id is not defined');

        $company = $this->getPeopleCompany($request->get('myCompany'));
        if ($company === null)
            throw new BadRequestHttpException('Company not found');

        // verify param orderId

        if ($request->request->get('orderId', null) === null)
            throw new BadRequestHttpException('Order Id is not defined');

        /**
         * @var Order $order
         */
        $order = $this->manager->find(Order::class, $request->request->get('orderId'));
        if ($order === null)
            throw new BadRequestHttpException('Order not found');


        if ($order->getStatus()->getRealStatus() != 'pending')
            throw new BadRequestHttpException('Order status does not allow this action');


        // get NF file content

        $invoiceFile = $this->getNFFileContent($uploadedFile);

        // verify relationship

        $_orderInvoiceTax = $this->manager->getRepository(PurchasingOrderInvoiceTax::class)
            ->findOneBy([
                'issuer'      => $order->getClient(),
                'invoiceType' => $this->invoiceType,
                'order'       => $order,
            ]);

        // in case invoice_tax file has been uploaded

        if ($_orderInvoiceTax instanceof PurchasingOrderInvoiceTax) {

            // update invoice tax

            $invoiceTax = $_orderInvoiceTax->getInvoiceTax();

            $invoiceTax->setInvoiceNumber($invoiceFile['number']);
            $invoiceTax->setInvoice($invoiceFile['invoice']);


            // change order status

            if ($order->getStatus()->getStatus() == 'waiting client invoice tax') {
                $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'automatic analysis']);
                if ($status instanceof Status)
                    $order->setStatus($status);
            }
        } else {

            // create invoice tax

            $invoiceTax = new InvoiceTax();

            $invoiceTax->setInvoiceNumber($invoiceFile['number']);
            $invoiceTax->setInvoice($invoiceFile['invoice']);

            // create invoice order relationship

            $PurchasingOrderInvoiceTax = new PurchasingOrderInvoiceTax();

            $PurchasingOrderInvoiceTax->setOrder($order);
            $PurchasingOrderInvoiceTax->setInvoiceTax($invoiceTax);
            $PurchasingOrderInvoiceTax->setInvoiceType($this->invoiceType);
            $PurchasingOrderInvoiceTax->setIssuer($order->getClient());

            $this->manager->persist($PurchasingOrderInvoiceTax);

            // change order status



            $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'automatic analysis']);
            if ($status instanceof Status)
                $order->setStatus($status);
        }

        return $invoiceTax;
    }

    private function getNFFileContent(UploadedFile $file): array
    {
        /**
         * @var \SimpleXMLElement $xml
         */
        if (($xml = @simplexml_load_file($file->getRealPath())) === false)
            throw new BadRequestHttpException('Não foi possivel ler o arquivo xml');

        if ($xml->NFe->infNFe->ide->mod != $this->invoiceType)
            throw new BadRequestHttpException('O modelo da Nota Fiscal não é válido');

        return [
            'number'  => $xml->NFe->infNFe->ide->nNF,
            'invoice' => $this->getFileContent($file->getRealPath()),
        ];
    }

    private function getFileContent(string $filePath): string
    {
        $handle = fopen($filePath, "r");

        if (($content = fread($handle, filesize($filePath))) === false)
            throw new BadRequestHttpException('Não foi possivel ler o conteudo do arquivo');

        fclose($handle);

        return $content;
    }

    private function getPeopleCompany(?int $companyId): ?People
    {
        if (empty($companyId))
            return null;

        /**
         * @var \ControleOnline\Entity\User $currentUser
         */
        $currentUser   = $this->security->getUser();
        $companyPeople = $this->manager->find(People::class, $companyId);

        if ($companyPeople instanceof People) {

            // verify if companyPeople is a company of current user

            $isMyCompany = $currentUser->getPeople()->getPeopleCompany()->exists(
                function ($key, $element) use ($companyPeople) {
                    return $element->getCompany() === $companyPeople;
                }
            );

            if ($isMyCompany === false) {
                return null;
            }
        } else
            return null;

        return $companyPeople;
    }
}
