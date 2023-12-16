<?php

namespace App\Controller;

use ControleOnline\Entity\MyContract;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class GetContractPdfAction
{
    private $manager;

    private $request;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $request)
    {
        $this->manager = $entityManager;
        $this->request = $request->getCurrentRequest();
    }

    public function __invoke(MyContract $data)
    {
        $content  = $this->getContractPDFContent($data);
        $response = new StreamedResponse(function () use ($content) {
             fputs(fopen('php://output', 'wb'), $content);
        });

        $response->headers->set('Content-Type', 'application/pdf');

        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, 'contract.pdf');

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function getContractPDFContent(MyContract $contract): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($contract->getHtmlContent());
        $dompdf->setPaper('A4');                           
        $dompdf->render();
        $html = $dompdf->output();

        return $html;
    }
}
