<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Config;
use App\Entity\People;
use App\Entity\PeopleDomain;
use App\Service\PeopleRoleService;

class GetDefaultCompanyAction
{
  /*
   * @var Security
   */
  private $security;
  private $em = null;
  private $roles;

  private $domainType = null;

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
    $domain = $request->get('domain', null);

    try {

      $defaultCompany = [];
      $configs = [];
      $allConfigs = [];
      $user = $this->security->getUser();
      $company = $this->getCompany($this->getDomain($domain));
      $permissions = $user ? $this->roles->getAllRolesByCompany($user->getPeople(), $company) : ['guest'];

      if ($company) {
        $allConfigs = $this->em->getRepository(Config::class)->findBy([
          'people'      => $company->getId(),
          'visibility'  => 'public'
        ]);
        foreach ($allConfigs as $config) {
          $configs[$config->getConfigKey()] = $config->getConfigValue();
        }


        $defaultCompany = [
          'id'         => $company->getId(),
          'alias'      => $company->getAlias(),
          'configs'    => $configs,
          'domainType' => $this->domainType,
          'permissions' => $permissions,
          'logo'       => $company->getFile() ? [
            'id'     => $company->getFile()->getId(),
            'domain' => $_SERVER['HTTP_HOST'],
            'url'    => '/files/download/' . $company->getFile()->getId()
          ] : null,
          'background'  => $company->getBackgroundFile() ? [
            'id'     => $company->getBackgroundFile()->getId(),
            'domain' => $_SERVER['HTTP_HOST'],
            'url'    => '/files/download/' . $company->getBackgroundFile()->getId()
          ] : null,
          'alternative_logo'  => $company->getAlternativeFile() ? [
            'id'     => $company->getAlternativeFile()->getId(),
            'domain' => $_SERVER['HTTP_HOST'],
            'url'    => '/files/download/' . $company->getAlternativeFile()->getId()
          ] : null
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
  private function getCompany(string $domain): ?People
  {
    $company = $this->em->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      return null;

    $this->domainType = $company->getDomainType();

    return $company->getPeople();
  }

  private function getDomain($domain = null): string
  {
    return $domain ?: $_SERVER['HTTP_HOST'];
  }
}
