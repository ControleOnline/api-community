<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use App\Entity\Task;
use App\Entity\People;
use App\Entity\SalesOrder;
use App\Entity\Category;
use App\Entity\TaskInteration;
use App\Entity\Status;
use Exception;

class CreateTaskAction
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

  public function __construct(
    EntityManagerInterface $entityManager,
    Security $security
  ) {
    $this->manager     = $entityManager;
    $this->security    = $security;
    $this->currentUser = $security->getUser();
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $order = null;
      $params = [
        'order' => null,
        'client' => null,
        'provider' => null,
        'name' => null,
        'taskStatus' => null,
        'category' => null,
        'reason' => null,
        'criticality' => null,
        'taskFor' => null,
        'dueDate' => null,
        'taskType' => null
      ];

      if ($content = $request->getContent()) {
        $params = array_merge($params, json_decode($content, true));
      }

      if (!$params['taskType'])
        throw new Exception("Task Type is required", 403);


      if (isset($params['id']) && $params['id']) {
        $task = $this->manager->getRepository(Task::class)->find($params['id']);
      } else {
        $task = new Task();
      }

      if ($params['order']) {
        $order = $this->manager->getRepository(SalesOrder::class)->find($params['order']);
        $task->setOrder($order);
      }

      $task->setClient($order ? $order->getClient() : ($params['client'] ? $this->manager->getRepository(People::class)->find($params['client']) : null));
      $task->setProvider($order ? $order->getProvider() : ($params['provider'] ? $this->manager->getRepository(People::class)->find($params['provider']) : null));
      $task->setName($params['name']);
      $task->setType($params['taskType']);


      $task->setTaskStatus($this->manager->getRepository(Status::class)->find($params['taskStatus']));
      $task->setCategory($this->manager->getRepository(Category::class)->find($params['category']));
      $task->setReason($this->manager->getRepository(Category::class)->find($params['reason']));
      $task->setCriticality($this->manager->getRepository(Category::class)->find($params['criticality']));

      //Optional
      $task->setTaskFor($params['taskFor'] ? $this->manager->getRepository(People::class)->find($params['taskFor']) : $this->currentUser->getPeople());
      $task->setDueDate(new \DateTime($params['dueDate'] ?: date('Y-m-d H:i:s', strtotime('+1 weekday'))));
      $task->setRegisteredBy($this->currentUser->getPeople());


      $this->manager->persist($task);
      $this->manager->flush();

      return new JsonResponse([
        'response' => [
          'data'    => [
            'id' => $task->getId()
          ],
          'params' => $params,
          'count'   => 0,
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'line'   => $e->getLine(),
          'success' => false,
        ],
      ]);
    }
  }
}
