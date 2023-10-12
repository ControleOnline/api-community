<?php

namespace App\Controller;

use App\Entity\Config;
use App\Entity\File;
use App\Entity\People;
use App\Entity\PeopleDomain;
use App\Entity\PeopleEmployee;
use App\Entity\PeoplePackage;
use App\Entity\PeopleSalesman;
use App\Service\PeopleRoleService;
use App\Entity\PackageModules;
use App\Entity\Module;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

class GetMyCompaniesAction
{
  /*
   * @var Security
   */
  private $security;

  private $em = null;
  private $roles;

  public function __construct(Security $security, EntityManagerInterface $entityManager, PeopleRoleService $roles)
  {
    $this->security = $security;
    $this->em      = $entityManager;
    $this->roles = $roles;
  }




  public function __invoke(): JsonResponse
  {
    try {

      $myCompanies = [];

      /**
       * @var \ControleOnline\Entity\User
       */
      $currentUser = $this->security->getUser();

      /**
       * @var \App\Entity\People
       */
      $userPeople  = $currentUser->getPeople();
      $permissions = [];


      $getPeopleCompanies = $userPeople->getPeopleCompany();

      /**
       * @var \App\Entity\PeopleEmployee $peopleCompany
       */
      foreach ($getPeopleCompanies as $peopleCompany) {

        $allConfigs = [];
        $configs = [];
        $people = $peopleCompany->getCompany();

        //if ($peopleCompany->getEnabled() && $people->getEnabled()) {

        $domains = $this->getPeopleDomains($people);
        $packages = $this->getPeoplePackages($people);


        $permissions[$people->getId()] = $this->roles->getAllRolesByCompany($userPeople, $people);

        $allConfigs = $this->em->getRepository(Config::class)->findBy([
          'people'      => $people->getId(),
          'visibility'  => 'public'
        ]);
        foreach ($allConfigs as $config) {
          $configs[$config->getConfigKey()] = $config->getConfigValue();
        }

        $myCompanies[$people->getId()] = [
          'id'            => $people->getId(),
          'enabled'       => $people->getEnabled(),
          'alias'         => $people->getAlias(),
          'logo'          => $this->getLogo($people),
          'document'      => $this->getDocument($people),
          'domains'       => $domains,
          'configs'       => $configs,
          'packages'      => $packages,
          'user'          => [
            'id' => $userPeople->getId(),
            'name' => $userPeople->getName(),
            'alias' => $userPeople->getAlias(),
            'enabled' => $userPeople->getEnabled(),
            'employee_enabled' => $peopleCompany->getEnabled(),
            'salesman_enabled' => false
          ]
        ];
        //}
      }


      $peopleSalesman     = $this->em->getRepository(PeopleSalesman::class)->getMySaleCompanies($userPeople);

      foreach ($peopleSalesman as $com) {
        $company = $this->em->getRepository(People::class)->find($com['people_id']);
        $allConfigs = [];
        $configs = [];
        $allConfigs = $this->em->getRepository(Config::class)->findBy([
          'people'      => $company->getId(),
          'visibility'  => 'public'
        ]);
        foreach ($allConfigs as $config) {
          $configs[$config->getConfigKey()] = $config->getConfigValue();
        }

        if ($company) {
          $people_domains = $this->em->getRepository(PeopleDomain::class)->findBy(['people' => $com['people_id']]);

          $domains = [];

          if (!empty($people_domains)) {

            /**
             * @var PeopleDomain $company
             */
            foreach ($people_domains as $domain) {

              $domains[] = [
                'id'         => $domain->getId(),
                'domainType' => $domain->getDomainType(),
                'domain'     => $domain->getDomain()
              ];
            }
          }

          $peopleemployee =   $this->em->getRepository(PeopleEmployee::class)->findOneBy(['company' => $company, 'employee' => $userPeople]);

          $permissions[$company->getId()][] = 'salesman';
          $myCompanies[$company->getId()] = [
            'id'         => $company->getId(),
            'enabled'    => $company->getEnabled(),
            'alias'      => $company->getAlias(),
            'logo'       => $this->getLogo($company),
            'document'   => $this->getDocument($company),
            'commission' => $com['commission'],
            'domains'    => $domains,
            'configs'       => $configs,
            'user'          => [
              'id' => $userPeople->getId(),
              'name' => $userPeople->getName(),
              'alias' => $userPeople->getAlias(),
              'enabled' => $userPeople->getEnabled(),
              'employee_enabled' => $peopleemployee ? $peopleemployee->getEnabled() : $com['enable'],
              'salesman_enabled' => $com['enable']
            ]
          ];
        }
      }

      foreach ($permissions as $key => $permission) {
        $myCompanies[$key]['permission'] = $permission;
      }

      usort($myCompanies, function ($a, $b) {

        if ($a['alias'] == $b['alias']) {
          return 0;
        }
        return ($a['alias'] < $b['alias']) ? -1 : 1;
      });

      return new JsonResponse([
        'response' => [
          'data'        => $myCompanies,
          'count'       => count($myCompanies),
          'error'       => '',
          'success'     => true,
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
  private function getPeoplePackages($people)
  {


    $people_packages = $this->em->getRepository(PeoplePackage::class)->findBy(['people' => $people]);
    $packages = [];
    $p_m = [];


    foreach ($people_packages as $people_package) {
      $package = $people_package->getPackage();
      $package_modules = $this->em->getRepository(PackageModules::class)->findBy(['package' => $package]);

      foreach ($package_modules as $package_module) {
        $p_m[$package_module->getId()]['users']  = $package_module->getUsers();
        $p_m[$package_module->getId()]['module'] = $package_module->getModule()->getName();
      }

      $packages[$people_package->getId()]['id']                   =  $people_package->getId();
      $packages[$people_package->getId()]['package']['id']        =  $package->getId();
      $packages[$people_package->getId()]['package']['name']      =  $package->getName();
      $packages[$people_package->getId()]['package']['active']    =  $package->isActive() ? true : false;
      $packages[$people_package->getId()]['package']['modules']   =  $p_m;
    }

    return $packages;
  }

  private function getPeopleDomains($people)
  {
    $people_domains = $this->em->getRepository(PeopleDomain::class)->findBy(['people' => $people->getId()]);
    $domains = [];

    if (!empty($people_domains)) {

      /**
       * @var PeopleDomain $company
       */
      foreach ($people_domains as $domain) {

        $domains[] = [
          'id'         => $domain->getId(),
          'domainType' => $domain->getDomainType(),
          'domain'     => $domain->getDomain()
        ];
      }
    }
    return $domains;
  }

  private function getDocument(People $company): ?string
  {
    $documents = $company->getDocument();

    /**
     * @var \App\Entity\Document $document
     */
    $documents = $documents->filter(function ($document) {
      return $document->getDocumentType()->getDocumentType() == 'CNPJ';
    });

    return $documents->first() !== false ? $documents->first()->getDocument() : null;
  }

  private function getLogo(People $company): ?array
  {
    if ($company->getFile() instanceof File)
      return [
        'id'     => $company->getFile()->getId(),
        'domain' => $_SERVER['HTTP_HOST'],
        'url'    => '/files/download/'.$company->getFile()->getId()
      ];

    return null;
  }
}
