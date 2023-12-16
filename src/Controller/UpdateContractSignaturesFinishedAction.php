<?php

namespace App\Controller;

use ControleOnline\Entity\MyContract;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\SignatureService;

class UpdateContractSignaturesFinishedAction
{
    private $manager;

    private $request;

    private $signature;

    public function __construct(
      EntityManagerInterface $entityManager,
      RequestStack           $request,
      SignatureService       $signature
    )
    {
        $this->manager   = $entityManager;
        $this->request   = $request->getCurrentRequest();
        $this->signature = $signature;
    }

    public function __invoke(Request $request, string $provider)
    {
      $signatureProvider = $this->signature->getFactory($provider);
      if ($signatureProvider === null) {
        throw new \Exception('Signature provider not found');
      }

      $docKey = $signatureProvider
        ->verifyEventPayload('closed', json_decode($this->request->getContent()));

      $contract = $this->manager->getRepository(MyContract::class)
        ->findOneBy([
          'docKey' => $docKey
        ]);
      if (!$contract instanceof MyContract) {
        throw new \Exception('Contract was not found');
      }

      // update contract status

      $this->manager->persist($contract->setContractStatus('Waiting approval'));
      $this->manager->flush();

      return new JsonResponse([
        'response' => [
          'success' => true,
        ],
      ], 200);
    }
}
