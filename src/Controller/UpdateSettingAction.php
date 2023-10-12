<?php

namespace App\Controller;

use App\Controller\AbstractCustomResourceAction;
use App\Entity\Config;
use App\Entity\People;

class UpdateSettingAction extends AbstractCustomResourceAction
{
  public function index(): ?array
  {
    try {

      $people = $this->manager()->getRepository(People::class)
        ->find($this->payload()->id);
      if ($people === null) {
        throw new \Exception('People not found');
      }

      $this->manager()->getConnection()->beginTransaction();

      // update integrations

      if ($this->payload()->integrations !== null) {
        foreach ($this->payload()->integrations as $key => $value) {
          $config = $this->manager()->getRepository(Config::class)
            ->findOneBy([
              'people'     => $people,
              'config_key' => $key,
            ]);

          if ($config instanceof Config) {
            $this->manager()->getRepository(Config::class)
              ->updatePeopleConfigKey($people, $key, $value);
          }
          else {
            $config = (new Config)
              ->setPeople     ($people)
              ->setConfigKey  ($key)
              ->setConfigValue($value)
            ;

            $this->manager()->persist($config);
          }
        }

      }

      $this->manager()->flush();
      $this->manager()->getConnection()->commit();

      return [
        'id' => $this->payload()->id,
      ];

    } catch (\Exception $e) {
      if ($this->manager()->getConnection()->isTransactionActive()) {
        $this->manager()->getConnection()->rollBack();
      }

      throw new \Exception($e->getMessage());
    }
  }
}
