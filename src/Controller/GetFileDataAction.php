<?php

namespace App\Controller;

use ControleOnline\Entity\File;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;



class GetFileDataAction
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

    public function __invoke(File $data,   Request $request)
    {

        try {
            $file = $data;
            //$file = $this->manager->getRepository(File::class)->findOneBy(['url' => $request->getPathInfo()]);
            if (!$file)
                throw new \Exception('Not found', 404);


            $filePath = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . $file->getPath();
            $content  = $this->getFileContent($filePath);
            $response = new StreamedResponse(function () use ($content) {
                fputs(fopen('php://output', 'wb'), $content);
            });


            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            if ($ext == 'svg') {
                $response->headers->set('Content-Type', 'image/svg+xml');
            } else {
                $response->headers->set('Content-Type', mime_content_type($filePath));
            }

            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, basename($request->getPathInfo()));
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

    private function getFileContent(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        if (($content = file_get_contents($filePath)) === false) {
            return '';
        }

        return $content;
    }
}
