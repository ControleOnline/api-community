<?php

namespace App\Library\Competitor\Central;

use ControleOnline\Entity\SalesOrder;
use App\Library\Rates\Exception\ClientRequestException;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;


class Client
{
  private $token = '8b945d3bd5c26f2183efe438aa5ad419eac682629e4109ba4ac65f51c9cac2da';
  private $url = 'https://api.centraldofrete.com';


  public function quote(SalesOrder $order)
  {

    foreach ($order->getOrderPackage() as $package) {
      $packages[] = [
        'quantity'    => $package->getQtd(),
        'height' => $package->getHeight() * 100,
        'width'  => $package->getWidth() * 100,
        'length'  => $package->getDepth() * 100,
        'weight' => $package->getWeight(),
      ];
    }
    $body = [
      "recipient" => ["document" => "22531311000110", "name" => "Nome do Destinatario"],
      'invoice_amount' => $order->getInvoiceTotal(),
      "from" => $order->getAddressOrigin()->getStreet()->getCep()->getCep(),
      "to" =>  $order->getAddressDestination()->getStreet()->getCep()->getCep(),
      "origin_document" => "111.948.110-43",
      "destination_document" => "334.559.990-26",
      "volumes" => $packages,
      "cargo_types" => [
        12
      ],
    ];

    $client = new GuzzleHttpClient();
    $response = $client->request('POST', $this->url . '/v1/quotation', [
      'body' => json_encode($body),
      'headers' => [
        'source' => 'WORDPRESS',
        'Authorization' => $this->token,
        'Content-Type'   => 'application/json',
      ]
    ]);

    $data = json_decode($response->getBody(), false);

    if ($data->code) {
      $data->body = $body;
      $data->competitor['carrier'] = $data->prices[0]->shipping_carrier;
      $data->competitor['price'] = $data->prices[0]->price;
      $data->competitor['deadline'] = $data->prices[0]->delivery_time;
    } else {
      throw new Exception($data->error);
    }
    return $data;
  }
}
