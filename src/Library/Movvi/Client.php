<?php

namespace App\Library\Movvi;

use App\Library\Movvi\Entity\Tracking;
use GuzzleHttp\Client as GuzzClient;

class Client
{



  private function getCLients()
  {
    $clients = array(
      [
        'session' => 'f4r941ohk592p0f387642fb9d86age08',
        'client' => '28214795000150'
      ],
      [
        'session' => 'f4r941ohk592p0f387642fb9d86age08',
        'client' => '34190533000103'
      ],
      [
        'session' => 'f4r941ohk592p0f387642fb9d86age08',
        'client' => '43092363000107'
      ]
    );

    return $clients;
  }

  /**
   * Retrieve Order tracking
   *
   * @param  string $nf Número da nota fiscal
   * @return array|null
   */
  public function getTracking(string $nf): ?array
  {
    try {
      $tracking = [];

      foreach ($this->getCLients() as $client) {
        $options  = ['json' =>
        [
          'cliente' => $client['client'],
          'sessao' => $client['session'],
          'chaves' => [$nf]
        ]];
        $response = (new GuzzClient())
          ->post('http://api.meridionalcargas.com.br/v4/tracking/', $options);

        if ($response->getStatusCode() === 200) {
          $result = json_decode($response->getBody());



          if (is_array($result)) {
            foreach ($result as $r) {
              foreach ($r->ocorrencias as $status) {
                $tracking[] = (new Tracking)
                  ->setDescricao($status->descricao)
                  ->setOcorrencia($status->descricao . '( ' . $status->codigo . ' )')
                  ->setDataHoraEfetiva($status->data)
                  ->setDataHora($status->data)
                  ->setFilial($status->unidade)
                  ->setCidade($status->unidade)
                  ->setDominio($status->conhecimento)
                  ->setTrackingNumber($status->codigo)
                  ->setTipo(null)
                  ->setNomeRecebedor(null)
                  ->setNroDocRecebedor(null);
              }
            }
          }
        }
      }

      return $tracking;


      return null;
    } catch (\Exception $e) {
      return null;
    }
  }
}
