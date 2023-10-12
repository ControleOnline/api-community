<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Library\Nuvemshop\Client;
use App\Library\Nuvemshop\Model\User    as NuvemUser;
use App\Library\Nuvemshop\Model\Carrier as NuvemCarrier;
use App\Library\Utils\Address;
use ControleOnline\Entity\User;
use App\Entity\Quote;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrayRatesAction extends AbstractController
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;


  private $productTotalPrice = 0;

  /**
   * Request
   *
   * @var Request
   */
  private $request = null;

  /**
   * Message bus
   *
   * @var MessageBusInterface
   */
  private $messenger;

  public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus)
  {
    $this->manager   = $entityManager;
    $this->messenger = $messageBus;
  }

  public function __invoke(Request $request)
  {
    try {

      // verify api key      
      $user = $this->manager->getRepository(User::class)
        ->findOneBy(['apiKey' => $request->query->get('token')]);
      if ($user === null) {
        throw new \Exception('Access denied');
      } else {

        // auto log

        $this->get('security.token_storage')
          ->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
          );
      }

      // make freight simulation

      $data['cep']  = $request->get('origin') ?: $request->query->get('cep');
      $data['cep_destino']  = $request->query->get('cep_destino');
      $data['envio']  = $request->query->get('envio');
      $data['num_ped']  = $request->query->get('num_ped');
      $data['prods']  = $request->query->get('prods');


      $rates = $this->getResults($data);



      $response = new StreamedResponse(function () use ($rates) {
        fputs(fopen('php://output', 'wb'), $rates);
      });

      $response->headers->set('Content-Type', 'text/xml');
      return $response;
    } catch (\Exception $e) {
      return new JsonResponse([
        'response' => [
          'data'    => null,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }
  private function getProducts($p)
  {

    $prods = array_chunk(explode(';', $p), 8);

    foreach ($prods as $item) {
      $products[] = [
        'qtd'    => $item[4],
        'weight' => $item[3],
        'height' => $item[0],
        'width'  => $item[1],
        'depth'  => $item[2]
      ];
      $this->productTotalPrice += $item[4] * $item[7];
    }

    return $products;
  }
  private function getResults(array $data)
  {

    // get origin and destination

    $origin      = new Address($data['cep']);
    $destination = new Address($data['cep_destino']);

    // get products


    $products = $this->getProducts($data['prods']);


    // make simulation, get freteclick rates

    $quote  = new Quote();

    $quote->origin            = [
      "city"    => $origin->getCity(),
      "state"   => $origin->getState(),
      "country" => $origin->getCountry(),
    ];

    $quote->destination       = [
      "city"    => $destination->getCity(),
      "state"   => $destination->getState(),
      "country" => $destination->getCountry(),
    ];

    $quote->productTotalPrice = $this->productTotalPrice;
    $quote->productType       = "Tray product";
    $quote->packages          = $products;


    $order = $this->simulate($quote);

    // prepare nuvemshop rates response

    $rates = '<?xml version="1.0"?>';
    $rates .= '<cotacao>';

    foreach ($order->quotes as $key => $quote) {
      $rates .= '<resultado>';
      $rates .= '<codigo>' . $order->id . '</codigo>';
      $rates .= '<transportadora>' . trim($quote->carrier->alias) . ' - via ' . APP_NAME . '</transportadora>';
      $rates .= '<servico>' . $quote->id . ' - ' . $quote->group->name . '</servico>';
      $rates .= '<transporte>TERRESTRE</transporte>';
      $rates .= '<valor>' . number_format($quote->total, 2, '.', '') . '</valor>';
      $rates .= '<peso>' . $key . '</peso>';
      $rates .= '<prazo_min>' . ($quote->retrieveDeadline + $quote->deliveryDeadline) . '</prazo_min>';
      $rates .= '<prazo_max>' . ($quote->retrieveDeadline + $quote->deliveryDeadline + 2) . '</prazo_max>';
      $rates .= '<imagem_frete>https://' . ($quote->carrier->image) . '</imagem_frete>';
      $rates .= '<aviso_envio>1</aviso_envio>';
      $rates .= '<entrega_domiciliar>1</entrega_domiciliar>';
      $rates .= '</resultado>';
    }
    $rates .= '</cotacao>';

    return $rates;
  }

  private function simulate(Quote $quote): object
  {
    $envelope     = $this->messenger->dispatch($quote);
    $handledStamp = $envelope->last(HandledStamp::class);
    $response     = $handledStamp->getResult();

    if ($response instanceof JsonResponse) {
      $result = json_decode($response->getContent());

      if (isset($result->response)) {
        if ($result->response->success === false) {
          throw new \Exception($result->response->error);
        }

        return $result->response->data->order;
      } else {
        throw new \Exception('Simulation response is not well formed');
      }
    }
  }
}
