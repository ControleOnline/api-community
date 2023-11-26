<?php

namespace App\Controller;

use App\Entity\Filesb;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;

class DownloadFilesGlobal extends AbstractController
{

    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager;

    private $appKernel;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $appKernel)
    {
        $this->manager = $entityManager;
        $this->appKernel = $appKernel;
    }

    public function __invoke(Request $request)
    {

        try {

            $ret = null;
            // $method = $request->getMethod();
            // $route = $request->getRequestUri();
            $id = $request->get('id', null);
            $type = $request->query->get('type', null);
            $apiItemOperationName = $request->get('_api_item_operation_name', null);

            // ------------- /files/{id}/download -> Download Files
            if ($apiItemOperationName === 'download_files') {
                $ret = $this->getDownload($id, $type);
            }

        } catch (Exception $e) {

            $ret['response']['data'] = [];
            $ret['response']['count'] = 0;
            $ret['response']['success'] = false;
            $ret['response']['message'] = $e->getMessage();

            return new JsonResponse($ret, 200);
            //exit;

        }

        return $ret;

    }

    /**
     * @param $pathFile
     * @return BinaryFileResponse
     */
    private function buildBinaryResponse($pathFile): BinaryFileResponse
    {
        $pathRoot = $this->appKernel->getProjectDir();
        $billetFullPath = $pathRoot . '/' . $pathFile;
        $response = new BinaryFileResponse($billetFullPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        return $response;
    }

    /**
     * @param string $id
     * @param string $type
     * @return BinaryFileResponse
     * @throws Exception
     */
    private function getDownload(string $id, string $type): BinaryFileResponse
    {
        /**
         * @var Files $filesEtt
         */
        $filesEtt = $this->manager->getRepository(Filesb::class)->find($id);
        if (empty($filesEtt)) {
            throw new Exception('Arquivo para Download não encontrado');
        }
        $fileNameGuidePath = $filesEtt->getFileNameGuide();
        $fileNameReceiptPath = $filesEtt->getFileNameReceipt();
        $pathFile = ($type === 'guide') ? $fileNameGuidePath : $fileNameReceiptPath;
        return $this->buildBinaryResponse($pathFile);
    }

}
