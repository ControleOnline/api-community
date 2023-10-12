<?php

namespace App\Library\Itau;

use ControleOnline\Entity\Invoice;
use GuzzleHttp\Client;
use App\Library\Itau\Entity\Payment;

class ItauClient
{
  /**
   * Order entity
   *
   * @var \ControleOnline\Entity\Invoice
   */
  private $invoice = null;

  /**
   * Itau params
   *
   * @var array
   */
  private $params;

  public function __construct(Invoice $invoice, array $params)
  {
    $this->invoice = $invoice;
    $this->params  = $params;
  }  

  public function getPayment(): Payment
  {
    $client   = new Client(['verify' => false]);
    $response = $client->post($this->params['itau-shopline-status-url'], ['form_params' => ['DC' => $this->getRequestHash(1)]]);

    if (($xmlDoc = @simplexml_load_string($response->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA)) === false)
      throw new \Exception('Não foi possivel ler o arquivo xml');

    $params = [];

    if ($xmlDoc && $xmlDoc->PARAMETER && $xmlDoc->PARAMETER->PARAM) {
      foreach ($xmlDoc->PARAMETER->PARAM as $param) {
        $params[(string) $param->attributes()->ID] = (string) $param->attributes()->VALUE;
      }
    }

    return new Payment($params);
  }

  private function getRequestHash(int $returnType = 0): ?string
  {
    $codEmp  = $this->params['itau-shopline-company'];
    $chave   = $this->params['itau-shopline-key'];

    return (new Itaucripto())->geraConsulta($codEmp, $this->invoice->getId(), $returnType, $chave);
  }
}
