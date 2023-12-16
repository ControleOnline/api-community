<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;

class GetAppConfigAction
{
    private $em = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
      $this->em = $entityManager;
    }

    public function __invoke(): JsonResponse
    {
      try {

        $config  = [];

        // get Google Tag Manager ID

        $company = $this->getCompany($this->getDomain());
        if ($company !== null) {
          $gtmcon = $this->em->getRepository(Config::class)
            ->findOneBy([
              'config_key' => 'google-tag-manager',
              'people'     => $company
            ]);

          if ($gtmcon instanceof Config) {
            $config['google-tag-manager'] = str_replace("'", '"', $gtmcon->getConfigValue());
          }
        }

        return new JsonResponse([
          'response' => [
            'data'    => $config,
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

    private function getDomain($domain = null): string
    {
      return $domain ?: $_SERVER['HTTP_HOST'];
    }
}
