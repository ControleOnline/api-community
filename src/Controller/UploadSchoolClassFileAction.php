<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\SchoolClass;
use App\Entity\SchoolClassFiles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\KernelInterface;

class UploadSchoolClassFileAction
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

    private $appKernel;

    public function __construct(Security $security, EntityManagerInterface $entityManager, KernelInterface $appKernel)
    {
        $this->security  = $security;
        $this->manager   = $entityManager;
        $this->appKernel = $appKernel;
    }

    public function __invoke(SchoolClass $data, Request $request): SchoolClassFiles
    {
        // validate file from request

        /**
         * @var UploadedFile $uploadedFile
         */
        if (!($uploadedFile = $request->files->get('file'))) {
            throw new BadRequestHttpException('File is required');
        }

        $fileInfo = $this->saveFile($uploadedFile);

        try {

          $this->manager->getConnection()->beginTransaction();

          $file = new File();
          $file->setPath($fileInfo['path']);
          $file->setUrl($fileInfo['path']);
          $this->manager->persist($file);

          $schoolClassFiles = new SchoolClassFiles();
          $schoolClassFiles->setFile($file);
          $schoolClassFiles->setSchoolClass($data);
          $this->manager->persist($schoolClassFiles);

          $this->manager->flush();
          $this->manager->getConnection()->commit();

          return $schoolClassFiles;

        } catch (\Exception $e) {
          if ($this->manager->getConnection()->isTransactionActive()) {
            $this->manager->getConnection()->rollBack();
          }
        }
    }

    private function saveFile($file)
    {
        $directoryPath = $this->appKernel->getProjectDir() .'/data/files';

        if (!is_dir($directoryPath) && !mkdir($directoryPath, 0777, true) && !is_dir($directoryPath)) {
            throw new \Exception('Error creating directory');
        }

        $arr = explode('/', $file->getMimeType());
        $fileType = array_pop($arr);

        if ('pdf' !== strtolower($fileType)) {
            throw new BadRequestHttpException('Allowed file type: pdf');
        }

        $gen = uniqid('file_', true);
        $newFileName = "{$gen}.{$fileType}";

        $newPath = "{$directoryPath}/{$newFileName}";

        if (!copy($file->getPathName(), $newPath)) {
            throw new \Exception('Error saving file');
        }

        return [
            'fileName'      => $newFileName,
            'path'          => $newPath,
            'directoryPath' => $directoryPath,
        ];
    }
}
