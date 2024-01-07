<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;

use ControleOnline\Entity\Person;
use ControleOnline\Entity\People;
use ControleOnline\Entity\File as File;
use App\Library\Utils\File as FileName;

class UploadPersonImageAction
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
  private $security;

  private $appKernel;

  public function __construct(Security $security, EntityManagerInterface $entityManager, KernelInterface $appKernel)
  {
    $this->security  = $security;
    $this->manager   = $entityManager;
    $this->appKernel = $appKernel;
  }

  public function __invoke(Person $data, Request $request): JsonResponse
  {
    try {
      if (!($file = $request->files->get('file'))) {
        throw new \InvalidArgumentException('The file was not uploaded');
      }

      if (!$this->fileIsValidFile($file)) {
        throw new \InvalidArgumentException('The file is not an file');
      }

      $fileInfo  = $this->getPersonFileInfo($data, $file);

      $people    = $this->manager->getRepository(People::class)->find($data->getId());
      $fileFile = $people->getFile();

      // create file entity

      $this->manager->getConnection()->beginTransaction();

      $fileFile = $fileFile ?: new File();
      $fileFile->setUrl($fileInfo['fileUrl']);
      $fileFile->setContent($file->getContent());

      $this->manager->persist($fileFile);

      $people->setFile($fileFile);
      $this->manager->persist($people);

      $this->manager->flush();
      $this->manager->getConnection()->commit();



      return new JsonResponse([
        'response' => [
          'data'    => [
            'id'  => $fileFile->getId(),
            'url' => $fileInfo['fileUrl']
          ],
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }

  private function getPersonFileInfo(Person $person, UploadedFile $file): array
  {
    $fileInfo = pathinfo($file->getClientOriginalName());

    return [
      'fileUrl'  => sprintf('/files/%s/%s', $person->getId(), $file->getClientOriginalName()),
      'fileName' => FileName::generateUniqueName($fileInfo['filename'], $fileInfo['extension']),
      'userPath' => sprintf('data/files/users/profile/%s', $person->getId()),
    ];
  }

  private function fileIsValidFile(UploadedFile $file): bool
  {
    $fileInfo = pathinfo($file->getClientOriginalName());
    if (!isset($fileInfo['extension'])) {
      return false;
    }

    if (in_array($fileInfo['extension'], ['png', 'jpg', 'jpeg', 'gif'])) {
      return true;
    }

    return false;
  }
}
