<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Security\Core\Security;
use App\Library\Quote\Exception\EmptyResultsException;

class TaxesRepository
{
  private $groups = [];
  private $routeType = 'simple';
  private static $output = [];
  private static $first;
  private static $second;

  public function __construct(EntityManagerInterface $manager, Security $security)
  {
    $this->manager = $manager;
    $this->user    = $security->getUser();
  }

  public function getRouteType()
  {
    return $this->routeType;
  }

  public function getMultipleTaxesByGroup(array $params): array
  {
    $quote = $this->getAllTaxesByGroup($params);
    if (empty($quote)) {

      $this->routeType = 'multiple';

      $sql = "
     SELECT 
     DISTINCT CONCAT( GROUP_CONCAT(DISTINCT dgo.id),',', GROUP_CONCAT(DISTINCT DEST.grp_id)) AS groupId,
     GROUP_CONCAT(DISTINCT DEST.DORC) AS cityId

          FROM delivery_tax dtb      
           INNER JOIN delivery_tax_group   dgo ON dgo.id = dtb.delivery_tax_group_id
           INNER JOIN delivery_region      dr1 ON dr1.id = dtb.region_origin_id
           INNER JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
           INNER JOIN city                 cio ON cio.id = dro.city_id
           INNER JOIN state                sto ON sto.id = cio.state_id
           INNER JOIN country              coo ON coo.id = sto.country_id
           
           INNER JOIN delivery_region      dr2 ON dr2.id = dtb.region_destination_id
           INNER JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
           INNER JOIN city                 cid ON cid.id = drd.city_id
           INNER JOIN state                stn ON stn.id = cid.state_id
           INNER JOIN country              cod ON cod.id = stn.country_id            
           
           INNER JOIN (
               SELECT dgo.id AS grp_id,dta.id,cio.id AS DORC,sto.id AS DORG,
               cio.city AS C,sto.UF AS U,
               cid.city,stn.UF
                   FROM delivery_tax dta  
                   INNER JOIN delivery_tax_group   dgo ON dgo.id = dta.delivery_tax_group_id    
                   INNER JOIN delivery_region      dr1 ON dr1.id = dta.region_origin_id
                   INNER JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
                   INNER JOIN city                 cio ON cio.id = dro.city_id
                   INNER JOIN state                sto ON sto.id = cio.state_id
                   INNER JOIN country              coo ON coo.id = sto.country_id
     
                   INNER JOIN delivery_region      dr2 ON dr2.id = dta.region_destination_id
                   INNER JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
                   INNER JOIN city                 cid ON cid.id = drd.city_id
                   INNER JOIN state                stn ON stn.id = cid.state_id
                   INNER JOIN country              cod ON cod.id = stn.country_id    
               
                   WHERE (cid.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name) AND cod.countryName IN (:country_destination_name))
               
               GROUP BY DORC,DORG
           ) DEST ON DEST.DORC = cid.id AND DEST.DORG = stn.id
           
           WHERE (cio.city IN (:city_origin_name) AND sto.UF IN (:state_origin_name) AND cod.countryName IN (:country_origin_name))
      ";

      $rsm = new ResultSetMapping();

      $rsm->addScalarResult('groupId', 'groupId');
      $rsm->addScalarResult('cityId', 'cityId');

      $nqu = $this->manager->createNativeQuery($sql, $rsm);

      $nqu->setParameter('city_origin_name', $params['cityOriginName']);
      $nqu->setParameter('state_origin_name', $params['stateOriginName']);
      $nqu->setParameter('country_origin_name', $params['countryOriginName']);
      $nqu->setParameter('city_destination_name', $params['cityDestinationName']);
      $nqu->setParameter('state_destination_name', $params['stateDestinationName']);
      $nqu->setParameter('country_destination_name', $params['countryDestinationName']);
      $nqu->setParameter('company_id', $params['companyId']);

      $res = $nqu->getArrayResult();

      if (!empty($res)) {

        $params['found_groups'] = explode(',', $res[0]['groupId']);
        $params['found_cities'] = explode(',', $res[0]['cityId']);

        $params['found_path'] = '1';
        $first = $this->getAllTaxesFromFirstRouteByGroup($params);

        $params['found_path'] = '2';
        $second = $this->getAllTaxesByGroup($params, true);

        if ($first && $second) {
          $quote =  self::$output;
        }
      }
    }

    return $quote;
  }


  public function getAllTaxesFromFirstRouteByGroup(array $params): array
  {
    $output  = self::$output;
    $isAdmin = $this->isAdmin($params);

    // get all taxes

    $result = $this->getGroupTaxesFixedKm($params, $isAdmin);
    if ($result !== null)
      $this->processGroupTaxesResult($result, $output, $params);

    $result = $this->getGroupTaxesRegionFixedOrder($params, $isAdmin);
    if ($result !== null)
      $this->processGroupTaxesResult($result, $output, $params);


    self::$output = $output;
    return $output;
  }


  public function getAllTaxesByGroup(array $params, $is_multiple = false): array
  {
    $output  = self::$output;
    $isAdmin = $this->isAdmin($params);

    // get all taxes

    $result = $this->getGroupTaxesFixedKm($params, $isAdmin);
    if ($result !== null)
      $this->processGroupTaxesResult($result, $output, $params, $is_multiple);

    $result = $this->getGroupTaxesRegionFixedOrder($params, $isAdmin);
    if ($result !== null)
      $this->processGroupTaxesResult($result, $output, $params, $is_multiple);

    $result = $this->getGroupTaxesFixed($params, $isAdmin);
    if ($result !== null)
      $this->processGroupTaxesResult($result, $output, $params, $is_multiple);

    $result = $this->getGroupTaxesRegionPercentageInvoice($params, $isAdmin);
    if ($result !== null)
      $this->processGroupTaxesResult($result, $output, $params, $is_multiple);

    $result = $this->getGroupTaxesRegionPercentageOrder($params, $isAdmin);
    if ($result !== null)
      $this->processGroupTaxesResult($result, $output, $params, $is_multiple = false);

    // ICMS TAX
    $result = $this->getTaxesCompany($params);
    if ($result !== null) {
      foreach ($output as $groupId => $group) {
        foreach ($result as $tax) {
          $taxId = 'tax-' . $tax['taxId'];

          if (!array_key_exists($taxId, $group['taxes'])) {
            $output[$groupId]['taxes'][$taxId] = [
              'id'           => $taxId,
              'name'         => $tax['taxName'],
              'description'  => $tax['taxDescription'],
              'type'         => $tax['taxType'],
              'subType'      => $tax['taxSubType'],
              'finalWeight'  => $tax['taxFinalWeight'],
              'price'        => $tax['taxPrice'],
              'minimumPrice' => $tax['taxMinimumPrice'],
              'deadline'     => $tax['taxDeadline'],
            ];
          }
        }
      }
    }
    self::$output = $output;
    return $output;
  }

  public function getOutput()
  {
    return self::$output;
  }

  public function getCachedTaxesFromExternalRateServices(array $params): array
  {
    $output = [];
    $result = $this->getGroupTaxesFromRemoteCarriers($params, $this->isAdmin($params));

    if ($result !== null) {
      $this->processGroupTaxesResult($result, $output, $params);
    }

    return $output;
  }

  private function isAdmin(array $params): bool
  {
    $isAdmin = false;

    if ($params['mainOrder']) {
      return false;
    }

    if ($params['isLoggedUser']) {
      $provider = null;

      if ($params['isMainCompany']) {
        $stateo  = $this->manager->getRepository(\App\Entity\State::class)->findOneBy(['uf' => $params['stateOriginName']]);
        $pstates = $this->manager->getRepository(\App\Entity\PeopleStates::class)
          ->findBy(['state' => $stateo]);

        if (!empty($pstates)) {
          $provider = $pstates[0]->getPeople();
        }
      } else {
        $provider = $this->manager->find(\App\Entity\People::class, $params['companyId']);
      }

      if ($provider !== null) {
        $people  = $this->user->getPeople();
        $isAdmin = $provider->getPeopleEmployee()
          ->exists(
            function ($key, \App\Entity\PeopleEmployee $peopleEmployee) use ($people) {
              return $peopleEmployee->getEmployee() === $people;
            }
          );
      }
    }

    return $isAdmin;
  }

  private function getTaxesCompany(array $filters): ?array
  {
    $sql = "
      SELECT

        tax.id              AS tax_id,
        tax.tax_name        AS tax_name,        
        tax.tax_type        AS tax_type,
        tax.tax_subtype     AS tax_subtype,
        null                AS tax_final_weight,
        tax.price           AS tax_price,
        tax.minimum_price   AS tax_minimum_price

      FROM tax

      INNER JOIN city          cor ON cor.state_id = tax.state_origin_id
      INNER JOIN state         sto ON sto.id = cor.state_id
      INNER JOIN city          cde ON cde.state_id = tax.state_destination_id
      INNER JOIN state         stn ON stn.id = cde.state_id      
      ";

    /*
      $sql .= "
      INNER JOIN people        peo ON peo.id = tax.people_id
      INNER JOIN people_states pes ON pes.people_id = peo.id
      INNER JOIN state         stt ON stt.id = pes.state_id
      ";
      */

    $sql .= "
      
      WHERE

        tax.optional        = 0        
        AND tax.tax_type    = 'percentage'
        AND tax.tax_subtype = 'order'
        AND tax.people_id   = :company_id

        AND (cor.city IN (:city_origin_name)      AND sto.UF IN (:state_origin_name)     )
        AND (cde.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name))
      ";



    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('tax_id', 'taxId');
    $rsm->addScalarResult('tax_name', 'taxName');    
    $rsm->addScalarResult('tax_type', 'taxType');
    $rsm->addScalarResult('tax_subtype', 'taxSubType');
    $rsm->addScalarResult('tax_final_weight', 'taxFinalWeight', 'float');
    $rsm->addScalarResult('tax_price', 'taxPrice', 'float');
    $rsm->addScalarResult('tax_deadline', 'taxDeadline', 'float');

    $rsm->addScalarResult('tax_minimum_price', 'taxMinimumPrice', 'float');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('company_id', $filters['companyId']);
    $nqu->setParameter('city_origin_name', $filters['cityOriginName']);
    $nqu->setParameter('state_origin_name', $filters['stateOriginName']);
    $nqu->setParameter('city_destination_name', $filters['cityDestinationName']);
    $nqu->setParameter('state_destination_name', $filters['stateDestinationName']);

    $res = $nqu->getArrayResult();

    return empty($res) ? null : $res;
  }

  private function getGroupTaxesFromRemoteCarriers(array $filters, bool $isAdmin = false): ?array
  {
    $addressComponents = $filters['addressComponents'];

    if (empty($addressComponents['origin']->getPostalCode())) {
      return null;
    }

    if (empty($addressComponents['destination']->getPostalCode())) {
      return null;
    }
    $sql = "
      SELECT
        dgo.id              AS groupId,
        dgo.group_name      AS groupName,
        dgo.code      AS code,
        dgo.marketplace         AS marketplace,
        pep.id              AS carrierId,
        pep.name            AS carrierName,
        pep.alias           AS carrierAlias,
        pep.icms            AS carrierIcms,
        pep.enable          AS carrierEnable,
        ima.url AS carrierFile,
        dr1.deadline        AS carrierRetrieve,
        dr2.deadline        AS carrierDeadline,
        dta.region_destination_id AS region_destination_id,
        dta.region_origin_id AS region_origin_id,      
        dta.optional AS optional,
        dta.tax_order AS tax_order,        
        dta.id              AS taxId,
        UPPER(dta.tax_name) AS taxName,
        dta.tax_description AS taxDescription,
        dta.tax_type        AS taxType,
        dta.tax_subtype     AS taxSubType,
        dta.final_weight    AS taxFinalWeight,
        dta.price           AS taxPrice,
        dta.deadline        AS tax_deadline,
        dta.minimum_price   AS taxMinimumPrice
        

      FROM delivery_tax dta

      INNER JOIN delivery_tax_group   dgo ON dgo.id = dta.delivery_tax_group_id
      INNER JOIN delivery_region      dr1 ON dr1.id = dta.region_origin_id
      INNER JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
      INNER JOIN city                 cio ON cio.id = dro.city_id
      INNER JOIN state                sto ON sto.id = cio.state_id
      INNER JOIN country              coo ON coo.id = sto.country_id

      INNER JOIN delivery_region      dr2 ON dr2.id = dta.region_destination_id
      INNER JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
      INNER JOIN city                 cid ON cid.id = drd.city_id
      INNER JOIN state                stn ON stn.id = cid.state_id
      INNER JOIN country              cod ON cod.id = stn.country_id

      INNER JOIN people_carrier       pca ON pca.carrier_id = dgo.carrier_id
      INNER JOIN people               pep ON pep.id = pca.carrier_id      

      INNER JOIN carrier_integration  cin ON cin.carrier_id = pep.id AND cin.enable = 1

      LEFT  JOIN files                ima ON ima.id = pep.image_id
      
      LEFT  JOIN delivery_restriction_material rm ON rm.people_id = pep.id AND rm.restriction_type = 'delivery_denied'
      LEFT  JOIN product_material              pm ON pm.id        = rm.product_material_id AND (pm.material IN (:product_material) OR pm.id IN (:product_material))

      WHERE
        dta.optional           = 0
        AND dgo.remote         = 1
        AND dta.final_weight  >= :final_weight
        AND dta.tax_type       = 'fixed'
        AND pca.company_id     = :company_id
        AND pep.people_type    = 'J'        
      ";



    if (!$isAdmin) {
      $sql .= "
        AND pep.enable = 1
        ";
      $sql .= $filters['isMainCompany'] ? " AND dgo.marketplace = 1" : "";
    }

    if ($filters['denyCarriers'])
      $sql .= "
  			AND pep.id NOT IN (:denyCarriers)
  			";

    if ($filters['hasPackages'])
      $sql .= "
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :max_cubage)
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= "
          AND (dgo.min_cubage <= :max_cubage AND dgo.max_cubage >= :max_cubage)
          AND (dgo.min_cubage <= :final_weight AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= $filters['isMainCompany'] ? "" : " AND dgo.website = 1";

    $sql .= "
        AND (cio.city IN (:city_origin_name) AND sto.UF IN (:state_origin_name) AND coo.countryName IN (:country_origin_name))

        AND (cid.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name) AND cod.countryName IN (:country_destination_name))

        GROUP BY taxId
        HAVING COUNT(pm.material) = 0
      ORDER BY dgo.id, pep.id, tax_name, dta.final_weight ASC
      ";

    $conn   = $this->manager->getConnection();
    $stmt   = $conn->prepare($sql);

    $params = [
      'final_weight'             => $filters['finalWeight'],
      'max_height'               => $filters['maxHeight'],
      'max_width'                => $filters['maxWidth'],
      'max_depth'                => $filters['maxDepth'],
      'max_cubage'               => $filters['maxCubage'],
      'city_origin_name'         => $filters['cityOriginName'],
      'state_origin_name'        => $filters['stateOriginName'],
      'country_origin_name'      => $filters['countryOriginName'],
      'city_destination_name'    => $filters['cityDestinationName'],
      'state_destination_name'   => $filters['stateDestinationName'],
      'country_destination_name' => $filters['countryDestinationName'],
      'company_id'               => $filters['companyId'],
      'product_material'        => explode(',', $filters['productType'])
    ];


    if ($filters['denyCarriers']) {
      $params['denyCarriers'] = $filters['denyCarriers'];
    }

    $stmt->execute($params);
    $result = $stmt->fetchAll();

    if (empty($result) === false) {
      foreach ($result as $row) {
        $this->groups[$row['groupId']] = $row['groupId'];
      }
    }

    return empty($result) ? null : $result;
  }

  private function getGroupTaxesRegionFixedOrder(array $filters, bool $isAdmin = false): ?array
  {

    $sql = "
      SELECT

        dgo.id              AS group_id,
        dgo.group_name      AS group_name,
        dgo.cubage      AS cubage,
        dgo.code      AS code,
        dgo.marketplace     AS marketplace,
        pep.id              AS carrier_id,
        pep.name            AS carrier_name,
        pep.alias           AS carrier_alias,
        pep.icms            AS carrier_icms,
        pep.enable          AS carrier_enable, 
CONCAT(
          '{',
          GROUP_CONCAT(
              '\"',config.config_key,'\"',
              ':',
              '\"',config.config_value,'\"',
              ','
          ),
          '}'
      ) AS carrier_configs,
AVG(rating.rating) AS average_rating,
        
        ima.url AS carrier_file,
        dr1.deadline        AS carrier_retrieve,
        dr2.deadline        AS carrier_deadline,        
        dta.region_destination_id AS region_destination_id,
        dta.region_origin_id AS region_origin_id,      
        dta.optional AS optional,
        dta.tax_order AS tax_order,        
        dta.id              AS tax_id,";
    if (isset($filters['found_path'])) {
      $sql .= "UPPER(CONCAT(" . $filters['found_path'] . ",' - ',dr1.region,' / ',dr2.region)) AS tax_name,";
    } else {
      $sql .= "UPPER(dta.tax_name) AS tax_name,";
    }

    $sql .= "
    dta.tax_description AS tax_description,
    dta.tax_type        AS tax_type,
        dta.tax_subtype     AS tax_subtype,
        dta.final_weight    AS tax_final_weight,
        dta.price           AS tax_price,
        dta.deadline        AS tax_deadline,
        dta.minimum_price   AS tax_minimum_price
        

      FROM delivery_tax dta

      INNER JOIN delivery_tax_group   dgo ON dgo.id = dta.delivery_tax_group_id

      INNER JOIN delivery_region      dr1 ON dr1.id = dta.region_origin_id
      INNER JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
      INNER JOIN city                 cio ON cio.id = dro.city_id
      INNER JOIN state                sto ON sto.id = cio.state_id
      INNER JOIN country              coo ON coo.id = sto.country_id

      INNER JOIN delivery_region      dr2 ON dr2.id = dta.region_destination_id
      INNER JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
      INNER JOIN city                 cid ON cid.id = drd.city_id
      INNER JOIN state                stn ON stn.id = cid.state_id
      INNER JOIN country              cod ON cod.id = stn.country_id

      INNER JOIN people_carrier       pca ON pca.carrier_id = dgo.carrier_id
      INNER JOIN people               pep ON pep.id = pca.carrier_id
      LEFT JOIN config ON config.people_id = pep.id AND config.visibility = 'public'
LEFT JOIN rating ON rating.people_rated = pep.id
      ";

    $sql .= "
      LEFT  JOIN delivery_restriction_material rm ON rm.people_id = pep.id AND rm.restriction_type = 'delivery_denied'
      LEFT  JOIN product_material              pm ON pm.id        = rm.product_material_id AND (pm.material IN (:product_material) OR pm.id IN (:product_material))
      ";


    $sql .= "LEFT  JOIN files                ima ON ima.id = pep.image_id

      LEFT JOIN carrier_integration cin ON cin.carrier_id = pep.id AND cin.enable = 1

      WHERE
      	dta.optional        = 0
        AND dgo.remote      = 0
        AND pca.company_id  = :company_id
        AND pep.people_type = 'J'
        AND cin.id IS NULL
        AND cubage IN (:cubage)
        ";


    if (!$isAdmin) {
      $sql .= "
        AND pep.enable = 1
        ";
      $sql .= $filters['isMainCompany'] ? " AND dgo.marketplace = 1" : "";
    }
    if ($filters['groupCode']) {
      $sql .= "
      AND dgo.code = :groupCode
      ";
    }
    if ($filters['groupTable']) {
      $sql .= "
      AND dgo.group_name = :groupTable
      ";
    }
    $sql .= "
      	AND (dta.tax_subtype != 'order' OR dta.tax_subtype IS NULL)
        AND (dta.final_weight IS NULL OR (dta.final_weight >= :final_weight AND dta.tax_type= 'fixed' AND dta.tax_subtype IS NULL) OR (dta.final_weight < :final_weight AND dta.tax_type= 'fixed' AND dta.tax_subtype = 'kg'))
      ";

    if ($filters['denyCarriers'])
      $sql .= "
  			AND pep.id NOT IN (:denyCarriers)
  			";

    if ($filters['hasPackages'])
      $sql .= "
        AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :max_cubage)
        AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :final_weight)
        ";

    $sql .= "
        AND (dgo.min_cubage <= :max_cubage AND dgo.max_cubage >= :max_cubage)
        AND (dgo.min_cubage <= :final_weight AND dgo.max_cubage >= :final_weight)
        ";

    $sql .= $filters['isMainCompany'] ? "" : " AND dgo.website = 1";


    if (isset($filters['found_groups'])) {
      $sql .= "
        AND dgo.id IN (:found_groups)
        ";
    }

    if (isset($filters['found_path'])) {
      if ($filters['found_path'] == '1') {
        $sql .= "
            AND cio.city IN (:city_origin_name) AND sto.UF IN (:state_origin_name) AND coo.countryName IN (:country_origin_name)
            AND cid.id IN (:found_cities)
          ";
      } else {
        $sql .= "
            AND cio.id IN (:found_cities)
            AND cid.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name) AND cod.countryName IN (:country_destination_name)                             
        ";
      }
    } else {
      $sql .= "
        AND (cio.city IN (:city_origin_name) AND sto.UF IN (:state_origin_name) AND coo.countryName IN (:country_origin_name))
        AND (cid.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name) AND cod.countryName IN (:country_destination_name))
      ";
    }
    $sql .= "GROUP BY tax_id
        HAVING COUNT(pm.material) = 0
      ORDER BY dgo.id, pep.id, tax_name, dta.final_weight ASC
      ";

    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('group_id', 'groupId');
    $rsm->addScalarResult('group_name', 'groupName');
    $rsm->addScalarResult('code', 'code');

    $rsm->addScalarResult('carrier_id', 'carrierId');
    $rsm->addScalarResult('carrier_name', 'carrierName');
    $rsm->addScalarResult('marketplace', 'marketplace');

    $rsm->addScalarResult('carrier_alias', 'carrierAlias');
    $rsm->addScalarResult('carrier_configs', 'carrierConfigs');
    $rsm->addScalarResult('average_rating', 'averageRating');




    $rsm->addScalarResult('carrier_icms', 'carrierIcms');
    $rsm->addScalarResult('carrier_enable', 'carrierEnable', 'boolean');
    $rsm->addScalarResult('carrier_file', 'carrierFile');
    $rsm->addScalarResult('carrier_retrieve', 'carrierRetrieve');
    $rsm->addScalarResult('carrier_deadline', 'carrierDeadline');
    $rsm->addScalarResult('region_destination_id', 'region_destination_id');
    $rsm->addScalarResult('region_origin_id', 'region_origin_id');
    $rsm->addScalarResult('optional', 'optional');
    $rsm->addScalarResult('tax_order', 'tax_order');




    $rsm->addScalarResult('tax_id', 'taxId');
    $rsm->addScalarResult('tax_name', 'taxName');
    $rsm->addScalarResult('tax_description', 'taxDescription');
    $rsm->addScalarResult('tax_type', 'taxType');
    $rsm->addScalarResult('tax_subtype', 'taxSubType');
    $rsm->addScalarResult('tax_final_weight', 'taxFinalWeight', 'float');
    $rsm->addScalarResult('cubage', 'cubage', 'float');
    $rsm->addScalarResult('tax_price', 'taxPrice', 'float');
    $rsm->addScalarResult('tax_deadline', 'taxDeadline', 'float');

    $rsm->addScalarResult('tax_minimum_price', 'taxMinimumPrice', 'float');



    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('final_weight', $filters['finalWeight']);
    $nqu->setParameter('max_height', $filters['maxHeight']);
    $nqu->setParameter('max_width', $filters['maxWidth']);
    $nqu->setParameter('max_depth', $filters['maxDepth']);
    $nqu->setParameter('max_cubage', $filters['maxCubage']);
    $nqu->setParameter('cubage', $filters['cubage']);


    if (isset($filters['found_groups'])) {
      $nqu->setParameter('found_groups', $filters['found_groups']);
      $nqu->setParameter('found_cities', $filters['found_cities']);
    }
    $nqu->setParameter('city_origin_name', $filters['cityOriginName']);
    $nqu->setParameter('state_origin_name', $filters['stateOriginName']);
    $nqu->setParameter('country_origin_name', $filters['countryOriginName']);
    $nqu->setParameter('city_destination_name', $filters['cityDestinationName']);
    $nqu->setParameter('state_destination_name', $filters['stateDestinationName']);
    $nqu->setParameter('country_destination_name', $filters['countryDestinationName']);
    if ($filters['groupCode']) {
      $nqu->setParameter('groupCode', $filters['groupCode']);
    }
    if ($filters['groupTable']) {
      $nqu->setParameter('groupTable', $filters['groupTable']);
    }

    $nqu->setParameter('company_id', $filters['companyId']);
    $nqu->setParameter('product_material', explode(',', $filters['productType']));

    if ($filters['denyCarriers']) {
      $nqu->setParameter('denyCarriers', $filters['denyCarriers']);
    }

    $res = $nqu->getArrayResult();

    if (empty($res) === false) {
      foreach ($res as $row) {
        $this->groups[$row['groupId']] = $row['groupId'];
      }
    }

    return empty($res) ? null : $res;
  }

  private function getGroupTaxesRegionPercentageInvoice(array $filters, bool $isAdmin = false): ?array
  {
    $sql = "
      SELECT

        dgo.id              AS group_id,
        dgo.group_name      AS group_name,
        dgo.code      AS code,
        dgo.cubage      AS cubage,
        dgo.marketplace     AS marketplace,
        pep.id              AS carrier_id,
        pep.name            AS carrier_name,
        pep.alias           AS carrier_alias,
        pep.icms            AS carrier_icms,
        pep.enable          AS carrier_enable, 
CONCAT(
          '{',
          GROUP_CONCAT(
              '\"',config.config_key,'\"',
              ':',
              '\"',config.config_value,'\"',
              ','
          ),
          '}'
      ) AS carrier_configs,
AVG(rating.rating) AS average_rating,

        ima.url AS carrier_file,
        dr1.deadline        AS carrier_retrieve,
        dr2.deadline        AS carrier_deadline,
        dta.region_destination_id AS region_destination_id,
        dta.region_origin_id AS region_origin_id,      
        dta.optional AS optional,
        dta.tax_order AS tax_order,        


        dta.id              AS tax_id,
        UPPER(dta.tax_name) AS tax_name,
        dta.tax_description AS tax_description,
        dta.tax_type        AS tax_type,
        dta.tax_subtype     AS tax_subtype,
        dta.final_weight    AS tax_final_weight,
        dta.price           AS tax_price,
        dta.deadline        AS tax_deadline,
        dta.minimum_price   AS tax_minimum_price
        


      FROM delivery_tax dta

      INNER JOIN delivery_tax_group   dgo ON dgo.id = dta.delivery_tax_group_id

      INNER JOIN delivery_region      dr1 ON dr1.id = dta.region_origin_id
      INNER JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
      INNER JOIN city                 cio ON cio.id = dro.city_id
      INNER JOIN state                sto ON sto.id = cio.state_id
      INNER JOIN country              coo ON coo.id = sto.country_id

      INNER JOIN delivery_region      dr2 ON dr2.id = dta.region_destination_id
      INNER JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
      INNER JOIN city                 cid ON cid.id = drd.city_id
      INNER JOIN state                stn ON stn.id = cid.state_id
      INNER JOIN country              cod ON cod.id = stn.country_id

      INNER JOIN people_carrier       pca ON pca.carrier_id = dgo.carrier_id
      INNER JOIN people               pep ON pep.id = pca.carrier_id
      LEFT JOIN config ON config.people_id = pep.id AND config.visibility = 'public'
LEFT JOIN rating ON rating.people_rated = pep.id

      LEFT  JOIN files                ima ON ima.id = pep.image_id

      LEFT JOIN carrier_integration cin ON cin.carrier_id = pep.id AND cin.enable = 1
      LEFT  JOIN delivery_restriction_material rm ON rm.people_id = pep.id AND rm.restriction_type = 'delivery_denied'
      LEFT  JOIN product_material              pm ON pm.id        = rm.product_material_id AND (pm.material IN (:product_material) OR pm.id IN (:product_material))

      WHERE
      	dta.optional        = 0
        AND dgo.remote      = 0
        AND dta.tax_subtype = 'invoice'
        AND pca.company_id  = :company_id
        AND pep.people_type = 'J'
        AND cin.id IS NULL
        AND cubage IN (:cubage)
      ";

    if (!$isAdmin) {
      $sql .= "
        AND pep.enable = 1
        ";
      $sql .= $filters['isMainCompany'] ? " AND dgo.marketplace = 1" : "";
    }
    if ($filters['groupCode']) {
      $sql .= "
      AND dgo.code = :groupCode
      ";
    }
    if ($filters['groupTable']) {
      $sql .= "
      AND dgo.group_name = :groupTable
      ";
    }
    $sql .= "
        AND (dta.final_weight > :final_weight AND dta.tax_type= 'percentage' AND dta.tax_subtype != 'kg')
      ";

    if ($filters['denyCarriers'])
      $sql .= "
  			AND pep.id NOT IN (:denyCarriers)
  			";


    if ($filters['hasPackages'])
      $sql .= "
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :max_cubage)
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= "
          AND (dgo.min_cubage <= :max_cubage AND dgo.max_cubage >= :max_cubage)
          AND (dgo.min_cubage <= :final_weight AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= $filters['isMainCompany'] ? "" : " AND dgo.website = 1";

    $sql .= "

        AND (cio.city IN (:city_origin_name) AND sto.UF IN (:state_origin_name) AND coo.countryName IN (:country_origin_name))

        AND (cid.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name) AND cod.countryName IN (:country_destination_name))
        GROUP BY tax_id
        HAVING COUNT(pm.material) = 0
      ORDER BY dgo.id, pep.id, tax_name, dta.final_weight ASC
      ";

    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('group_id', 'groupId');
    $rsm->addScalarResult('group_name', 'groupName');
    $rsm->addScalarResult('code', 'code');
    $rsm->addScalarResult('marketplace', 'marketplace');
    $rsm->addScalarResult('carrier_id', 'carrierId');
    $rsm->addScalarResult('carrier_name', 'carrierName');
    $rsm->addScalarResult('carrier_alias', 'carrierAlias');
    $rsm->addScalarResult('carrier_configs', 'carrierConfigs');
    $rsm->addScalarResult('average_rating', 'averageRating');
    $rsm->addScalarResult('carrier_icms', 'carrierIcms');
    $rsm->addScalarResult('carrier_enable', 'carrierEnable', 'boolean');
    $rsm->addScalarResult('carrier_file', 'carrierFile');
    $rsm->addScalarResult('carrier_retrieve', 'carrierRetrieve');
    $rsm->addScalarResult('carrier_deadline', 'carrierDeadline');
    $rsm->addScalarResult('region_destination_id', 'region_destination_id');
    $rsm->addScalarResult('region_origin_id', 'region_origin_id');
    $rsm->addScalarResult('optional', 'optional');
    $rsm->addScalarResult('tax_order', 'tax_order');
    $rsm->addScalarResult('cubage', 'cubage', 'float');



    $rsm->addScalarResult('tax_id', 'taxId');
    $rsm->addScalarResult('tax_name', 'taxName');
    $rsm->addScalarResult('tax_description', 'taxDescription');
    $rsm->addScalarResult('tax_type', 'taxType');
    $rsm->addScalarResult('tax_subtype', 'taxSubType');
    $rsm->addScalarResult('tax_final_weight', 'taxFinalWeight', 'float');
    $rsm->addScalarResult('tax_price', 'taxPrice', 'float');
    $rsm->addScalarResult('tax_deadline', 'taxDeadline', 'float');

    $rsm->addScalarResult('tax_minimum_price', 'taxMinimumPrice', 'float');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('cubage', $filters['cubage']);
    $nqu->setParameter('final_weight', $filters['finalWeight']);
    $nqu->setParameter('max_height', $filters['maxHeight']);
    $nqu->setParameter('max_width', $filters['maxWidth']);
    $nqu->setParameter('max_depth', $filters['maxDepth']);
    $nqu->setParameter('max_cubage', $filters['maxCubage']);
    $nqu->setParameter('city_origin_name', $filters['cityOriginName']);
    $nqu->setParameter('state_origin_name', $filters['stateOriginName']);
    $nqu->setParameter('country_origin_name', $filters['countryOriginName']);
    $nqu->setParameter('city_destination_name', $filters['cityDestinationName']);
    $nqu->setParameter('state_destination_name', $filters['stateDestinationName']);
    $nqu->setParameter('country_destination_name', $filters['countryDestinationName']);
    $nqu->setParameter('company_id', $filters['companyId']);
    $nqu->setParameter('product_material', explode(',', $filters['productType']));
    if ($filters['groupCode']) {
      $nqu->setParameter('groupCode', $filters['groupCode']);
    }
    if ($filters['groupTable']) {
      $nqu->setParameter('groupTable', $filters['groupTable']);
    }


    if ($filters['denyCarriers']) {
      $nqu->setParameter('denyCarriers', $filters['denyCarriers']);
    }

    $res = $nqu->getArrayResult();

    return empty($res) ? null : $res;
  }

  private function getGroupTaxesRegionPercentageOrder(array $filters, bool $isAdmin = false): ?array
  {
    $sql = "
      SELECT

        dgo.id              AS group_id,
        dgo.group_name      AS group_name,
        dgo.code      AS code,
        dgo.cubage      AS cubage,        
        dgo.marketplace      AS marketplace,
        pep.id              AS carrier_id,
        pep.name            AS carrier_name,
        pep.alias           AS carrier_alias,
        pep.icms            AS carrier_icms,
        pep.enable          AS carrier_enable, 
CONCAT(
          '{',
          GROUP_CONCAT(
              '\"',config.config_key,'\"',
              ':',
              '\"',config.config_value,'\"',
              ','
          ),
          '}'
      ) AS carrier_configs,
AVG(rating.rating) AS average_rating,

        ima.url AS carrier_file,
        dr1.deadline        AS carrier_retrieve,
        dr2.deadline        AS carrier_deadline,
        dta.region_destination_id AS region_destination_id,
        dta.region_origin_id AS region_origin_id,      
        dta.optional AS optional,
        dta.tax_order AS tax_order,        


        dta.id              AS tax_id,
        UPPER(dta.tax_name) AS tax_name,
        dta.tax_description AS tax_description,
        dta.tax_type        AS tax_type,
        dta.tax_subtype     AS tax_subtype,
        dta.final_weight    AS tax_final_weight,
        dta.price           AS tax_price,
        dta.deadline        AS tax_deadline,
        dta.minimum_price   AS tax_minimum_price
        

      FROM delivery_tax dta

      INNER JOIN delivery_tax_group   dgo ON dgo.id = dta.delivery_tax_group_id

      INNER JOIN delivery_region      dr1 ON dr1.id = dta.region_origin_id
      INNER JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
      INNER JOIN city                 cio ON cio.id = dro.city_id
      INNER JOIN state                sto ON sto.id = cio.state_id
      INNER JOIN country              coo ON coo.id = sto.country_id

      INNER JOIN delivery_region      dr2 ON dr2.id = dta.region_destination_id
      INNER JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
      INNER JOIN city                 cid ON cid.id = drd.city_id
      INNER JOIN state                stn ON stn.id = cid.state_id
      INNER JOIN country              cod ON cod.id = stn.country_id

      INNER JOIN people_carrier       pca ON pca.carrier_id = dgo.carrier_id
      INNER JOIN people               pep ON pep.id = pca.carrier_id
      LEFT JOIN config ON config.people_id = pep.id AND config.visibility = 'public'
LEFT JOIN rating ON rating.people_rated = pep.id

      LEFT  JOIN files                ima ON ima.id = pep.image_id

      LEFT JOIN carrier_integration cin ON cin.carrier_id = pep.id AND cin.enable = 1

      LEFT  JOIN delivery_restriction_material rm ON rm.people_id = pep.id AND rm.restriction_type = 'delivery_denied'
      LEFT  JOIN product_material              pm ON pm.id        = rm.product_material_id AND (pm.material IN (:product_material) OR pm.id IN (:product_material))

      WHERE
      	dta.optional        = 0
        AND dgo.remote      = 0
        AND pca.company_id  = :company_id
        AND dta.tax_subtype = 'order'
        AND pep.people_type = 'J'
        AND cin.id IS NULL
        AND cubage IN (:cubage)
      ";
    if (!$isAdmin) {
      $sql .= "
        AND pep.enable = 1
        ";
      $sql .= $filters['isMainCompany'] ? " AND dgo.marketplace = 1" : "";
    }
    if ($filters['groupCode']) {
      $sql .= "
      AND dgo.code = :groupCode
      ";
    }
    if ($filters['groupTable']) {
      $sql .= "
      AND dgo.group_name = :groupTable
      ";
    }
    $sql .= "
        AND (
          (dta.final_weight >= :final_weight AND dta.tax_type = 'percentage' AND dta.tax_subtype = 'order')
        	OR (dta.final_weight IS NULL)
        	OR (dta.final_weight >= :final_weight AND dta.tax_type = 'percentage' AND dta.tax_subtype IS NULL)
        	OR (dta.final_weight <  :final_weight AND dta.tax_type = 'percentage' AND dta.tax_subtype = 'kg')
        )
      ";

    if ($filters['denyCarriers'])
      $sql .= "
  			AND pep.id NOT IN (:denyCarriers)
  			";

    if ($filters['hasPackages'])
      $sql .= "
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :max_cubage)
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= "
          AND (dgo.min_cubage <= :max_cubage AND dgo.max_cubage >= :max_cubage)
          AND (dgo.min_cubage <= :final_weight AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= $filters['isMainCompany'] ? "" : " AND dgo.website = 1";

    $sql .= "
        AND (cio.city IN (:city_origin_name) AND sto.UF IN (:state_origin_name) AND coo.countryName IN (:country_origin_name))

        AND (cid.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name) AND cod.countryName IN (:country_destination_name))
        GROUP BY tax_id
        HAVING COUNT(pm.material) = 0

      ORDER BY dgo.id, pep.id, tax_name, dta.final_weight ASC
      ";

    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('group_id', 'groupId');
    $rsm->addScalarResult('group_name', 'groupName');
    $rsm->addScalarResult('code', 'code');
    $rsm->addScalarResult('marketplace', 'marketplace');

    $rsm->addScalarResult('carrier_id', 'carrierId');
    $rsm->addScalarResult('carrier_name', 'carrierName');
    $rsm->addScalarResult('carrier_alias', 'carrierAlias');
    $rsm->addScalarResult('carrier_configs', 'carrierConfigs');
    $rsm->addScalarResult('average_rating', 'averageRating');
    $rsm->addScalarResult('carrier_icms', 'carrierIcms');
    $rsm->addScalarResult('carrier_enable', 'carrierEnable', 'boolean');
    $rsm->addScalarResult('carrier_file', 'carrierFile');
    $rsm->addScalarResult('carrier_retrieve', 'carrierRetrieve');
    $rsm->addScalarResult('carrier_deadline', 'carrierDeadline');
    $rsm->addScalarResult('region_destination_id', 'region_destination_id');
    $rsm->addScalarResult('region_origin_id', 'region_origin_id');
    $rsm->addScalarResult('optional', 'optional');
    $rsm->addScalarResult('tax_order', 'tax_order');



    $rsm->addScalarResult('tax_id', 'taxId');
    $rsm->addScalarResult('tax_name', 'taxName');
    $rsm->addScalarResult('tax_description', 'taxDescription');
    $rsm->addScalarResult('tax_type', 'taxType');
    $rsm->addScalarResult('tax_subtype', 'taxSubType');
    $rsm->addScalarResult('tax_final_weight', 'taxFinalWeight', 'float');
    $rsm->addScalarResult('tax_price', 'taxPrice', 'float');
    $rsm->addScalarResult('tax_deadline', 'taxDeadline', 'float');

    $rsm->addScalarResult('tax_minimum_price', 'taxMinimumPrice', 'float');
    $rsm->addScalarResult('cubage', 'cubage', 'float');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('final_weight', $filters['finalWeight']);
    $nqu->setParameter('max_height', $filters['maxHeight']);
    $nqu->setParameter('max_width', $filters['maxWidth']);
    $nqu->setParameter('max_depth', $filters['maxDepth']);
    $nqu->setParameter('max_cubage', $filters['maxCubage']);
    $nqu->setParameter('city_origin_name', $filters['cityOriginName']);
    $nqu->setParameter('state_origin_name', $filters['stateOriginName']);
    $nqu->setParameter('country_origin_name', $filters['countryOriginName']);
    $nqu->setParameter('city_destination_name', $filters['cityDestinationName']);
    $nqu->setParameter('state_destination_name', $filters['stateDestinationName']);
    $nqu->setParameter('country_destination_name', $filters['countryDestinationName']);
    $nqu->setParameter('company_id', $filters['companyId']);
    $nqu->setParameter('product_material', explode(',', $filters['productType']));
    $nqu->setParameter('cubage', $filters['cubage']);

    if ($filters['denyCarriers']) {
      $nqu->setParameter('denyCarriers', $filters['denyCarriers']);
    }
    if ($filters['groupCode']) {
      $nqu->setParameter('groupCode', $filters['groupCode']);
    }
    if ($filters['groupTable']) {
      $nqu->setParameter('groupTable', $filters['groupTable']);
    }
    $res = $nqu->getArrayResult();

    return empty($res) ? null : $res;
  }

  private function getGroupTaxesFixedKm(array $filters, bool $isAdmin = false): ?array
  {
    $sql = "
        SELECT

          dgo.id              AS group_id,
          dgo.group_name      AS group_name,
          dgo.code            AS code,
          dgo.cubage          AS cubage,
          dgo.marketplace     AS marketplace,
          pep.id              AS carrier_id,
          pep.name            AS carrier_name,
          pep.alias           AS carrier_alias,
          pep.icms            AS carrier_icms,
          pep.enable          AS carrier_enable, 
CONCAT(
          '{',
          GROUP_CONCAT(
              '\"',config.config_key,'\"',
              ':',
              '\"',config.config_value,'\"',
              ','
          ),
          '}'
      ) AS carrier_configs,
AVG(rating.rating) AS average_rating,

          ima.url AS carrier_file,
          '0'        AS carrier_retrieve,
          '0'        AS carrier_deadline,          

          dta.id              AS tax_id,
          UPPER(dta.tax_name) AS tax_name,
          dta.tax_description AS tax_description,
          dta.tax_type        AS tax_type,
          dta.tax_subtype     AS tax_subtype,
          dta.final_weight    AS tax_final_weight,
          dta.price           AS tax_price,
          dta.deadline        AS tax_deadline,
          dta.minimum_price   AS tax_minimum_price
          

        FROM delivery_tax dta

        	INNER JOIN delivery_tax_group   dgo ON dgo.id = dta.delivery_tax_group_id

          LEFT JOIN delivery_region      dr1 ON dr1.id = dta.region_origin_id
          LEFT JOIN delivery_region_city dro ON dro.delivery_region_id = dr1.id
          LEFT JOIN city                 cio ON cio.id = dro.city_id
          LEFT JOIN state                sto ON sto.id = cio.state_id
          LEFT JOIN country              coo ON coo.id = sto.country_id
    
          LEFT JOIN delivery_region      dr2 ON dr2.id = dta.region_destination_id
          LEFT JOIN delivery_region_city drd ON drd.delivery_region_id = dr2.id
          LEFT JOIN city                 cid ON cid.id = drd.city_id
          LEFT JOIN state                stn ON stn.id = cid.state_id
          LEFT JOIN country              cod ON cod.id = stn.country_id


          INNER JOIN people_carrier       pca ON pca.carrier_id = dgo.carrier_id

          INNER JOIN people               pep ON pep.id = pca.carrier_id
      LEFT JOIN config ON config.people_id = pep.id AND config.visibility = 'public'
LEFT JOIN rating ON rating.people_rated = pep.id

      	  LEFT  JOIN files                ima ON ima.id = pep.image_id

          LEFT JOIN carrier_integration cin ON cin.carrier_id = pep.id AND cin.enable = 1
          LEFT  JOIN delivery_restriction_material rm ON rm.people_id = pep.id AND rm.restriction_type = 'delivery_denied'
          LEFT  JOIN product_material              pm ON pm.id        = rm.product_material_id AND (pm.material IN (:product_material) OR pm.id IN (:product_material))
        WHERE
        	dta.optional           = 0
          AND dgo.remote         = 0
          AND dta.final_weight  >= :final_weight
          AND dta.tax_type       = 'fixed'
          AND dta.tax_subtype    = 'km'
          AND pca.company_id     = :company_id
          AND pep.people_type    = 'J'
          AND cin.id IS NULL     
          AND cubage IN (:cubage)     
        ";

    if (!$isAdmin) {
      $sql .= "
          AND pep.enable = 1
          ";
      $sql .= $filters['isMainCompany'] ? " AND dgo.marketplace = 1" : "";
    }


    if ($filters['groupCode']) {
      $sql .= "
      AND dgo.code = :groupCode
      ";
    }

    if ($filters['groupTable']) {
      $sql .= "
      AND dgo.group_name = :groupTable
      ";
    }

    if ($filters['denyCarriers'])
      $sql .= "
    			AND pep.id NOT IN (:denyCarriers)
    			";

    if ($filters['hasPackages'])
      $sql .= "
            AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :max_cubage)
            AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :final_weight)
            ";

    $sql .= "
            AND (dgo.min_cubage <= :max_cubage AND dgo.max_cubage >= :max_cubage)
            AND (dgo.min_cubage <= :final_weight AND dgo.max_cubage >= :final_weight)
            ";

    $sql .= $filters['isMainCompany'] ? "" : " AND dgo.website = 1";

    $sql .= " AND ((cio.city IN (:city_origin_name) AND sto.UF IN (:state_origin_name) AND coo.countryName IN (:country_origin_name)) OR dta.region_origin_id IS NULL)";
    $sql .= " AND ((cid.city IN (:city_destination_name) AND stn.UF IN (:state_destination_name) AND cod.countryName IN (:country_destination_name)) OR dta.region_destination_id IS NULL)";

    $sql .= "
        GROUP BY tax_id
        HAVING COUNT(pm.material) = 0

        ORDER BY dgo.id, pep.id, tax_name, dta.final_weight ASC
        ";



    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('group_id', 'groupId');
    $rsm->addScalarResult('group_name', 'groupName');
    $rsm->addScalarResult('code', 'code');
    $rsm->addScalarResult('marketplace', 'marketplace');

    $rsm->addScalarResult('carrier_id', 'carrierId');
    $rsm->addScalarResult('carrier_name', 'carrierName');
    $rsm->addScalarResult('carrier_alias', 'carrierAlias');
    $rsm->addScalarResult('carrier_configs', 'carrierConfigs');
    $rsm->addScalarResult('average_rating', 'averageRating');
    $rsm->addScalarResult('carrier_icms', 'carrierIcms');
    $rsm->addScalarResult('carrier_enable', 'carrierEnable', 'boolean');
    $rsm->addScalarResult('carrier_file', 'carrierFile');
    $rsm->addScalarResult('carrier_retrieve', 'carrierRetrieve');
    $rsm->addScalarResult('carrier_deadline', 'carrierDeadline');
    $rsm->addScalarResult('region_destination_id', 'region_destination_id');
    $rsm->addScalarResult('region_origin_id', 'region_origin_id');
    $rsm->addScalarResult('optional', 'optional');
    $rsm->addScalarResult('tax_order', 'tax_order');



    $rsm->addScalarResult('tax_id', 'taxId');
    $rsm->addScalarResult('tax_name', 'taxName');
    $rsm->addScalarResult('tax_description', 'taxDescription');
    $rsm->addScalarResult('tax_type', 'taxType');
    $rsm->addScalarResult('tax_subtype', 'taxSubType');
    $rsm->addScalarResult('tax_final_weight', 'taxFinalWeight', 'float');
    $rsm->addScalarResult('tax_price', 'taxPrice', 'float');
    $rsm->addScalarResult('tax_deadline', 'taxDeadline', 'float');

  
    $rsm->addScalarResult('tax_minimum_price', 'taxMinimumPrice', 'float');
    $rsm->addScalarResult('cubage', 'cubage', 'float');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('cubage', $filters['cubage']);
    $nqu->setParameter('final_weight', $filters['finalWeight']);
    $nqu->setParameter('max_height', $filters['maxHeight']);
    $nqu->setParameter('max_width', $filters['maxWidth']);
    $nqu->setParameter('max_depth', $filters['maxDepth']);
    $nqu->setParameter('max_cubage', $filters['maxCubage']);
    $nqu->setParameter('product_material', explode(',', $filters['productType']));

    $nqu->setParameter('city_origin_name', $filters['cityOriginName']);
    $nqu->setParameter('state_origin_name', $filters['stateOriginName']);
    $nqu->setParameter('country_origin_name', $filters['countryOriginName']);
    $nqu->setParameter('city_destination_name', $filters['cityDestinationName']);
    $nqu->setParameter('state_destination_name', $filters['stateDestinationName']);
    $nqu->setParameter('country_destination_name', $filters['countryDestinationName']);

    $nqu->setParameter('company_id', $filters['companyId']);
    if ($filters['groupCode']) {
      $nqu->setParameter('groupCode', $filters['groupCode']);
    }
    if ($filters['groupTable']) {
      $nqu->setParameter('groupTable', $filters['groupTable']);
    }
    if ($filters['denyCarriers']) {
      $nqu->setParameter('denyCarriers', $filters['denyCarriers']);
    }

    $res = $nqu->getArrayResult();

    if (empty($res) === false) {
      foreach ($res as $row) {
        $this->groups[$row['groupId']] = $row['groupId'];
      }
    }

    return empty($res) ? null : $res;
  }

  private function getGroupTaxesFixed(array $filters, bool $isAdmin = false): ?array
  {
    $sql = "
      SELECT

        dgo.id              AS group_id,
        dgo.group_name      AS group_name,
        dgo.code      AS code,
        dgo.cubage      AS cubage,
        dgo.marketplace     AS marketplace,
        pep.id              AS carrier_id,
        pep.name            AS carrier_name,
        pep.alias           AS carrier_alias,
        pep.icms            AS carrier_icms,
        pep.enable          AS carrier_enable, 
CONCAT(
          '{',
          GROUP_CONCAT(
              '\"',config.config_key,'\"',
              ':',
              '\"',config.config_value,'\"',
              ','
          ),
          '}'
      ) AS carrier_configs,
AVG(rating.rating) AS average_rating,

        ima.url AS carrier_file,
        null                AS carrier_retrieve,
        null                AS carrier_deadline,
        dta.region_destination_id AS region_destination_id,
        dta.region_origin_id AS region_origin_id,      
        dta.optional AS optional,
        dta.tax_order AS tax_order,        


        dta.id              AS tax_id,
        UPPER(dta.tax_name) AS tax_name,
        dta.tax_description AS tax_description,
        dta.tax_type        AS tax_type,
        dta.tax_subtype     AS tax_subtype,
        dta.final_weight    AS tax_final_weight,
        dta.price           AS tax_price,
        dta.deadline        AS tax_deadline,
        dta.minimum_price   AS tax_minimum_price
        

      FROM delivery_tax dta

        INNER JOIN delivery_tax_group dgo ON dgo.id = dta.delivery_tax_group_id

        INNER JOIN people_carrier     pca ON pca.carrier_id = dgo.carrier_id

        INNER JOIN people             pep ON pep.id = pca.carrier_id
        LEFT JOIN config ON config.people_id = pep.id AND config.visibility = 'public'
LEFT JOIN rating ON rating.people_rated = pep.id

    	  LEFT  JOIN files              ima ON ima.id = pep.image_id

        LEFT JOIN carrier_integration cin ON cin.carrier_id = pep.id AND cin.enable = 1
        LEFT  JOIN delivery_restriction_material rm ON rm.people_id = pep.id AND rm.restriction_type = 'delivery_denied'
        LEFT  JOIN product_material              pm ON pm.id        = rm.product_material_id AND (pm.material IN (:product_material) OR pm.id IN (:product_material))

      WHERE
        dta.optional   = 0
        AND dgo.remote = 0
        AND cubage IN (:cubage)
        AND (dta.tax_subtype != 'km' OR dta.tax_subtype IS NULL)

        AND dta.region_origin_id IS NULL AND dta.region_destination_id IS NULL

        AND pca.company_id = :company_id

        AND pep.people_type = 'J'
        AND dgo.id IN (:groups_id)

        AND cin.id IS NULL
      ";
    if (!$isAdmin) {
      $sql .= "
        AND pep.enable = 1
        ";
      $sql .= $filters['isMainCompany'] ? " AND dgo.marketplace = 1" : "";
    }
    if ($filters['groupCode']) {
      $sql .= "
      AND dgo.code = :groupCode
      ";
    }
    if ($filters['groupTable']) {
      $sql .= "
      AND dgo.group_name = :groupTable
      ";
    }
    if ($filters['denyCarriers'])
      $sql .= "
  			AND pep.id NOT IN (:denyCarriers)
  			";

    if ($filters['hasPackages'])
      $sql .= "
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :max_cubage)
          AND (dgo.max_height >= :max_height AND dgo.max_width >= :max_width AND dgo.max_depth >= :max_depth AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= "
          AND (dgo.min_cubage <= :max_cubage AND dgo.max_cubage >= :max_cubage)
          AND (dgo.min_cubage <= :final_weight AND dgo.max_cubage >= :final_weight)
          ";

    $sql .= $filters['isMainCompany'] ? "" : " AND dgo.website = 1";

    $sql .= "
      GROUP BY tax_id
      HAVING COUNT(pm.material) = 0

      ORDER BY dgo.id, pep.id, tax_name, dta.final_weight ASC
      ";

    $rsm = new ResultSetMapping();

    $rsm->addScalarResult('group_id', 'groupId');
    $rsm->addScalarResult('group_name', 'groupName');
    $rsm->addScalarResult('code', 'code');
    $rsm->addScalarResult('marketplace', 'marketplace');
    $rsm->addScalarResult('carrier_id', 'carrierId');
    $rsm->addScalarResult('carrier_name', 'carrierName');
    $rsm->addScalarResult('carrier_alias', 'carrierAlias');
    $rsm->addScalarResult('carrier_configs', 'carrierConfigs');
    $rsm->addScalarResult('average_rating', 'averageRating');
    $rsm->addScalarResult('carrier_icms', 'carrierIcms');
    $rsm->addScalarResult('carrier_enable', 'carrierEnable', 'boolean');
    $rsm->addScalarResult('carrier_file', 'carrierFile');
    $rsm->addScalarResult('carrier_retrieve', 'carrierRetrieve');
    $rsm->addScalarResult('carrier_deadline', 'carrierDeadline');
    $rsm->addScalarResult('region_destination_id', 'region_destination_id');
    $rsm->addScalarResult('region_origin_id', 'region_origin_id');
    $rsm->addScalarResult('optional', 'optional');
    $rsm->addScalarResult('tax_order', 'tax_order');


    $rsm->addScalarResult('tax_id', 'taxId');
    $rsm->addScalarResult('tax_name', 'taxName');
    $rsm->addScalarResult('tax_description', 'taxDescription');
    $rsm->addScalarResult('tax_type', 'taxType');
    $rsm->addScalarResult('tax_subtype', 'taxSubType');
    $rsm->addScalarResult('tax_final_weight', 'taxFinalWeight', 'float');
    $rsm->addScalarResult('tax_price', 'taxPrice', 'float');
    $rsm->addScalarResult('tax_deadline', 'taxDeadline', 'float');

    $rsm->addScalarResult('tax_minimum_price', 'taxMinimumPrice', 'float');
    $rsm->addScalarResult('cubage', 'cubage', 'float');

    $nqu = $this->manager->createNativeQuery($sql, $rsm);

    $nqu->setParameter('final_weight', $filters['finalWeight']);
    $nqu->setParameter('cubage', $filters['cubage']);
    $nqu->setParameter('max_height', $filters['maxHeight']);
    $nqu->setParameter('max_width', $filters['maxWidth']);
    $nqu->setParameter('max_depth', $filters['maxDepth']);
    $nqu->setParameter('max_cubage', $filters['maxCubage']);
    $nqu->setParameter('company_id', $filters['companyId']);
    $nqu->setParameter('groups_id', $this->groups);
    $nqu->setParameter('product_material', explode(',', $filters['productType']));
    if ($filters['groupCode']) {
      $nqu->setParameter('groupCode', $filters['groupCode']);
    }
    if ($filters['groupTable']) {
      $nqu->setParameter('groupTable', $filters['groupTable']);
    }
    if ($filters['denyCarriers']) {
      $nqu->setParameter('denyCarriers', $filters['denyCarriers']);
    }

    $res = $nqu->getArrayResult();

    return empty($res) ? null : $res;
  }

  private function processGroupTaxesResult(array $rows, array &$result, $is_multiple = false): array
  {
    if ($is_multiple) {
      $rows = $this->processRoute($rows);
    }
    foreach ($rows as $row) {
      if (!array_key_exists($row['groupId'], $result)) {
        $file = !empty($row['carrierFile']) ? $_SERVER['HTTP_HOST'] . $row['carrierFile'] : null;

        $result[$row['groupId']] = [
          'id'               => $row['groupId'],
          'name'             => $row['groupName'],
          'code'             => $row['code'],
          'marketplace'      => $row['marketplace'],
          'carrier'          => [
            'id'      => $row['carrierId'],
            'name'    => $row['carrierName'],
            'alias'   => $row['carrierAlias'],
            'icms'    => (bool) $row['carrierIcms'],
            'enabled' => (bool) $row['carrierEnable'],
            'image'   => $file,
            'configs' => json_decode(substr($row['carrierConfigs'], 0, -2) . '}'),
            'rating' => $row['averageRating'],
          ],
          'retrieveDeadline' => array_key_exists('carrierRetrieve', $row) ? $row['carrierRetrieve'] : null,
          'deliveryDeadline' => array_key_exists('carrierDeadline', $row) ? $row['carrierDeadline'] : null,
          'taxes'            => [],

        ];
      }

      if (!array_key_exists($row['taxName'], $result[$row['groupId']]['taxes'])) {
        $result[$row['groupId']]['taxes'][$row['taxName']] = [
          'id'                  => $row['taxId'],
          'name'                => $row['taxName'],
          'description'         => $row['taxDescription'],
          'cubage'              => (float) $row['cubage'],
          'type'                => $row['taxType'],
          'subType'             => $row['taxSubType'],
          'finalWeight'         => (float) $row['taxFinalWeight'],
          'price'               => (float) $row['taxPrice'],
          'deadline'            => (float) $row['taxDeadline'],
          'minimumPrice'        => (float) $row['taxMinimumPrice'],
          'region_destination_id' => array_key_exists('region_destination_id', $row) ? $row['region_destination_id'] : null,
          'region_origin_id' => array_key_exists('region_origin_id', $row) ? $row['region_origin_id'] : null,
          'tax_order' => array_key_exists('tax_order', $row) ? $row['tax_order'] : 0,
          'optional' => array_key_exists('optional', $row) ? $row['optional'] : 0,
        ];
      }
    }

    return $result;
  }

  private function processRoute($rows)
  {
    foreach ($rows as $row) {
      if (substr($row['taxName'], 0, 1) ==  '1') {
        self::$first[] = $row;
      } elseif (substr($row['taxName'], 0, 1) ==  '2') {
        self::$second[] = $row;
      } else {
        $result[] = $row;
      }
    }

    if (self::$first && !isset(self::$first['taxPrice'])) {
      foreach (self::$first as $t) {
        if (!isset($p['taxPrice']) || $t['taxPrice'] < $p['taxPrice']) {
          $p = $t;
        }
      }
      self::$first = $p;
    }


    if (self::$first && self::$second  && isset(self::$first['taxPrice'])) {
      foreach (self::$second as $s) {
        if ($s['region_origin_id'] == self::$first['region_destination_id'] && (!isset($pp['taxPrice']) || $s['taxPrice'] < $pp['taxPrice'])) {
          $pp = $s;
        }
      }
      self::$second = $pp;
    }


    if (self::$first && self::$second && isset(self::$first['taxPrice']) && isset(self::$second['taxPrice'])) {
      $result[] = self::$first;
      $result[] = self::$second;
    }

    return $result;
  }
}
