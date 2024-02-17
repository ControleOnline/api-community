<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use App\Service\PeopleRoleService;

class GetDefaultCompanyAction
{
  /*
   * @var Security
   */
  private $security;
  private $em = null;
  private $roles;
  private $domain;

  public function __construct(Security $security, EntityManagerInterface $entityManager, PeopleRoleService $roles)
  {
    $this->security = $security;
    $this->em = $entityManager;
    $this->roles = $roles;
  }

  public function __invoke(Request $request): JsonResponse
  {
    /**
     * @var string $domain
     */
    $domain = $request->get('app-domain', null);
    $this->domain = $this->getDomain($domain);

    try {

      $defaultCompany = [];
      $configs = [];
      $allConfigs = [];
      $user = $this->security->getUser();
      $company = $this->getCompany();
      $permissions = $user ? $this->roles->getAllRolesByCompany($user->getPeople(), $company->getPeople()) : ['guest'];

      if ($company) {
        $allConfigs = $this->em->getRepository(Config::class)->findBy([
          'people'      => $company->getPeople()->getId(),
          'visibility'  => 'public'
        ]);

        foreach ($allConfigs as $config) {
          $configs[$config->getConfigKey()] = $config->getConfigValue();
        }
        
        $defaultCompany = [
          'id'         => $company->getPeople()->getId(),
          'alias'      => $company->getPeople()->getAlias(),
          'configs'    => $configs,
          'domainType' => $company->getDomainType(),
          'permissions' => $permissions,
          'theme'       => $this->getTheme(),
          'logo'       => $company->getPeople()->getFile() ? [
            'id'     => $company->getPeople()->getFile()->getId(),
            'domain' => $_SERVER['HTTP_HOST'],
            'url'    => '/files/download/' . $company->getPeople()->getFile()->getId()
          ] : null,
        ];
      }

      return new JsonResponse([
        'response' => [
          'data'    => $defaultCompany,
          'count'   => 1,
          'error'   => '',
          'success' => true
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
  private function getCompany(): ?PeopleDomain
  {
    $company = $this->em->getRepository(PeopleDomain::class)->findOneBy(['domain' => $this->domain]);
    return $company;
  }

  private function getTheme()
  {
    return [
      'theme' =>  $this->domain->getTheme()->getTheme(),
      'colors' =>  $this->domain->getTheme()->getColors(),
      'background'  =>  $this->domain->getTheme()->getBackground() ? [
        'id'     =>  $this->domain->getTheme()->getBackground()->getId(),
        'domain' => $_SERVER['HTTP_HOST'],
        'url'    => '/files/download/' .  $this->domain->getTheme()->getBackground()->getId()
      ] : null,
    ];
  }

  private function getDomain($domain = null): string
  {
    return $domain ?: $_SERVER['HTTP_HOST'];
  }
}
