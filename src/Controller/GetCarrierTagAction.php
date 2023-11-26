<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use ApiPlatform\Core\Exception\InvalidValueException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use App\Entity\SalesOrder;
use App\Library\Tag\Html\HtmlClient;
use App\Library\Tag\Pimaco\PimacoClient;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class GetCarrierTagAction
{

    /**
     * Synfony Kernel
     *
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Mimetypes
     *
     * @var array
     */
    private $mimetypes = [
        'pdf' => 'application/pdf'
    ];

    /**
     * File output default names
     *
     * @var array
     */
    private $filenames = [
        'pdf' => 'Etiqueta',
    ];
    /**
     * Twig render
     *
     * @var \Twig\Environment
     */
    private $twig;



    public function __invoke(SalesOrder $data, Request $request, KernelInterface $kernel, Environment $twig)
    {
        $this->kernel = $kernel;
        $this->twig   = $twig;

        $format = $request->query->get('format', 'pdf');
        $method = 'get' . ucfirst(strtolower($format));

        if (method_exists($this, $method) === false)
            throw new InvalidValueException(
                sprintf('Format "%s" is not available', $format)
            );

        if (($content = $this->$method($data, $request)) === null)
            throw new InvalidValueException('File content is empty');

        $response = new StreamedResponse(function () use ($content) {
            fputs(fopen('php://output', 'wb'), $content);
        });

        $response->headers->set('Content-Type', $this->mimetypes[$format]);

        $disposition = HeaderUtils::makeDisposition(
            $format == 'pdf' ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->filenames[$format] . ' - ' . $data->getId() . '.' . $format
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    protected function getPdf(SalesOrder $orderData, Request $request)
    {
        $tag = new HtmlClient($this->twig, $request, $this->kernel->getProjectDir());
        $tagContent =  $tag->getPdf($orderData);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($tagContent);

        /**
         * Tamanho padrão Mercado Livre (150x107)
         */
        $dompdf->setPaper([0, 0, 450, 321]);
        //$dompdf->setPaper([0, 0, 595.28, 300]);
        //$dompdf->setPaper('A4');
        $dompdf->render();
        return $dompdf->output();
    }
}
