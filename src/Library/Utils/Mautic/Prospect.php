<?php

namespace App\Library\Utils\Mautic;

class Prospect
{

    private $company  = null;

    private $contact  = null;

    private $client   = null;

    public function __construct(Client\Contacts $client)
    {
      $this->client = $client;
    }

    public function setCompany(Model\Company $company)
    {
      $this->company = $company;

      return $this;
    }

    public function setContact(Model\Contact $contact)
    {
      $this->contact = $contact;

      return $this;
    }

    public function persist()
    {
      $response = $this->execute();

      if (isset($response['error'])) {
        throw new \Exception(
          sprintf('%s: %s', $response['error']['code'], $response['error']['message'])
        );
      }
    }

    protected function execute()
    {
      if ($this->company === null)
        throw new \Exception('Company is not defined');

      if ($this->contact === null)
        throw new \Exception('Contact is not defined');

      return ($this->client->getApi())->create([
        'company'     => $this->company->getAlias(),
        'companyname' => $this->company->getName(),
        'email'       => $this->contact->getEmail(),
        'firstname'   => $this->contact->getName(),
        'phone'       => $this->contact->getPhone(),
        'tags'        => $this->contact->getTagList(),
      ]);
    }
}
