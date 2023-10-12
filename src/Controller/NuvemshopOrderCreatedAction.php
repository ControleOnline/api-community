<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Library\Nuvemshop\Client;
use App\Library\Nuvemshop\Model\User as NuvemUser;
use ControleOnline\Entity\User;

class NuvemshopOrderCreatedAction extends AbstractController
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
    private $request = null;

    /**
     * Nuvemshop client
     *
     * @var Client
     */
    private $client  = null;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
      $this->manager  = $entityManager;
      $this->client   = new Client;
      $this->security = $security;
    }

    public function __invoke(Request $request)
    {
      try {

        // verify api key

        $user = $this->manager->getRepository(User::class)
          ->findOneBy(['apiKey' => $request->query->get('api-key')]);
        if ($user === null) {
          throw new \Exception('Access denied');
        }
        else {

          // auto log

          $this->get('security.token_storage')
            ->setToken(
              new UsernamePasswordToken($user, null, 'main', $user->getRoles())
            );
        }

        // set nuvemshop user

        $nuser = (new NuvemUser())
          ->setId   ($this->security->getToken()->getUser()->getOauthUser())
          ->setToken($this->security->getToken()->getUser()->getOauthHash())
          ->setKey  ($this->security->getToken()->getUser()->getApiKey())
          ->setHost ($_SERVER['HTTP_HOST'])
        ;

        $this->client->setUser($nuser);

        // handle nuvemshop order

        $data  = json_decode($request->getContent(), true);
        if (!isset($data['id'])) {
          throw new \Exception('Order id is not defined');
        }

        $order = $this->client->getOrder($data['id']);

        return new JsonResponse([], 200);

      } catch (\Exception $e) {
        return new JsonResponse([
          'response' => [
            'data'    => null,
            'error'   => $e->getMessage(),
            'success' => false,
          ],
        ]);
      }
    }
}
