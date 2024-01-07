<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Security;
use ControleOnline\Entity\File as File;
use ControleOnline\Entity\Import;

use App\Library\Utils\File as FileName;

class ImportAction
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
  private $security = null;

  /**
   * Current user
   *
   * @var \ControleOnline\Entity\User
   */
  private $currentUser = null;

  /**
   * App Kernel
   *
   * @var KernelInterface
   */
  private $appKernel;

  public function __construct(
    EntityManagerInterface $entityManager,
    KernelInterface $appKernel,
    Security $security
  ) {
    $this->manager     = $entityManager;
    $this->appKernel   = $appKernel;
    $this->security    = $security;
    $this->currentUser = $security->getUser();
  }

  public function __invoke(Request $request): JsonResponse
  {

    try {

      // validate file from request

      /**
       * @var UploadedFile $uploadedFile
       */
      if (!($uploadedFile = $request->files->get('file')))
        throw new BadRequestHttpException('csv file is required');


      $tableid = $request->get('tableId', null);
      $importType = $request->get('importType', null);
      $fileFormat = $request->query->get('fileFormat', 'csv');
      

      $fileInfo  = $this->getCSVFileInfo($tableid, $uploadedFile);
      $filePath  = $fileInfo['filePath'] . '/' . $fileInfo['fileName'];

      // save uploaded file in drive

      $this->manager->getConnection()->beginTransaction();
      $file = new File();

      $file->setUrl($fileInfo['fileUrl']);
      $file->setPath($filePath);
      $file->setContent($uploadedFile->getContent());

      $this->manager->persist($file);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      $this->manager->getConnection()->beginTransaction();
      $import = new Import();

      $importStatus = "waiting";      

      $import->setFileId($file->getId());      
      $import->setName($tableid);
      $import->setStatus($importStatus);
      $import->setImportType($importType);
      $import->setPeopleId($this->currentUser->getPeople()->getId());
      $import->setFileFormat($fileFormat);
      $import->setFeedback('waiting to import');

      $this->manager->persist($import);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return new JsonResponse([
        'response' => [
          'data'    => [
            "fileId" => $file->getId(),
            "importId" => $import->getId(),            
            "fileUrl" => $file->getUrl(),
            "importStatus" => $importStatus
          ],
          'count'   => 1,
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

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

  private function getCSVFileInfo(string $tableid, UploadedFile $file): array
  {
    $fileInfo = pathinfo($file->getClientOriginalName());

    return [
      'fileUrl'  => sprintf('/files/%s/%s/%s', date("Y-m-d-H-i-s"), $tableid, $file->getClientOriginalName()),
      'fileName' => FileName::generateUniqueName($fileInfo['filename'], $fileInfo['extension']),
      'filePath' => sprintf('data/imports/%s/%s', $tableid, date("Y-m-d")),
    ];
  }
}
