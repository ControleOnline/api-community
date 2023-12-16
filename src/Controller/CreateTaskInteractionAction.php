<?php

namespace App\Controller;

use ControleOnline\Entity\Task;
use ControleOnline\Entity\File as File;
use ControleOnline\Entity\TaskInteration;
use App\Library\Utils\File as FileName;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateTaskInteractionAction
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
    $this->security    = $security;
    $this->appKernel   = $appKernel;
    $this->currentUser = $security->getUser();
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      /**
       * @var string $taskId
       */
      if (!($taskId = $request->get('task_id', null)))
        throw new BadRequestHttpException('task_id is required');




      /**
       * @var UploadedFile $uploadedFile
       */
      $uploadedFile = $request->files->get('file', null);
      $file = null;

      $interaction = $request->get("payload", null);

      if ($interaction == null) {
        $interaction = $request->getContent();
      }

      if ($interaction !== null) {
        $interaction = json_decode($interaction, true);

        $this->manager->getConnection()->beginTransaction();

        if ($uploadedFile) {
          $pathRoot  = $this->appKernel->getProjectDir();

          $fileInfo  = $this->getFileInfo($uploadedFile);
          $filePath  = $fileInfo['filePath'] . '/' . $fileInfo['fileName'];
          $fullPath  = sprintf('%s/%s', $pathRoot, $fileInfo['filePath']);

          // save uploaded file in drive

          if (!file_exists($fullPath)) {
            if (mkdir($fullPath, 0777, true) === false) {
              throw new \InvalidArgumentException('Import files storage was not created');
            }
          }

          if (((fileperms("$fullPath") & 0x4000) == 0x4000) === false) {
            throw new \InvalidArgumentException('Import storage space is unavailable');
          }

          $uploadedFile->move($fullPath, $fileInfo['fileName']);

          $file = new File();

          $file->setUrl($fileInfo['fileUrl']);
          $file->setPath($filePath);

          $this->manager->persist($file);
        }

        $newInteraction = new TaskInteration();

        $newInteraction->setRegisteredBy($this->currentUser->getPeople());
        $newInteraction->setType($interaction["type"]);

        if (!empty($file)) {
          $newInteraction->setFile($file);
        }

        $newInteraction->setBody($interaction['body']);

        $newInteraction->setTask(
          $this->manager->getRepository(Task::class)
            ->findOneBy(array(
              "id" => $taskId
            ))
        );

        $newInteraction->setVisibility($interaction['visibility']);

        $this->manager->persist($newInteraction);
        $this->manager->flush();
        $this->manager->getConnection()->commit();

        return new JsonResponse([
          'response' => [
            'data'    => $newInteraction->getId(),
            'count'   => 1,
            'error'   => '',
            'success' => true,
          ],
        ]);
      } else {
        throw new BadRequestHttpException('interaction data is required');
      }
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

  private function getFileInfo(UploadedFile $file): array
  {
    $fileInfo = pathinfo($file->getClientOriginalName());

    $lastFile = $this->manager->getRepository(File::class)
      ->findOneBy([], ['id' => 'desc']);

    $lastId = $lastFile->getId() + 1;

    return [
      'fileUrl'  => '/files/' . $lastId . '/image.png',
      'fileName' => FileName::generateUniqueName($fileInfo['filename'], $fileInfo['extension']),
      'filePath' => sprintf('data/interactions/%s', date("Y-m-d")),
    ];
  }
}
