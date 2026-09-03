<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

use App\Library\Utils\Mautic\Prospect;
use App\Library\Utils\Mautic\Client\Contacts;
use App\Library\Utils\Mautic\Model\Company;
use App\Library\Utils\Mautic\Model\Contact;

use ControleOnline\Entity\People as Provider;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Config;

class MauticService
{

  private $em      = null;

  private $clients = [];

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->em = $entityManager;
  }

  public function getProspect(array $data, int $providerId): Prospect
  {
    $provider = $this->em->find(Provider::class, $providerId);
    $configs  = $this->getClientData($provider);

    // configure client

    $contacts = new Contacts($configs['endpoint']);

    $contacts->setAuth($configs['username'], $configs['password']);

    // configure data

    $company  = new Company();
    $contact  = new Contact();

    $company->setAlias($provider->getAlias());
    $company->setName ($provider->getName ());

    $contact
      ->setEmail($data['email'])
      ->setName ($data['name' ])
      ->setPhone($data['phone'])
      ->setTags ([
        'prospect',
        'quote'   ,
      ]);

    $contact->addTag('quote-' . strtolower($data['ufOrigin']));

    return (new Prospect($contacts))->setCompany($company)->setContact($contact);
  }

  private function getClientData(Provider $provider): array
  {
    $providerId = $provider->getId();

    if (isset($this->clients[$providerId]))
      return $this->clients[$providerId];

    $configs = $this->em->getRepository(Config::class)->findBy(['people' => $provider]);
    if (count($configs) == 0)
      throw new \Exception(
        sprintf('Company config data for "%s" was not found', $provider->getName())
      );

    $username = array_filter($configs, function($c) { return $c->getConfigKey() == 'mautic-basic-auth-user';     });
    $password = array_filter($configs, function($c) { return $c->getConfigKey() == 'mautic-basic-auth-password'; });
    $endpoint = array_filter($configs, function($c) { return $c->getConfigKey() == 'mautic-url';                 });

    if (empty($username))
      throw new \Exception('Mautic client username config not found');

    if (empty($password))
      throw new \Exception('Mautic client password config not found');

    if (empty($endpoint))
      throw new \Exception('Mautic client endpoint config not found');

    $username = current($username)->getConfigValue();
    $password = current($password)->getConfigValue();
    $endpoint = current($endpoint)->getConfigValue();

    return $this->clients[$providerId] = ['username' => $username, 'password' => $password, 'endpoint' => $endpoint];
  }
}
