<?php

namespace App\Library\SSW;

use ControleOnline\Entity\SalesOrder;
use App\Library\SSW\Entity\Tracking;
use GuzzleHttp\Client as GuzzClient;

class Client
{

  /**
   * Retrieve Order tracking
   *
   * @param  string $nf Número da nota fiscal
   * @return array|null
   */
  public function getTracking(string $nf): ?array
  {
    try {

      $options  = ['json' => ['chave_nfe' => $nf]];
      $response = (new GuzzClient())
        ->post('https://ssw.inf.br/api/trackingdanfe', $options);

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        if ($result->success === true) {
          if (isset($result->documento) && is_array($result->documento->tracking)) {
            $tracking = [];

            foreach ($result->documento->tracking as $status) {
              $tracking[] = (new Tracking)
                ->setDataHora($status->data_hora)
                ->setDominio($status->dominio)
                ->setFilial($status->filial)
                ->setCidade($status->cidade)
                ->setOcorrencia($status->ocorrencia)
                ->setDescricao($status->descricao)
                ->setTipo($status->tipo)
                ->setDataHoraEfetiva($status->data_hora_efetiva)
                ->setNomeRecebedor($status->nome_recebedor)
                ->setNroDocRecebedor($status->nro_doc_recebedor);
            }
          }

          return $tracking;
        }
      }

      return null;
    } catch (\Exception $e) {
      return null;
    }
  }




  /**
   * Retrieve Order tracking
   *
   * @param  string $nf Número da nota fiscal
   * @return array|null
   */
  public function putRetrieve(SalesOrder $order): ?array
  {
    try {

      $carrier = $order->getQuote()->getCarrier();
      $configs = $carrier->getConfig();

      $options  = ['json' => [
        'dominio' => $configs->filter(function ($config) {
          return $config->getConfigKey() == 'ssw-dominio';
        }),
        'login' => $configs->filter(function ($config) {
          return $config->getConfigKey() == 'ssw-login';
        }),
        'senha' => $configs->filter(function ($config) {
          return $config->getConfigKey() == 'ssw-senha';
        }),

        'tipoPagamento' => 'T',
        'limiteColeta' => date('Y-m-dTH:i:s', strtotime('+ 2 weekdays')),

        'chaveNF',
        'numeroNF',
        'cnpjRemetente',        
        'solicitante',
        'cepEntrega',
        'quantidade',
        'peso',
        'cnpjSolicitante',
        'nroPedido',
        'cubagem',
        'valorMerc',


        'observacao',
        'instrucao',

      ]];
      $response = (new GuzzClient())
        ->post('https://ssw.inf.br/ws/sswColeta', $options);

      if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody());

        print_r($result);
        exit;


        //return $tracking;

      }

      return null;
    } catch (\Exception $e) {
      return null;
    }
  }
}
