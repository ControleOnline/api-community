<?php

namespace App\Controller;


use ControleOnline\Entity\People;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use ControleOnline\Repository\MenuRepository;


class GetActionByPeopleAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Request
   *
   * @var Request
   */
  private $request  = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;

  /**
   * @var \ControleOnline\Repository\MenuRepository
   */
  private $repository = null;


  public function __construct(Security $security, EntityManagerInterface $entityManager)
  {
    $this->manager    = $entityManager;
    $this->security   = $security;
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $menu  = [];

      $company = $request->query->get('myCompany', null);

      if ($company === null)
        throw new Exception("Company not found", 404);


      $myCompany = $this->manager->getRepository(People::class)
        ->find($company);

      if ($myCompany === null)
        throw new Exception("Company not found", 404);



      $currentUser = $this->security->getUser();
      /**
       * @var People
       */
      $userPeople = $currentUser->getPeople();

      $menu =  $this->getMenuByPeople($userPeople, $myCompany);


      return new JsonResponse([
        'response' => [
          'data'    => $menu,
          'count'   => 1,
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
          'success' => false,
        ],
      ]);
    }
  }

  private function getMenuByPeople(People $userPeople, People $myCompany)
  {

    $return = [];
    $connection = $this->manager->getConnection();

    // build query

    $sql  = 'SELECT action.*,routes.route

    FROM action 
       INNER JOIN routes ON routes.id = action.route_id
       INNER JOIN action_role ON action_role.action_id = action.id
       INNER JOIN role ON role.id = action_role.role_id
       INNER JOIN people_role ON people_role.role_id = role.id
    WHERE people_role.company_id=:myCompany AND 
       people_role.people_id=:userPeople AND 
       routes.route=:route
    GROUP BY action.id
    ';



    $params = [];

    $params['myCompany']   = $myCompany->getId();
    $params['userPeople']   = $userPeople->getId();
    $params['route']   = $this->route;
    // execute query

    $statement = $connection->prepare($sql);
    $statement->execute($params);

    $result = $statement->fetchAll();

    foreach ($result as $action) {
      $return['routes'][trim($action['route'])]['actions'][$action['id']] = trim($action['action']);
    }

    return $return;
  }
}
