<?php

namespace App\Controller;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;

class UploadLessonFileAction extends AbstractController
{
    /**
     * Entity Manager.
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Security.
     *
     * @var Security
     */
    private $security;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->manager = $entityManager;
    }

    public function __invoke(Request $request): File
    {
        /** @var UploadedFile $uploadedFile */
        if (!($uploadedFile = $request->files->get('file'))) {
            throw new BadRequestHttpException('File is required');
        }

        $fileInfo = $this->saveFile($uploadedFile);

        $file = new File();
        $file->setPath($fileInfo['path']);
        $file->setUrl($fileInfo['url']);

        $this->manager->persist($file);
        $this->manager->flush();

        return $file;
    }

    private function saveFile($file)
    {
        $directoryPath = $_SERVER['DOCUMENT_ROOT'].'/lessonFiles';

        if (!is_dir($directoryPath) && !mkdir($directoryPath) && !is_dir($directoryPath)) {
            throw new \Exception('Error creating directory');
        }

        $arr = explode('/', $file->getMimeType());
        $fileType = array_pop($arr);

        if ('pdf' !== strtolower($fileType)) {
            throw new BadRequestHttpException('Allowed file type: pdf');
        }

        $gen = uniqid('file_', true);
        $newFileName = "{$gen}.{$fileType}";

        $url = "/lessonFiles/{$newFileName}";
        $newPath = "{$directoryPath}/{$newFileName}";

        if (!copy($file->getPathName(), $newPath)) {
            throw new \Exception('Error saving file');
        }

        return [
            'fileName' => $newFileName,
            'path' => $newPath,
            'directoryPath' => $directoryPath,
            'url' => $url,
        ];
    }
}
