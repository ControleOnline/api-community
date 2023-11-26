<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
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

class NuvemshopRatesAction extends AbstractController
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

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
        ->findOneBy(['apiKey' => $request->query->get('api-key')]);
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

      $data  = json_decode(file_get_contents('php://input'), true);
      $rates = $this->getResults($data);

      return new JsonResponse(['rates' => $rates], 200);
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

  private function getResults(array $data): array
  {
    // get origin and destination

    $origin      = new Address($data['origin']['postal_code']);
    $destination = new Address($data['destination']['postal_code']);

    // get products

    $total    = 0;
    $products = [];
    foreach ($data['items'] as $item) {
      $products[] = [
        'qtd'    => $item['quantity'],
        'weight' => $item['grams'] / 1000,
        'height' => $item['dimensions']['height'] / 100,
        'width'  => $item['dimensions']['width']  / 100,
        'depth'  => $item['dimensions']['depth']  / 100
      ];
      $total += $item['price'] * $item['quantity'];
    }

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

    $quote->productTotalPrice = $total;
    $quote->productType       = "Nuvemshop product";
    $quote->packages          = $products;

    $order = $this->simulate($quote);

    // prepare nuvemshop rates response

    $rates  = [];
    foreach ($order->quotes as $key => $quote) {

      $date = new \DateTime('now');

      $reference = sprintf('%s-%s', $order->id, $quote->id);

      $rates[] = [
        'name'              => trim($quote->carrier->alias) . '(' . $reference . ') - via ' . APP_NAME,
        'code'              => (new NuvemCarrier)->getOptionCode(),
        'price'             => $quote->total,
        'currency'          => 'BRL',
        'type'              => 'ship',
        'min_delivery_date' => $date->format('Y-m-d\TH:i:d-0300'),
        'max_delivery_date' => $date->add(new \DateInterval('P' . $quote->deliveryDeadline . 'D'))
          ->format('Y-m-d\TH:i:d-0300'),
        'phone_required'    => true,
        'reference'         => $reference,
      ];
    }

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
