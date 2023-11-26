<?php

namespace App\Controller;

use App\Controller\AbstractCustomResourceAction;
use App\Library\Utils\Address;
use App\Entity\SalesOrder;
use App\Entity\PeopleDomain;
use ControleOnline\Entity\User;
use App\Entity\Order;
use App\Repository\TaxesRepository;
use App\Library\Quote\View\Group as ViewGroup;
use App\Library\Quote\Core\DataBag;
use App\Entity\DeliveryRegion;
use App\Entity\DeliveryRegionCity;
use App\Entity\People;
use App\Entity\City;
use App\Entity\State;
use App\Entity\DeliveryTax;
use App\Entity\DeliveryTaxGroup;
use App\Entity\Quotation;
use App\Entity\QuoteDetail;

class QuoteRemoteAction extends AbstractCustomResourceAction
{
  private $taxesRepository = null;

  private $references = [];
  private $params     = [];

  public function index(): ?array
  {
    $order = $this->entity(SalesOrder::class, $this->payload()->orderId);
    if ($order === null) {
      throw new \Exception('Order was not found', 404);
    }

    if ($order->getStatus()->getStatus() != 'quote') {
      throw new \Exception('Order can not be modified', 400);
    }

    return $this->getRateQuotations($order);
  }

  private function getRateQuotations(Order $order): array
  {
    $params = $this->getQuotationParams($order);
    $cached = $this->taxesRepo()->getCachedTaxesFromExternalRateServices($params);
    $taxes  = $cached;

    if (empty($cached)) {
      $data = $this->getRateQuotationsFromExternalServices($params);
      if (!empty($data)) {
        if ($this->saveRateQuotationsInCache($data, $params, $order)) {
          $taxes = $this->taxesRepo()->getCachedTaxesFromExternalRateServices($params);
        }
      }
    }

    $remoteQuotes = $this->getRatesResponse($taxes, $params);

    
    if (!empty($remoteQuotes) && count($remoteQuotes) > 0) {
      $this->saveRateQuotationsInCache($remoteQuotes, $params, $order);
    }

    return [
      'order' => [
        'id'     => $order->getId(),
        'quotes' => $remoteQuotes
      ],
    ];
  }

  private function getRatesResponse(array $taxes, array $params): array
  {
    $rates  = [];

    foreach ($taxes as $groupData) {
      $dtBag = $groupData + ['params' => $params];
      $group = new DataBag($dtBag);

      $view  = new ViewGroup($group);
      $vwRes = $view->getResults();

      if ($vwRes === null)
        continue;

      $groupTaxes = [];
      foreach ($group->taxes as $tax) {
        if (isset($vwRes[$tax->name])) {
          $groupTaxes[$tax->name] = [
            'id'           => $tax->id,
            'name'         => $tax->name,
            'description'  => $tax->description,
            'type'         => $tax->type,
            'subType'      => $tax->subType,
            'weight'       => $tax->finalWeight,
            'price'        => $tax->price,
            'minimumPrice' => $tax->minimumPrice,
            'subtotal'     => $vwRes[$tax->name],
          ];
        }
      }

      $quoteId = null;
      array_walk(
        $this->references,
        function($value) use($group, &$quoteId) {
          if (
            $value['carrier'] == $group->carrier->id
            && strtolower($value['group']) == strtolower($group->name)
          ) {
            $quoteId = $value['quotation'];
          }
        }
      );

      $rates[] = [
        'id'               => $quoteId,
        'group'            => [
          'id'   => $group->id,
          'name' => $group->name,
          'enabled' => $group->marketplace ? true : false
        ],
        'carrier'          => [
          'id'            => $group->carrier->id,
          'name'          => $group->carrier->name,
          'alias'         => $group->carrier->alias,
          'image'         => $group->carrier->file,
          'averageRating' => 5,
          'enabled'       => $group->carrier->enabled,
        ],
        'retrieveDeadline' => $group->retrieveDeadline,
        'deliveryDeadline' => $group->deliveryDeadline,
        'total'            => $vwRes['total'],
        'taxes'            => $groupTaxes,
      ];
    }

    return $rates;
  }

  private function taxesRepo(): TaxesRepository
  {
    if ($this->taxesRepository === null) {
      $this->taxesRepository = new TaxesRepository($this->manager(), $this->security());
    }

    return $this->taxesRepository;
  }

  private function getQuotationParams(Order $order,$cubage = 300): array
  {
    $totWeight = 0;
    $maxHeight = 0;
    $maxWidth  = 0;
    $maxDepth  = 0;
    $maxCubage = 0;

    $payload   = $this->payload();

    $payload->packages = [];
    foreach ($order->getOrderPackage() as $package) {
      $payload->packages[] = [
        'qtd'    => $package->getQtd(),
        'height' => $package->getHeight(),
        'width'  => $package->getWidth(),
        'depth'  => $package->getDepth(),
        'weight' => $package->getWeight(),
      ];
    }

    $payload->productTotalPrice = $order->getInvoiceTotal();

    foreach ($payload->packages as $package) {
      $maxCubage += $package['qtd'] * $package['height'] * $package['width'] * $package['depth'] * $cubage;
      $totWeight += $package['qtd'] * $package['weight'];
    }

    $maxHeight = max(array_column($payload->packages, 'height'));
    $maxWidth  = max(array_column($payload->packages, 'width' ));
    $maxDepth  = max(array_column($payload->packages, 'depth' ));

    $oAddress  = new Address($payload->origin);
    $dAddress  = new Address($payload->destination);
    $companyId = $this->getDomainCompanyId($_SERVER['HTTP_HOST']);

    return $this->params = [
      'totalWeight'            => $totWeight,
      'finalWeight'            => $maxCubage > $totWeight ? $maxCubage : $totWeight,
      'maxHeight'              => $maxHeight,
      'maxWidth'               => $maxWidth,
      'maxDepth'               => $maxDepth,
      'maxCubage'              => $maxCubage,
      'cubage'                 => $cubage,
      'productTotalPrice'      => $payload->productTotalPrice,
      'countryOriginName'      => $oAddress->getCountry(),
      'stateOriginName'        => $oAddress->getState(),
      'cityOriginName'         => $oAddress->getCity(),
      'countryDestinationName' => $dAddress->getCountry(),
      'stateDestinationName'   => $dAddress->getState(),
      'cityDestinationName'    => $dAddress->getCity(),
      'companyId'              => $companyId,
      'hasPackages'            => true,
      'packages'               => $payload->packages,
      'domainAddress'          => $_SERVER['HTTP_HOST'],
      'isMainCompany'          => $companyId == 2,
      'addressComponents'      => [
        'origin'      => $oAddress,
        'destination' => $dAddress,
      ],
      'isLoggedUser'           => ($this->security()->getUser() instanceof User),
      'denyCarriers'           => null,
    ];
  }

  private function getDomainCompanyId(string $domain)
  {
    $company = $this->repository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

    if ($company === null)
      throw new \Exception(
        sprintf('Company domain "%s" not found', $domain)
      );

    return $company->getPeople()->getId();
  }

  private function getRateQuotationsFromExternalServices(array $params): array
  {
    $rates    = [];
    $carriers = $this->repository(\App\Entity\CarrierIntegration::class)->findBy(['enable' => 1]);

    foreach ($carriers as $carrier) {
      $service = \App\Library\Rates\RateServiceFactory::create(
        $carrier->getIntegrationType(), $carrier->getIntegrationUser(), $carrier->getIntegrationPassword());

      $quotation = new \App\Library\Rates\Model\Quotation();
      $quotation->setOrigin     ($params['addressComponents']['origin']->getPostalCode());
      $quotation->setDestination($params['addressComponents']['destination']->getPostalCode());
      $quotation->setTotalPrice ($params['productTotalPrice']);

      foreach ($params['packages'] as $product) {
        $quotation->addProduct(
           (new \App\Library\Rates\Model\Product)
           ->setWidth   ($product['width'])
           ->setHeight  ($product['height'])
           ->setDepth   ($product['depth'])
           ->setWeight  ($product['weight'])
           ->setQuantity($product['qtd'])
        );
      }

      try {
        $carrierRates = $service->getRates($quotation);
        if (!empty($carrierRates)) {
          foreach ($carrierRates as $rate) {
            if (!$rate->hasError()) {
              $rates[] = $rate->setCarrierId($carrier->getCarrier()->getId());
            }
          }
        }
      } catch (\Exception $e) {
        continue;
      }
    }

    return $rates;
  }

  private function saveRateQuotationsInCache(array $data, array $params, Order $order): bool
  {
    $numberCount = 1;

    foreach ($data as $rate) {
      $carrierId = null;
      $number    = null;
      $deadline  = null;
      $price     = null;
      $table     = null;

      if (is_object($rate)) {
        $carrierId = $rate->getCarrierId();
        $number    = $rate->getNumber();
        $deadline  = $rate->getDeadline();
        $price     = $rate->getPrice();
        $table     = $rate->getTable();
      }
      else {
        $carrierId = $rate['carrier']['id'];
        $number    = $numberCount;
        $deadline  = $rate['deliveryDeadline'];
        $price     = $rate['total'];
        $table     = $rate['group']['name'];
      }

      $regionOriginName = sprintf('Origem %s-%s-%s' , $params['stateOriginName']     , $carrierId, $number);
      $regionDestinName = sprintf('Destino %s-%s-%s', $params['stateDestinationName'], $carrierId, $number);

      $regionOrigin = $this->repository(DeliveryRegion::class)
        ->findOneBy([
          'region' => $regionOriginName,
          'people' => $this->entity(People::class, $carrierId)
        ]);
      $regionOriginId = $regionOrigin === null ? null : $regionOrigin->getId();
      if ($regionOriginId === null) {
        $regionOriginId = $this->repository(DeliveryRegion::class)
          ->insert([
            'region'       => $regionOriginName,
            'people_id'    => $carrierId,
            'deadline'     => 0,
            'retrieve_tax' => 0,
          ]);
      }

      $regionDestin = $this->repository(DeliveryRegion::class)
        ->findOneBy([
          'region' => $regionDestinName,
          'people' => $this->entity(People::class, $carrierId)
        ]);
      $regionDestinId = $regionDestin === null ? null : $regionDestin->getId();
      if ($regionDestinId === null) {
        $regionDestinId = $this->repository(DeliveryRegion::class)
          ->insert([
            'region'       => $regionDestinName,
            'people_id'    => $carrierId,
            'deadline'     => $deadline,
            'retrieve_tax' => 0,
          ]);
      }

      $originCityEntity = $this->getCityEntity($params['addressComponents']['origin']);
      $regionOrigCity   = $this->repository(DeliveryRegionCity::class)
        ->findOneBy([
          'region' => $this->entity(DeliveryRegion::class, $regionOriginId),
          'city'   => $originCityEntity
        ]);
      $originCityId = $regionOrigCity === null ? null : $regionOrigCity->getId();
      if ($originCityId === null) {
        $originCityId = $this->repository(DeliveryRegionCity::class)
          ->insert([
            'delivery_region_id' => $regionOriginId,
            'city_id'            => $originCityEntity->getId()
          ]);
      }

      $destinCityEntity = $this->getCityEntity($params['addressComponents']['destination']);
      $regionDestCity   = $this->repository(DeliveryRegionCity::class)
        ->findOneBy([
          'region' => $this->entity(DeliveryRegion::class, $regionDestinId),
          'city'   => $destinCityEntity
        ]);
      $destinCityId = $regionDestCity === null ? null : $regionDestCity->getId();
      if ($destinCityId === null) {
        $destinCityId = $this->repository(DeliveryRegionCity::class)
          ->insert([
            'delivery_region_id' => $regionDestinId,
            'city_id'            => $destinCityEntity->getId()
          ]);
      }

      $carrier  = $this->entity(People::class, $carrierId);
      $taxGroup = $this->repository(DeliveryTaxGroup::class)
        ->findOneBy([
          'carrier'   => $carrier,
          'groupName' => $table,
          'remote'    => 1
        ]);

      if (empty($taxGroup)) {
        throw new \Exception(
          sprintf('Carrier "%s" without group name "%s"',
            $carrier->getId(), $table)
        );
      }

      $deliveryTax = $this->repository(DeliveryTax::class)
        ->findOneBy([
          'taxName'           => 'Frete',
          'taxType'           => 'fixed',
          'finalWeight'       => $params['finalWeight'],
          'regionOrigin'      => $regionOrigin,
          'regionDestination' => $regionDestin,
          'groupTax'          => $taxGroup,
        ]);
      if ($deliveryTax === null) {
        $deliveryTaxId = $this->repository(DeliveryTax::class)
          ->insert([
            'tax_name'              => 'Frete',
            'tax_type'              => 'fixed',
            'final_weight'          => $params['finalWeight'],
            'region_origin_id'      => $regionOriginId,
            'region_destination_id' => $regionDestinId,
            'tax_order'             => 0,
            'price'                 => $price,
            'minimum_price'         => 0,
            'optional'              => 0,
            'delivery_tax_group_id' => $taxGroup->getId(),
          ]);
        $deliveryTax = $this->entity(DeliveryTax::class, $deliveryTaxId);
      }

      $quotation = (new Quotation())
        ->setClient         ($order->getClient())
        ->setProvider       ($order->getProvider())
        ->setCarrier        ($carrier)
        ->setCityOrigin     ($originCityEntity)
        ->setCityDestination($destinCityEntity)
        ->setOrder          ($order)
        ->setDeadline       ($deadline)
        ->setTotal          ($price)
        ->setDenied         (false)
      ;
      $this->persist($quotation);

      $detail = (new QuoteDetail())
        ->setQuote            ($quotation)
        ->setTaxName          ($deliveryTax->getTaxName())
        ->setTaxDescription   ($deliveryTax->getTaxDescription())
        ->setTaxType          ($deliveryTax->getTaxType())
        ->setFinalWeight      ($deliveryTax->getFinalWeight())
        ->setRegionOrigin     ($deliveryTax->getRegionOrigin())
        ->setRegionDestination($deliveryTax->getRegionDestination())
        ->setTaxOrder         ($deliveryTax->getTaxOrder())
        ->setPrice            ($deliveryTax->getPrice())
        ->setMinimumPrice     ($deliveryTax->getPrice())
        ->setOptional         ($deliveryTax->getOptional())
        ->setPriceCalculated  ($deliveryTax->getPrice())
      ;
      $this->persist($detail);

      $this->references[] = [
        'carrier'   => $carrier->getId(),
        'group'     => $table,
        'quotation' => $quotation->getId(),
      ];

      $numberCount++;
    }

    return true;
  }

  private function getCityEntity(Address $address): ?City
  {
    $city  = null;
    $state = $this->repository(State::class)->findOneBy(['uf' => $address->getState()]);
    if ($state instanceof State) {
      $city = $this->repository(City::class)
        ->findOneBy([
          'city'  => $address->getCity(),
          'state' => $state,
        ]);
    }

    return $city;
  }
}
