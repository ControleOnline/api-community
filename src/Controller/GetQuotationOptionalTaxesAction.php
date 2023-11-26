<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Quotation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Library\Quote\View\Group   as TaxesView;
use App\Library\Quote\Core\DataBag as TaxesData;

class GetQuotationOptionalTaxesAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  public function __construct(EntityManagerInterface $manager)
  {
    $this->manager = $manager;
  }

  public function __invoke(Quotation $data): JsonResponse
  {
    try {

      $result = false;
      $taxes  = $this->getCarrierOptionalDeliveryTaxes($data);

      if (!empty($taxes)) {
        $values = [
          'taxes'   => $taxes,
          'carrier' => [
            'icms' => $data->getCarrier()->getIcms()
          ],
        ];

        $tdata = new TaxesData($values, $this->getParams($data));
        $tview = new TaxesView($tdata);
        $dview = $tview->getResults();

        if (is_array($dview)) {
          $result = [];
          foreach ($dview as $taxName => $taxValue) {
            if ($taxName !== 'total') {
              $result[] = [
                'id'    => $taxes[$taxName]['id'],
                'name'  => $taxName,
                'value' => $taxValue,
              ];
            }
          }
        }
      }

      return new JsonResponse([
        'response' => [
          'data'    => $result,
          'count'   => is_array($result) ? count($result) : 0,
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

  private function getParams(Quotation $quotation): TaxesData
  {
    /**
     * @var \App\Entity\SalesOrder
     */
    $order    = $quotation->getOrder();
    $oaddress = $this->getAddressData($order->getAddressOrigin());
    $daddress = $this->getAddressData($order->getAddressDestination());

    $params = new TaxesData([
      'productTotalPrice'      => $order->getInvoiceTotal(),
      'cityOriginName'         => $oaddress['city'   ],
      'stateOriginName'        => $oaddress['state'  ],
      'countryOriginName'      => $oaddress['country'],
      'cityDestinationName'    => $daddress['city'   ],
      'stateDestinationName'   => $daddress['state'  ],
      'countryDestinationName' => $daddress['country'],
      'regionDestinationId'    => null,
    ]);

    return $params;
  }

  private function getAddressData(?Address $address): array
  {
    $data = [
      'city'    => '',
      'state'   => '',
      'country' => '',
    ];

    if ($address === null)
      return $data;

    $street   = $address->getStreet();
    $district = $street->getDistrict();
    $city     = $district->getCity();
    $state    = $city->getState();

    $data['city'   ] = $city->getCity();
    $data['state'  ] = $state->getState();
    $data['country'] = $state->getCountry()->getCountryname();

    return $data;
  }

  private function getCarrierOptionalDeliveryTaxes(Quotation $quotation): array
  {
    $conn = $this->manager->getConnection();

    $sql = '
      SELECT
        dta.id                    AS id,
        UPPER(TRIM(dta.tax_name)) AS name,
        dta.tax_type              AS type,
        dta.tax_description	      AS description	,
        dta.tax_subtype           AS subType,
        dta.final_weight          AS finalWeight,
        dta.price                 AS price,
        dta.minimum_price         AS minimumPrice

      FROM delivery_tax dta

        INNER JOIN delivery_tax_group  dgo ON dgo.id = dta.delivery_tax_group_id

        LEFT JOIN delivery_region      dr1 ON dr1.id = dta.region_origin_id
        LEFT JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
        LEFT JOIN delivery_region      dr2 ON dr2.id = dta.region_destination_id
        LEFT JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
        LEFT JOIN city                 cio ON cio.id = dro.city_id
        LEFT JOIN city                 cid ON cid.id = drd.city_id

      WHERE
        dta.optional       = 1
        AND dgo.carrier_id = :carrier_id
        AND (
            (dta.region_origin_id IS NULL AND dta.region_destination_id IS NULL) OR (cio.id = :city_origin AND cid.id = :city_destination)
        )
    ';

    $stmt = $conn->prepare($sql);

    $stmt->execute([
      'carrier_id'       => $quotation->getCarrier()->getId(),
      'city_origin'      => $quotation->getCityOrigin()->getId(),
      'city_destination' => $quotation->getCityDestination()->getId(),
    ]);

    $taxes = [];

    foreach ($stmt->fetchAll() as $tax) {
      $tdata = [
        'id'           => $tax['id'],
        'name'         => $tax['name'],
        'subType'      => $tax['subType'],
        'type'         => $tax['type'],
        'description'  => $tax['description'],        
        'finalWeight'  => (float) $tax['finalWeight'],
        'price'        => (float) $tax['price'],
        'minimumPrice' => (float) $tax['minimumPrice'],
      ];

      $taxes[$tax['name']] = $tdata;
    }

    return $taxes;
  }
}
