<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Repository\ImportRepository;

use ControleOnline\Entity\Import;

class DeleteImportAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
    * Import repository
    *
    * @var ImportRepository
    */
  private $imports = null;

  public function __construct(
    EntityManagerInterface $entityManager
  ) {
    $this->manager = $entityManager;
    $this->imports = $this->manager->getRepository(Import::class);
  }
  
  public function __invoke(Request $request): JsonResponse
  {

    try {

        /**
         * @var string $id
         */
        if (!($id = $request->get('id', null)))
          throw new BadRequestHttpException('id is required');

        /**
         * @var Import $importObject
         */
        $importObject = $this->imports->findOneBy(array("id" => $id));

        if (empty($importObject))
          throw new BadRequestHttpException('import data not found');

        $status = $importObject->getStatus();

        if ($status == "waiting") {
            
            $this->manager->getConnection()->beginTransaction();
            
            $this->manager->remove($importObject);

            $this->manager->flush();
            $this->manager->getConnection()->commit();
        }
        else {
            throw new BadRequestHttpException('import data can\'t be deleted');
        }

        return new JsonResponse([
          'response' => [
            'data'    => [],
            'count'   => 1,
            'error'   => '',
            'success' => true,
          ],
        ]);

    }
    catch (\Exception $e) {
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
}