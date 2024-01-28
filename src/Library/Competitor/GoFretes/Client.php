<?php

namespace App\Library\Competitor\GoFretes;

use ControleOnline\Entity\Order;
use App\Library\Rates\Exception\ClientRequestException;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;


class Client
{
  private $token = 'b5780cae-4a08-46f8-998e-0d4fe6089bc2';
  private $url = 'https://api.gofretes.com.br';


  public function quote(Order $order)
  {
    $pkg = $order->getOrderPackage();
    for ($i = 0; $i < count($pkg); $i++) {
      foreach ($pkg as $package) {
        $packages[] = [
          'peso' => ceil($package->getWeight()),
          'largura'  => ceil($package->getWidth()) * 100,
          'comprimento'  => ceil($package->getDepth() * 100),
          'altura' => ceil($package->getHeight() * 100),
          'valor' => ($order->getInvoiceTotal() / count($pkg)) / $package->getQtd(),
        ];
      }
    }
    $body = [

      'Produtos' => $packages,
      'Origem' => [
        'logradouro' => "",
        'numero' => "",
        'complemento' => "",
        'bairro' => "",
        'referencia' => "",
        'cep' => strval($order->getAddressOrigin()->getStreet()->getCep()->getCep()),
      ],
      'Destino' => [
        'logradouro' => "",
        'numero' => "",
        'complemento' => "",
        'bairro' => "",
        'referencia' => "",
        'cep' =>  strval($order->getAddressDestination()->getStreet()->getCep()->getCep()),
      ],
      'Token' => $this->token,
    ];



    $client = new GuzzleHttpClient();
    $response = $client->request('POST', $this->url . '/cotacao', [
      'body' => json_encode($body),
      'headers' => [
        'Content-Type'   => 'application/ld+json',
      ]
    ]);

    $data = json_decode($response->getBody(), false);

    if ($data->codigo == 1) {
      $data->body = $body;
      $data->competitor['carrier'] = 'Não informado';
      $data->competitor['price'] = str_replace(',', '.', $data->cotacao->valor);
      $data->competitor['deadline'] = $data->cotacao->prazo_min . "/" . $data->cotacao->prazo_max;
    } else {
      throw new Exception($data->descricao, $data->codigo);
    }
    return $data;
  }
}
