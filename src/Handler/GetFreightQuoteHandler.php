<?php

namespace App\Handler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\Query\ResultSetMapping;

use App\Library\Quote\Exception\ExceptionInterface;
use App\Library\Quote\Exception\EmptyResultsException;
use App\Library\Quote\Exception\EntityNotFoundException;

use ControleOnline\Entity\FreightQuote;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Quotation;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\Status;
use ControleOnline\Repository\TaxesRepository;
use ControleOnline\Repository\QuoteRepository;
use App\Library\Quote\View\Group as ViewGroup;
use App\Library\Quote\Core\DataBag;
use App\Library\Utils\Address;
use App\Service\PeopleRoleService;


use SDK\SDK;
use SDK\Models\QuoteRequest;
use SDK\Models\Package;
use SDK\Models\Origin;
use SDK\Models\Destination;
use SDK\Models\Config;


class GetFreightQuoteHandler implements MessageHandlerInterface
{
  private $manager;
  private $user;
  private $quote_request;

  public function __construct(
    EntityManagerInterface $manager,
    Security               $security
  ) {
    $this->manager       = $manager;
    $this->user          = $security->getUser();
  }

  public function __invoke(FreightQuote $quote)
  {
    try {

      $this->config($quote);
      $this->origin(new Address($quote->origin));
      $this->destination(new Address($quote->destination));
      $this->packages($quote->packages);
      $this->contacts($quote->contact);

      /**
       * API KEY
       */
      $api_key = "";
      $SDK = new SDK($api_key);
      $cotafacil = $SDK->cotaFacilClient();

      return new JsonResponse(json_decode($cotafacil::quote($this->quote_request)));
    } catch (\Exception $e) {
      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'error_code' => $e->getCode(),
          'line' => $e->getLine(),
          'file' => $e->getFile(),
          'success' => false,
        ],
      ]);
    }
  }

  private function packages($packages)
  {
    foreach ($packages as $p) {
      $package = new Package();
      $package->setQuantity($p['qtd']);
      $package->setWeight($p['weight']);
      $package->setHeight($p['height']);
      $package->setWidth($p['width']);
      $package->setDepth($p['depth']);
      $package->setProductType($p['type']);
      $package->setProductPrice($p['price']);
      $this->quote_request->setProductTotalPrice($this->quote_request->getProductTotalPrice() + ($p['price'] * $p['qtd']));
      $this->quote_request->setProductType($this->quote_request->getProductType() . ',' . $p['type']);
      $this->quote_request->addPackage($package);
    }
  }

  private function destination(Address $address)
  {
    $destination = new Destination();
    $destination->setCEP($address->getPostalCode());
    $destination->setStreet($address->getStreet());
    $destination->setNumber($address->getNumber());
    $destination->setComplement($address->getComplement());
    $destination->setDistrict($address->getDistrict());
    $destination->setCity($address->getCity());
    $destination->setState($address->getState());
    $destination->setCountry($address->getCountry());
    $this->quote_request->setDestination($destination);
  }

  private function origin(Address $address)
  {

    $origin = new Origin();
    $origin->setCEP($address->getPostalCode());
    $origin->setStreet($address->getStreet());
    $origin->setNumber($address->getNumber());
    $origin->setComplement($address->getComplement());
    $origin->setDistrict($address->getDistrict());
    $origin->setCity($address->getCity());
    $origin->setState($address->getState());
    $origin->setCountry($address->getCountry());
    $this->quote_request->setOrigin($origin);
  }

  private function contacts($contact)
  {
    $this->quote_request->setContact($contact);
  }

  private function config(FreightQuote $quote)
  {
    $this->quote_request = new QuoteRequest();

    $config = new Config;
    $config->setQuoteType($quote->quoteType ?: 'full');
    $config->setOrder('total');
    $config->setNoRetrieve(false); // false or true
    $config->setAppType(APP_NAME); // (string) type of aplication, example Wordpress
    $config->setDenyCarriers($quote->denyCarriers); // (int) array
    $this->quote_request->setConfig($config);
  }
}
