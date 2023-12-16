<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Exception;

class SearchTasksPeopleAction
{

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $em   = null;

  public function __construct(EntityManagerInterface $entityManager, Security $security)
  {
    $this->em   = $entityManager;
    $this->user = $security->getUser();
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $context     = $request->query->get('context', null);
      $company     = $request->query->get('company', null);
      if ($company === null)
        throw new Exception("Company not found", 404);


      $repository = $this->em->getRepository(People::class);
      $company = $repository->find($company);

      $peoples  = $repository->createQueryBuilder('P')
        ->select()
        ->innerJoin('\ControleOnline\Entity\Task', 'T', 'WITH', 'T.taskFor = P.id')
        ->where('T.provider = :provider')
        ->andWhere('T.type = :context')        
        ->setParameters([
          'provider' => $company,
          'context' => $context
        ])
        ->groupBy('P.id')
        ->orderBy('P.name','ASC')
        ->getQuery()
        ->getResult();

      foreach ($peoples as $people) {
        $data[] = [
          'id' => $people->getId(),
          'name' => $people->getName()
        ];
      }


      return new JsonResponse([
        'response' => [
          'data'    => $data,
          'count'   => '',
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Doctrine\DBAL\Driver\Exception $e) {
      return new JsonResponse($e->getMessage());
    } catch (\Exception $e) {
      return new JsonResponse($e->getMessage());
    }
  }
}
