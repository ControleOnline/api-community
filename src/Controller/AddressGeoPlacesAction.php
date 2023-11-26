<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\GMapsService;
use App\Library\Postalcode\PostalcodeProviderBalancer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class AddressGeoPlacesAction extends AbstractController
{
  private $gmaps = null;

  public function __construct(GMapsService $gmaps)
  {
    $this->gmaps = $gmaps;
  }



  /**
   *
   * @Route("/geo_places","GET")
   */
  public function geo_places(Request $request): JsonResponse
  {
    try {
      $items = [];
      $input = $request->get('input', false);
      
      // if input is a valid CEP
      
      if (preg_match('/^\d{8}$/', $input) === 1) {
        $provider = new PostalcodeProviderBalancer();
        $address  = $provider->search($input);
        $items    = [
          [
            'id'          => 'ChIJX48DsG31xZQR8qfMVhqfeas',
            'description' => sprintf(
              '%s - %s, %s, %s, %s',
              $address->getStreet(),
              $address->getDistrict(),
              $address->getPostalCode(),
              $address->getUF(),
              $address->getCountry()
            ),
            'country'     => $address->getCountry(),
            'state'       => $address->getUF(),
            'city'        => $address->getCity(),
            'district'    => $address->getDistrict(),
            'street'      => $address->getStreet(),
            'number'      => $address->getNumber(),
            'postal_code' => $address->getPostalCode(),
            'provider'    => $provider->getProviderCodeName(),
            ]
          ];
        } else {
        $items = $this->gmaps->search($input);
      }

      return new JsonResponse([
        'response' => [
          'data'    => $items,
          'count'   => count($items),
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }
}
