<?php

namespace App\Library\Competitor\CargoBR;

use App\Entity\SalesOrder;
use App\Library\Rates\Exception\ClientRequestException;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;


class Client
{
  private $user = 'antunesdelimasouza@gmail.com';
  private $password = 'Lw7q8a9w5@@qw';
  private $url = 'https://api.cargobr.com';


  public function quote(SalesOrder $order)
  {


    foreach ($order->getOrderPackage() as $package) {
      $packages[] = [
        'amount'    => $package->getQtd(),
        'height' =>  number_format($package->getHeight(), 2, '.', ''),
        'width'  =>  number_format($package->getWidth(), 2, '.', ''),
        'length'  => number_format($package->getDepth(), 2, '.', ''),
        'weight' =>  number_format($package->getWeight(), 2, '.', ''),
        'value' =>   number_format((($order->getInvoiceTotal() / count($order->getOrderPackage())) / $package->getQtd()), 2, '.', ''),
        'object_type' => ['branca']
      ];
    }
    $body = [
      "origin_zipcode" => $order->getAddressOrigin()->getStreet()->getCep()->getCep(),
      "destination_zipcode" =>  $order->getAddressDestination()->getStreet()->getCep()->getCep(),
      "origin_document" => "111.948.110-43",
      "destination_document" => "334.559.990-26",
      "volumes" => $packages
    ];



    $client = new GuzzleHttpClient();
    $response = $client->request('POST', $this->url . '/v1/freights/quotations/', [
      'body' => json_encode($body),
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->user . ':' . $this->password),
        'Content-Type'   => 'application/json',
      ]
    ]);

    $data = json_decode($response->getBody(), false);

    if ($data->id) {
      
     $price = array_column($data->options, 'price');
     array_multisort($price, SORT_ASC, $data->options);


      $data->body = $body;
      $data->competitor['carrier'] = $data->options[0]->company->exhibition_name;
      $data->competitor['price'] = $data->options[0]->price;
      $data->competitor['deadline'] = $data->options[0]->delivery_time->min . "/" . $data->options[0]->delivery_time->max;
    } else {
      throw new Exception($data->error);
    }
    return $data;
  }
}
