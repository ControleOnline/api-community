<?php

namespace App\Controller;

use App\Controller\AbstractCustomResourceAction;
use App\Entity\Config;
use App\Entity\People;

class GetSettingAction extends AbstractCustomResourceAction
{
  public function index(): ?array
  {
    try {

      $people = $this->manager()->getRepository(People::class)
        ->find($this->payload()->id);
      if ($people === null) {
        throw new \Exception('People not found');
      }

      $repository = $this->manager()->getRepository(Config::class);

      return [
        'integrations' => [
          'default_provider' => $this->getIntegrationsDefaultProvider($people),
          'zapsign'          => $this->getIntegrationsZapsign($people),
          'clicksign'        => $this->getIntegrationsClicksign($people),
        ],
      ];

    } catch (\Exception $e) {
      if ($this->manager()->getConnection()->isTransactionActive()) {
        $this->manager()->getConnection()->rollBack();
      }

      throw new \Exception($e->getMessage());
    }
  }

  private function getIntegrationsDefaultProvider(People $people): array
  {
    $configs = $this->manager()->getRepository(Config::class)
      ->getKeyValuesByPeople($people, 'provider');

    $params = [
      'provider-signature' => [
        'type'  => 'string',
        'value' => null,
      ]
    ];

    if (is_array($configs)) {
      $params['provider-signature']['value'] = isset($configs['provider-signature']) ?
        $configs['provider-signature'] : null;
    }

    return $params;
  }

  private function getIntegrationsZapsign(People $people): array
  {
    $configs = $this->manager()->getRepository(Config::class)
      ->getKeyValuesByPeople($people, 'zapsign');

    $params = [
      'zapsign-token'   => [
        'type'     => 'string',
        'value'    => null,
      ],
      'zapsign-webhook'   => [
        'type'     => 'string',
        'value'    => null,
        'readonly' => true,
      ],
      'zapsign-sandbox' => [
        'type'  => 'bool',
        'value' => '0',
      ],
    ];

    if (is_array($configs)) {
      $params['zapsign-token']['value'] = isset($configs['zapsign-token']) ?
        $configs['zapsign-token'] : null;

      $params['zapsign-webhook']['value'] = isset($configs['zapsign-webhook']) ?
        $configs['zapsign-webhook'] : null;

      $params['zapsign-sandbox']['value'] = isset($configs['zapsign-sandbox']) ?
        $configs['zapsign-sandbox'] : '0';
    }

    if ($params['zapsign-webhook']['value'] === null) {
      $params['zapsign-webhook']['value'] = sprintf(
        'https://%s/my_contracts/signatures-finished/zapsign',
        $_SERVER['HTTP_HOST']
      );
    }

    return $params;
  }

  private function getIntegrationsClicksign(People $people): array
  {
    $configs = $this->manager()->getRepository(Config::class)
      ->getKeyValuesByPeople($people, 'clicksign');

    $params = [
      'clicksign-token'   => [
        'type'  => 'string',
        'value' => null,
      ],
      'clicksign-webhook'   => [
        'type'     => 'string',
        'value'    => null,
        'readonly' => true,
      ],
      'clicksign-sandbox' => [
        'type'  => 'bool',
        'value' => '0',
      ],
    ];

    if (is_array($configs)) {
      $params['clicksign-token']['value'] = isset($configs['clicksign-token']) ?
        $configs['clicksign-token'] : null;

      $params['clicksign-webhook']['value'] = isset($configs['clicksign-webhook']) ?
        $configs['clicksign-webhook'] : null;

      $params['clicksign-sandbox']['value'] = isset($configs['clicksign-sandbox']) ?
        $configs['clicksign-sandbox'] : '0';
    }

    if ($params['clicksign-webhook']['value'] === null) {
      $params['clicksign-webhook']['value'] = sprintf(
        'https://%s/my_contracts/signatures-finished/clicksign',
        $_SERVER['HTTP_HOST']
      );
    }

    return $params;
  }
}
