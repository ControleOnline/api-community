<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class KarrierRepository
{
  public function __construct(EntityManagerInterface $manager)
  {
    $this->manager = $manager;
  }

  public function getCompanyCarriersGroupsAndTaxes(int $companyId, array $filters = []): ?array
  {
    $sql = '
        SELECT
          pc.carrier_id            AS carrier_id,
        	pe.name                  AS carrier_name,
          pe.alias                 AS carrier_alias,
          im.url                   AS carrier_file,
          dc.document              AS carrier_cnpj,
          pe.enable                AS carrier_enabled,

          dg.id                    AS dgroup_id,
        	dg.group_name            AS dgroup_name,
          dg.max_height            AS dgroup_maxheight,
          dg.max_width             AS dgroup_maxwidth,
          dg.max_depth             AS dgroup_maxdepth,
          dg.min_cubage            AS dgroup_mincubage,
          dg.max_cubage            AS dgroup_maxcubage,
          dg.marketplace           AS dgroup_marketplace,

          dt.id                    AS tax_id,
        	dt.tax_name              AS tax_name,
          dt.tax_description       AS tax_description,
          dt.tax_type              AS tax_type,
          dt.final_weight          AS tax_finalweight,
          dt.price                 AS tax_price,
        	dt.region_origin_id      AS tax_regionorigin,
          dt.region_destination_id AS tax_regiondestination,
          dt.alter_date            AS tax_alterdate

        FROM people_carrier pc

        	LEFT JOIN people              pe  ON pe.id                    = pc.carrier_id
          LEFT JOIN document            dc  ON dc.people_id             = pc.carrier_id AND dc.document_type_id in (select id FROM document_type WHERE document_type = \'CNPJ\')
          LEFT JOIN files               im  ON im.id                    = pe.image_id

          INNER JOIN delivery_tax_group dg  ON dg.carrier_id            = pc.carrier_id
        	INNER JOIN delivery_tax       dt  ON dt.delivery_tax_group_id = dg.id

        WHERE pc.company_id = :company_id AND (dt.region_origin_id IS NOT NULL OR dt.region_destination_id IS NOT NULL)
      ';

    if (isset($filters['carrierEnabled']))
      $sql .= ' AND pe.enable = :carrier_status';

    if (isset($filters['taxGroupMarketplace']))
      $sql .= ' AND dg.marketplace = :marketplace';

    if (isset($filters['taxAlterdate']))
      $sql .= ' AND DATE(dt.alter_date) = :alter_date';

    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('carrier_id', 'carrierId');
    $rsm->addScalarResult('carrier_name', 'carrierName');
    $rsm->addScalarResult('carrier_alias', 'carrierAlias');
    $rsm->addScalarResult('carrier_file', 'carrierFile');
    $rsm->addScalarResult('carrier_cnpj', 'carrierCnpj');
    $rsm->addScalarResult('carrier_enabled', 'carrierEnabled');

    $rsm->addScalarResult('dgroup_id', 'dgroupId');
    $rsm->addScalarResult('dgroup_name', 'dgroupName');
    $rsm->addScalarResult('dgroup_maxheight', 'dgroupMaxheight');
    $rsm->addScalarResult('dgroup_maxwidth', 'dgroupMaxwidth');
    $rsm->addScalarResult('dgroup_maxdepth', 'dgroupMaxdepth');
    $rsm->addScalarResult('dgroup_mincubage', 'dgroupMincubage');
    $rsm->addScalarResult('dgroup_marketplace', 'dgroupMarketplace');

    $rsm->addScalarResult('tax_id', 'taxId');
    $rsm->addScalarResult('tax_name', 'taxName');
    $rsm->addScalarResult('tax_description', 'taxDescription');
    $rsm->addScalarResult('tax_type', 'taxType');
    $rsm->addScalarResult('tax_finalweight', 'taxFinalweight');
    $rsm->addScalarResult('tax_price', 'taxPrice');
    $rsm->addScalarResult('tax_regionorigin', 'taxRegionorigin');
    $rsm->addScalarResult('tax_regiondestination', 'taxRegiondestination');
    $rsm->addScalarResult('tax_alterdate', 'taxAlterdate');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);
    $nqu->setParameter('company_id', $companyId);

    if (isset($filters['carrierEnabled']))
      $nqu->setParameter('carrier_status', $filters['carrierEnabled']);

    if (isset($filters['taxGroupMarketplace']))
      $nqu->setParameter('marketplace', $filters['taxGroupMarketplace']);

    if (isset($filters['taxAlterdate']))
      $nqu->setParameter('alter_date', $filters['taxAlterdate']);

    $res = $nqu->getArrayResult();

    return empty($res) ? null : $this->hydrateCompanyCarriersGroupsAndTaxesResult($res);
  }

  public function getCompanyCarriersCities(int $companyId): ?array
  {
    $sql = '
        SELECT
        	dr.id          AS region_id,
          dr.region      AS region_name,
          cy.id          AS city_id,
          cy.city        AS city_name,
          st.state       AS state_name,
          co.countryName AS country_name

        FROM delivery_region dr

        	INNER JOIN people_carrier       pc ON pc.carrier_id = dr.people_id
          INNER JOIN delivery_region_city dc ON dc.delivery_region_id = dr.id
          INNER JOIN city                 cy ON cy.id = dc.city_id
          INNER JOIN state                st ON st.id = cy.state_id
          INNER JOIN country              co ON co.id = st.country_id

        WHERE pc.company_id = :company_id
      ';

    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('region_id', 'regionId');
    $rsm->addScalarResult('region_name', 'regionName');
    $rsm->addScalarResult('city_id', 'cityId');
    $rsm->addScalarResult('city_name', 'cityName');
    $rsm->addScalarResult('state_name', 'stateName');
    $rsm->addScalarResult('country_name', 'countryName');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('company_id', $companyId);

    $res = $nqu->getArrayResult();

    return empty($res) ? null : $this->hydrateCompanyCarriersCitiesResult($res);
  }

  public function getCompanyCarriersRestrictionMaterial(int $companyId): ?array
  {
    $sql = '
        SELECT
        	rm.people_id        AS carrier_id,
        	rm.restriction_type AS restriction_type,
          pm.material         AS product_name

        FROM delivery_restriction_material rm

        	INNER JOIN people_carrier   pc ON pc.carrier_id = rm.people_id
          INNER JOIN product_material pm ON pm.id = rm.product_material_id

        WHERE pc.company_id = :company_id
      ';

    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('carrier_id', 'carrierId');
    $rsm->addScalarResult('restriction_type', 'restrictionType');
    $rsm->addScalarResult('product_name', 'productName');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('company_id', $companyId);

    $res = $nqu->getArrayResult();

    return empty($res) ? null : $this->hydrateCompanyCarriersRestrictionMaterialResult($res);
  }

  private function hydrateCompanyCarriersGroupsAndTaxesResult(array $res): array
  {
    $result  = [];
    $keys    = ['carrier' => null, 'group' => null, 'tax' => null];
    $keys    = (object) $keys;
    $carrier = [];
    $dgroup  = [];
    $tax     = [];

    foreach ($res as $row) {
      if ($row['carrierId'] != $keys->carrier) {
        $keys->carrier = $row['carrierId'];

        $carrier = [];
        $carrier = [
          'id'     => $row['carrierId'],
          'name'   => $row['carrierName'],
          'alias'  => $row['carrierAlias'],
          'image'  => $row['carrierFile'],
          'cnpj'   => $row['carrierCnpj'],
          'groups' => [],
        ];

        $result[] = $carrier;
      }

      if ($row['dgroupId'] != $keys->group) {
        $keys->group = $row['dgroupId'];

        $dgroup = [];
        $dgroup = [
          'name'        => $row['dgroupName'],
          'maxHeight'   => $row['dgroupMaxheight'],
          'maxWidth'    => $row['dgroupMaxwidth'],
          'maxDepth'    => $row['dgroupMaxdepth'],
          'minCubage'   => $row['dgroupMincubage'],
          'marketplace' => $row['dgroupMarketplace'],
          'taxes'       => [],
        ];

        $idxCarrier = array_key_last($result);
        $result[$idxCarrier]['groups'][] = $dgroup;
      }

      if ($row['taxId'] != $keys->tax) {
        $keys->tax = $row['taxId'];

        $tax = [];
        $tax = [
          'id'          => $row['taxId'],
          'name'        => $row['taxName'],
          'type'        => $row['taxType'],
          'finalWeight' => $row['taxFinalweight'],
          'price'       => $row['taxPrice'],
          'origin'      => $row['taxRegionorigin'],
          'destination' => $row['taxRegiondestination'],
          'alterDate'   => $row['taxAlterdate'],
        ];

        $idxCarrier = array_key_last($result);
        $idxGroup   = array_key_last($result[$idxCarrier]['groups']);
        $result[$idxCarrier]['groups'][$idxGroup]['taxes'][] = $tax;
      }
    }

    return $result;
  }

  private function hydrateCompanyCarriersCitiesResult(array $res): array
  {
    $result  = [];
    $keys    = ['region' => null, 'city' => null];
    $keys    = (object) $keys;
    $region  = [];
    $city    = [];

    foreach ($res as $row) {
      if ($row['regionId'] != $keys->region) {
        $keys->region = $row['regionId'];

        $region = [];
        $region = [
          'id'     => $row['regionId'],
          'name'   => $row['regionName'],
          'cities' => [],
        ];

        $result[$keys->region] = $region;
      }

      if ($row['cityId'] != $keys->city) {
        $keys->city = $row['cityId'];

        $city = [];
        $city = [
          'cityName'    => $row['cityName'],
          'stateName'   => $row['stateName'],
          'countryName' => $row['countryName'],
        ];

        $result[$keys->region]['cities'][] = $city;
      }
    }

    return $result;
  }

  private function hydrateCompanyCarriersRestrictionMaterialResult(array $res): array
  {
    $result  = [];
    $keys    = ['carrier' => null];
    $keys    = (object) $keys;
    $carrier = [];

    foreach ($res as $row) {
      if ($row['carrierId'] != $keys->carrier) {
        $keys->carrier = $row['carrierId'];

        $carrier = [];
        $carrier = [
          'carrierId'       => $row['carrierId'],
          'restrictionType' => $row['restrictionType'],
          'materials'       => [],
        ];

        $result[$keys->carrier] = $carrier;
      }

      $result[$keys->carrier]['materials'][] = $row['productName'];
    }

    return $result;
  }
}
