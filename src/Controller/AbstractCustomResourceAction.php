<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Container\ContainerInterface;
use App\Resource\ResourceEntity;
use App\Service\PeopleService;
use App\Service\UserCompanyService;

abstract class AbstractCustomResourceAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $entityManager  = null;

  private $currentRequest = null;


  private $payload        = null;


  private $security       = null;

  private $container      = null;

  private $people         = null;

  private $company        = null;

  public function __construct(
    EntityManagerInterface $manager,
    RequestStack           $request,
    Security               $security,
    ContainerInterface     $container,
    PeopleService          $peopleService,
    UserCompanyService     $company
  )
  {
    $this->entityManager  = $manager;
    $this->currentRequest = $request->getCurrentRequest();
    $this->security       = $security;
    $this->container      = $container;
    $this->people         = $peopleService;
    $this->company        = $company;
  }

  protected function company(): UserCompanyService
  {
    return $this->company;
  }

  protected function people(): PeopleService
  {
    return $this->people;
  }

  protected function isPost(): bool
  {
    return $this->request()->getMethod() == Request::METHOD_POST;
  }

  protected function isPut(): bool
  {
    return $this->request()->getMethod() == Request::METHOD_PUT;
  }

  protected function service(string $serviceId): ?object
  {
    if ($this->container->has($serviceId)) {
      return $this->container->get($serviceId);
    }
    return null;
  }

  protected function security(): Security
  {
    return $this->security;
  }

  protected function manager(): EntityManagerInterface
  {
    return $this->entityManager;
  }

  protected function repository(string $repositoryId): ServiceEntityRepository
  {
    return $this->entityManager->getRepository($repositoryId);
  }

  protected function entity(string $repositoryId, string $entityId): ?object
  {
    return $this->entityManager->getRepository($repositoryId)->find($entityId);
  }

  protected function request(): Request
  {
    return $this->currentRequest;
  }

  protected function payload(): ResourceEntity
  {
    return $this->payload;
  }

  protected function persist(object $entity): void
  {
    $this->entityManager->persist($entity);
    $this->entityManager->flush();
  }

  public function __invoke(ResourceEntity $data): JsonResponse
  {


    $this->payload = $data;

    try {
      $output   = $this->index();
      $httpcode = 200;

      switch ($this->request()->getMethod()) {
        case Request::METHOD_GET :
          $httpcode = 200;
        break;
        case Request::METHOD_POST:
          $httpcode = 201;
        break;
        case Request::METHOD_PUT:
          $httpcode = 200;
        break;
        case Request::METHOD_DELETE:
          $httpcode = 204;
        break;
      }

      if ($output === null) {
        return $this->response([], $httpcode);
      }

      return $this->response([
        'response' => [
          'data'    => $output,
          'error'   => '',
          'success' => true,
        ],
      ], $httpcode);
    } catch (\Exception $e) {
      if ($this->manager()->getConnection()->isTransactionActive()) {
        $this->manager()->getConnection()->rollBack();
      }

      return $this->response([
        'response' => [
          'data'    => null,
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ], 500);
    }
  }

  abstract public function index(): ?array;



  protected function response(array $output, int $code = 200): JsonResponse
  {
    return new JsonResponse($output, $code);
  }
}
