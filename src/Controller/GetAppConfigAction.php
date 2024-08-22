<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Service\DomainService;

class GetAppConfigAction
{
  private $em = null;

  public function __construct(EntityManagerInterface $entityManager, private DomainService $domainService)
  {
    $this->em = $entityManager;
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $config  = [];
      $config_key = $request->get('config_key', null);
      $result = [];
      $company = $this->getCompany($this->domainService->getMainDomain());
      if ($company !== null) {

        $filters = [
          'visibility' => 'public',
          'people'     => $company
        ];
        if ($config_key) {
          $filters['config_key'] = $config_key;
        }

        $configs = $this->em->getRepository(Config::class)
          ->findBy($filters);


        foreach ($configs as $config) {
          $result[$config->getConfigKey()] = $config->getConfigValue();
        }
      }

      return new JsonResponse([
        'response' => [
          'data'    => $result,
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

  private function getCompany(string $domain): ?People
  {
    $company = $this->em->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      return null;

    return $company->getPeople();
  }
}
