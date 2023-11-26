<?php

namespace App\Controller;

use App\Entity\PurchasingInvoiceTax AS InvoiceTax;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use ApiPlatform\Core\Exception\InvalidValueException;
use App\Entity\People;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\CTe\Dacte;

class DownloadOrderNFAction
{
    /**
     * Mimetypes
     *
     * @var array
     */
    private $mimetypes = [
        'xml' => 'application/xml',
        'pdf' => 'application/pdf',
    ];

    /**
     * File output default names
     *
     * @var array
     */
    private $filenames = [
        'xml' => 'nota_fiscal.xml',
        'pdf' => 'nota_fiscal.pdf',
    ];

    /**
     * Synfony Kernel
     *
     * @var KernelInterface
     */
    private $kernel;

    public function __invoke(InvoiceTax $data, Request $request, KernelInterface $kernel)
    {
        $this->kernel = $kernel;

        $format = $request->query->get('format', 'pdf');
        $method = 'get' . ucfirst(strtolower($format));
        if (method_exists($this, $method) === false)
            throw new InvalidValueException(
                sprintf('Format "%s" is not available', $format)
            );

        if (($content = $this->$method($data)) === null)
            throw new InvalidValueException('File content is empty');

        $response = new StreamedResponse(function () use ($content) {            
			fputs(fopen('php://output', 'wb'), $content);
        });

        $response->headers->set('Content-Type', $this->mimetypes[$format]);

        $disposition = HeaderUtils::makeDisposition(
            $format == 'pdf' ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->filenames[$format]
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function getXml(InvoiceTax $invoiceTax): ?string
    {
        return $invoiceTax->getInvoice();
    }

    private function getPdf(InvoiceTax $invoiceTax): ?string
    {        
        if ($invoiceTax->getOrder()[0]->getInvoiceType() == 55) {
            $file = $this->getPeopleFilePath($invoiceTax->getOrder()[0]->getOrder()->getClient());

			$danfe = new Danfe($invoiceTax->getInvoice(), 'P', 'A4', $file, 'I', '');
            $danfe->montaDANFE();

			return $danfe->render();			
        }

        if ($invoiceTax->getOrder()[0]->getInvoiceType() == 57) {
            $file = $this->getPeopleFilePath($invoiceTax->getOrder()[0]->getOrder()->getProvider());

            $danfe = new Dacte($invoiceTax->getInvoice(), 'P', 'A4', $file, 'I', '');
            $danfe->montaDACTE();

			return $danfe->render();			
        }

        return null;
    }

    private function getPeopleFilePath(?People $people): string
    {
        $root  = $this->kernel->getProjectDir();
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
}
