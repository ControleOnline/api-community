<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\DeliveryTaxGroup;
use Doctrine\ORM\Query\ResultSetMapping;

class GetTablesAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager = $entityManager;
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $sql = '
        SELECT DTG.group_name, GROUP_CONCAT(DISTINCT DTG.code) AS codes  
        FROM delivery_tax_group DTG
        INNER JOIN people_carrier C ON (C.carrier_id = DTG.carrier_id)
        INNER JOIN people_domain PD ON (PD.people_id = C.company_id)
        WHERE PD.domain = :domain
        AND (DTG.marketplace = 1 OR DTG.website = 1)
        GROUP BY DTG.group_name        
        ORDER BY DTG.group_name            
        ';

      $rsm = new ResultSetMapping();

      $rsm->addScalarResult('group_name', 'groupName');
      $rsm->addScalarResult('codes', 'groupCodes');

      $nqu = $this->manager->createNativeQuery($sql, $rsm);

      $nqu->setParameter('domain', $request->get('domain', null));

      $taxNames = $nqu->getArrayResult();

      $result = [];
      $merged = [];
      $merged['groupNames'] = [];
      $merged['groupCodes'] = [];
      foreach ($taxNames as $key => $taxes) {
        $merged['groupNames'][$taxes['groupName']] = $taxes['groupName'];
        foreach (explode(',', $taxes['groupCodes']) as $code) {
          $merged['groupCodes'][$code] = $code;
        }
        $result[$key]['groupName'] = $taxes['groupName'];
        $result[$key]['groupCodes'] = explode(',', $taxes['groupCodes']);
      }
      $merged['groupNames'] = array_values($merged['groupNames']);
      $merged['groupCodes'] = array_values($merged['groupCodes']);

      $output = [
        'response' => [
          'data'    => [
            'members' => $result,
            'merged'  => $merged,
            'total'   => count($result)
          ],
          'success' => true,
        ],
      ];

      return new JsonResponse($output, 200);
    } catch (\Exception $e) {

      $output = [
        'response' => [
          'data'    => [],
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ];

      return new JsonResponse($output, $e->getCode() >= 400 ? $e->getCode() : 500);
    }
  }
}
