<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Email;

class SearchEmailAction
{
    private $em = null;

    private $rq = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
      $this->em = $entityManager;
    }

    public function __invoke(Request $request): JsonResponse
    {
      $this->rq = $request;
      try {

        $emailObj  = null;
        $email = $this->rq->query->get('email', null);

        if (is_string($email) && empty($email) === false)
            $emailObj = $this->em->getRepository(Email::class)->findOneBy(['email' => $email]);

        if (is_null($emailObj))
          throw new \Exception('Email not found');

        return new JsonResponse([
          'response' => [
            'data'    => [
                'id'       => $emailObj->getId(),
                'people_id'     => $emailObj->getPeople()->getId(),
            ],
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
}
