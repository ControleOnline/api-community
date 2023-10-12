<?php

namespace App\Controller;

use ApiPlatform\Core\EventListener\RespondListener;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class FastCommerceAction
 * @package App\Controller
 * @Route("/fast-commerce")
 */
class FastCommerceAction extends AbstractController
{
    /**
     * @param Request $request
     * @Route("/quotes")
     */
    public function quotes(Request $request)
    {
        try {
            $apiKey = $request->request->get('api-key');
            $type = $request->request->get('type');
            $correios = $request->request->get('correios');
            $req = json_decode(trim($request->request->get('quote')), false);

            self::writeLog("Body:\n" . $request->request->get('quote'));

            if (!$apiKey || !$req) {
                throw new Exception("Incomplete request");
            }

            $origin = self::getAddressByCep($req->freightQuoteRequest->fromCEP)->response->data;

            if (count($origin) > 0) {
                $origin = $origin[0];
            }

            $destination = self::getAddressByCep($req->freightQuoteRequest->toCEP)->response->data;

            if (count($destination) > 0) {
                $destination = $destination[0];
            }

            $packages = [];

            foreach ($req->freightQuoteRequest->products as $product) {
                $packages[] = [
                    "qtd" => $product->quantity,
                    "weight" => $product->unitWeight,
                    "height" => $product->unitHeight / 100,
                    "width" => $product->unitWidth / 100,
                    "depth" => $product->unitLength / 100
                ];
            }

            $body = [
                "origin" => [
                    "country" => $origin->country,
                    "state" => $origin->state,
                    "city" => $origin->city
                ],
                "destination" => [
                    "country" => $destination->country,
                    "state" => $destination->state,
                    "city" => $destination->city
                ],
                "productTotalPrice" => $req->freightQuoteRequest->totalAmount,
                "packages" => $packages,
                "productType" => 'Material de Escritório',
                "contact" => null
            ];

            $order = self::getQuotes($apiKey, json_encode($body));

            $shippingServices = [];

            $resultRates = [];

            foreach ($order->quotes as $quote) {
                if ($correios || strpos($quote->carrier->name, 'Correios') !== false) {
                    $shippingServices[] = [
                        "serviceId" => $order->id . '.' . $quote->id,
                        "serviceName" => $quote->carrier->name,
                        "servicePrice" => $quote->total,
                        "serviceNotes" => $quote->deliveryDeadline . " dias"
                    ];
                }
            }

//            if ($type === 'simple') {
//                $lowest = null;
//                $shortest = null;
//
//                $arr = [];
//
//
//
//                if (isset($arr[0])) {
//                    $lowest = $arr[0];
//                    $shortest = $arr[0];
//                }
//
//                foreach ($arr as $rate) {
//                    if (isset($lowest) && $lowest['price'] > $rate['price']) {
//                        $lowest = $rate;
//                    }
//                }
//
//                if ($shortest['name'] === $lowest['name']) {
//                    $shortest = $arr[1];
//                    unset($arr[0]);
//                }
//
//                foreach ($arr as $rate) {
//                    if (isset($shortest) && $shortest['name'] !== $lowest['name'] && $shortest['max_delivery_date'] > $rate['max_delivery_date']) {
//                        $shortest = $rate;
//                    }
//                }
//
//                $lowest['name'] = "Menor Preço - {$lowest['name']}";
//                $lowest['max_delivery_date'] = (new \DateTime($lowest['max_delivery_date']))->modify('+3 days')->format('Y-m-d\TH:i:sO');
//                $lowest['min_delivery_date'] = (new \DateTime($lowest['min_delivery_date']))->modify('+3 days')->format('Y-m-d\TH:i:sO');
//                $shortest['name'] = "Menor Prazo - {$shortest['name']}";
//                $shortest['max_delivery_date'] = (new \DateTime($shortest['max_delivery_date']))->modify('+3 days')->format('Y-m-d\TH:i:sO');
//                $shortest['min_delivery_date'] = (new \DateTime($shortest['min_delivery_date']))->modify('+3 days')->format('Y-m-d\TH:i:sO');
//
//                array_push($resultRates, $lowest);
//                array_push($resultRates, $shortest);
//
//            } else {
//                $resultRates = $rates;
//            }

            $return = [
                "freightQuoteResponse" => [
//                    "err" => 123,
//                    "errDescr" => "error description",
                    "useContingency" => false,
                    "useLower" => false,
                    "shippingServices" => $shippingServices
                ]
            ];

            return $this->json($return);
        } catch (Exception $exception) {
            self::writeLog("Erro:\n" . $exception->getMessage());
        }
        return $this->json(null);
    }

    public static function writeLog($message, $file = 'FastCommerce.txt'): void
    {
        $fp = fopen($file, 'a+');
        fwrite($fp, date('d/m/Y H:i') . "\n");
        fwrite($fp, $message . "\n");
        fwrite($fp, "------\n");
        fclose($fp);
    }

    /**
     * @Route("/sales")
     */
    public function getSales()
    {
        try {

            $client = new Client(['verify' => false]);
            $response = $client->request('POST', 'https://www.rumo.com.br/sistema/adm/APILogon.asp', [
                'form_params' => [
                    'StoreName' => 'Marcelo Almeida Dev',
                    'StoreID' => 45367,
                    'Username' => 'Teste',
                    'Password' => '**MN3a8d5w5**',
                    'Method' => 'ReportView',
                    'ObjectID' => 427
                ],
                'headers' => [
                    'User-Agent' => 'FastCommerce API Interface',
                    'Accept' => 'application/xml',
                ]
            ]);
            return new Response (
                mb_convert_encoding($response->getBody(), 'UTF-8', 'ISO-8859-1')
            );
        } catch (\Exception $e) {
            self::writeLog($e->getMessage());
            return $this->json($e->getMessage());
        }
    }

    public static function getQuotes(string $apiKey, string $body): ?object
    {
        try {
            $client = new Client(['verify' => false]);
            $response = $client->request('POST', 'https://'.$_SERVER['HTTP_HOST'].'/quotes', [
                'body' => $body,
                'headers' => [
                    'User-Agent' => 'testing/1.0',
                    'Accept' => 'application/json',
                    'content-type' => 'application/ld+json',
                    'X-Foo' => ['Bar', 'Baz'],
                    'api-token' => $apiKey,
                ]
            ]);

            $response = json_decode($response->getBody(), false);

            if ($response && $response->response && $response->response->data && $response->response->data->order) {
                return $response->response->data->order;
            }
        } catch (\Exception $e) {
            self::writeLog($e->getMessage());
        }

        return null;
    }

    /**
     * @param string $apiKey
     * @param string $body
     * @return object|null
     * @Route("/teste")
     */
    public function teste()
    {
        try {

            $client = new Client(['verify' => false]);
            $response = $client->request('GET', 'https://webhook.site/e6d39638-f73b-44ff-a210-14b944270a0c', [
                'headers' => [
                    'User-Agent' => 'marcelo',
                    'Accept' => 'application/json',
                    'content-type' => 'application/ld+json',
                    'X-Foo' => ['Bar', 'Baz'],
                ]
            ]);

            $response = json_decode($response->getBody(), false);

                return $this->json($response);
        } catch (\Exception $e) {
            self::writeLog($e->getMessage());
        }

        return $this->json(null);
    }

    public static function getAddressByCep(string $cep): ?object
    {
        try {

            $client = new Client(['verify' => false]);
            $response = $client->request('GET', 'https://'.$_SERVER['HTTP_HOST'].'/geo_places?input=' . $cep, [
                'headers' => [
                    'User-Agent' => 'testing/1.0',
                    'Accept' => 'application/json',
                    'X-Foo' => ['Bar', 'Baz'],
                ]
            ]);

            return json_decode($response->getBody(), false);

        } catch (\Exception $e) {
            self::writeLog($e->getMessage());
        }

        return null;
    }
}
