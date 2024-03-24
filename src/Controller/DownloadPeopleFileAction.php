<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\People;
use ControleOnline\Entity\File as File;

class DownloadPeopleFileAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Synfony Kernel
     *
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $appKernel)
    {
        $this->kernel  = $appKernel;
        $this->manager = $entityManager;
    }

    public function __invoke(People $data, int $fileId)
    {
        try {

            if (($file = $this->manager->getRepository(File::class)->find($fileId)) === false) {
                throw new \InvalidArgumentException('File was not found');
            }

            $filePath = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . $file->getPath();
            $fileName = pathinfo($file->getUrl(), PATHINFO_BASENAME);

            $content  = $file->getContent();
            $response = new StreamedResponse(function () use ($content) {
    			       fputs(fopen('php://output', 'wb'), $content);
            });

            $response->headers->set('Content-Type', mime_content_type($filePath));

            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName);

            $response->headers->set('Content-Disposition', $disposition);

            return $response;

        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }

}
