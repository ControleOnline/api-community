<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DownloadFiles extends AbstractController
{

    /**
     * @var Request
     */
    private $request = null;

    /** //
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
    private $security = null;
    private $appKernel;

    public function __construct(EntityManagerInterface $entityManager, Security $security, KernelInterface $appKernel)
    {
        $this->manager = $entityManager;
        $this->security = $security;
        $this->appKernel = $appKernel;
    }

    /**
     * @Route("/download", name="api_download_pdf", methods={"GET"})
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function getDownload(Request $request): BinaryFileResponse
    {
        $billetPartialPathRequest = $request->get('invoiceUrl', null);
        $pathRoot = $this->appKernel->getProjectDir();
        $billetFullPath = $pathRoot . '/' . $billetPartialPathRequest;
        $response = new BinaryFileResponse($billetFullPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        return $response;
    }

}
