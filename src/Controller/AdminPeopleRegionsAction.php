<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\DeliveryRegion;
use ControleOnline\Entity\DeliveryRegionCity;
use ControleOnline\Entity\City;
use ControleOnline\Entity\State;

class AdminPeopleRegionsAction
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
    private $request  = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    public function __construct(EntityManagerInterface $manager, Security $security)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(People $data, int $regionId = null, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_PUT    => empty($regionId) ? 'createRegion' : 'updateRegion',
                Request::METHOD_DELETE => 'deleteRegion',
                Request::METHOD_GET    => 'getRegions'  ,
            ];

            $payload   = json_decode($this->request->getContent(), true);
            $operation = $methods[$request->getMethod()];
            $result    = $this->$operation($data, $payload);

            return new JsonResponse([
                'response' => [
                    'data'    => $result,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);

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

    private function createRegion(People $people, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($people->getId());
            $region  = $this->manager->getRepository(DeliveryRegion::class)->findOneBy(['region' => $payload['regionName'], 'people' => $company]);

            if ($region instanceof DeliveryRegion) {
              throw new \InvalidArgumentException('Esta praça já foi criada');
            }

            $region = new DeliveryRegion();
            $region->setRegion     ($payload['regionName']);
            $region->setPeople     ($company);
            $region->setDeadline   ($payload['deadline']);
            $region->setRetrieveTax($payload['taxValue']);

            $this->manager->persist($region);

            if (isset($payload['cities']) && is_array($payload['cities'])) {
              if (empty($payload['cities'])) {
                throw new \InvalidArgumentException('As cidades não foram informadas');
              }

              foreach ($payload['cities'] as $cityId) {
                $city = $this->manager->getRepository(City::class)->find($cityId);
                if ($city === null) {
                  throw new \InvalidArgumentException(
                    sprintf('City with id "%s" not found', $cityId)
                  );
                }

                $regionCity = new DeliveryRegionCity();
                $regionCity->setCity  ($city);
                $regionCity->setRegion($region);

                $this->manager->persist($regionCity);
              }
            }

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $region->getId()
            ];

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function updateRegion(People $people, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($people->getId());
            $region  = $this->manager->getRepository(DeliveryRegion::class)->findOneBy(['id' => $this->request->get('regionId'), 'people' => $company]);
            if ($region === null) {
              throw new \InvalidArgumentException('People Region not found');
            }

            if (isset($payload['regionName']) && !empty($payload['regionName'])) {
              if ($payload['regionName'] !== $region->getRegion()) {
                $region->setRegion($payload['regionName']);
              }
            }

            if (isset($payload['deadline']) && !empty($payload['deadline'])) {
              if ($payload['deadline'] !== $region->getDeadline()) {
                $region->setDeadline($payload['deadline']);
              }
            }

            if (isset($payload['taxValue']) && !empty($payload['taxValue'])) {
              if ($payload['taxValue'] !== $region->getRetrieveTax()) {
                $region->setRetrieveTax($payload['taxValue']);
              }
            }

            $this->manager->persist($region);

            // update cities

            if (isset($payload['cities']) && is_array($payload['cities'])) {
              if (isset($payload['cities']['removed']) && is_array($payload['cities']['removed'])) {
                $removeIds = $payload['cities']['removed'];

                // remove cities

                foreach ($removeIds as $cityId) {
                  $city = $this->manager->getRepository(City::class)->find($cityId);
                  if ($city === null) {
                    continue;
                  }

                  $regionCity = $this->manager->getRepository(DeliveryRegionCity::class)
                    ->findOneBy([
                      'region' => $region,
                      'city'   => $city
                    ]);
                  if ($regionCity instanceof DeliveryRegionCity) {
                    $this->manager->remove($regionCity);
                  }
                }
              }
              else {
                if (isset($payload['cities']['added']) && is_array($payload['cities']['added'])) {
                  if (!isset($payload['cities']['state'])) {
                    throw new \InvalidArgumentException('State id is not defined');
                  }

                  $state = $this->manager->getRepository(State::class)->findOneBy(['uf' => $payload['cities']['state']]);
                  if ($state === null) {
                    throw new \InvalidArgumentException('State not found');
                  }

                  foreach ($payload['cities']['added'] as $cityName) {
                    if (empty($cityName) || !is_string($cityName)) {
                      continue;
                    }

                    $city = $this->manager->getRepository(City::class)
                      ->findOneBy([
                        'city'  => $cityName,
                        'state' => $state
                      ]);

                    if ($city === null) {
                      $city = new City();
                      $city->setCity ($cityName);
                      $city->setState($state);

                      $this->manager->persist($city);
                    }

                    if (!empty($city->getId())) {
                      $regionCity = $this->manager->getRepository(DeliveryRegionCity::class)
                        ->findOneBy([
                          'region' => $region,
                          'city'   => $city
                        ]);

                      if ($regionCity instanceof DeliveryRegionCity) {
                        continue;
                      }
                    }

                    $regionCity = new DeliveryRegionCity();
                    $regionCity->setCity  ($city);
                    $regionCity->setRegion($region);

                    $this->manager->persist($regionCity);
                  }
                }
              }
            }

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
              'id' => $region->getId()
            ];

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function deleteRegion(People $people, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Region id is not defined');
            }

            $company = $this->manager->getRepository(People::class)->find($people->getId());
            $regions = $this->manager->getRepository(DeliveryRegion::class)->findBy(['people' => $company]);
            if (count($regions) == 1) {
                throw new \InvalidArgumentException('Deve existir pelo menos uma praça');
            }

            $region  = $this->manager->getRepository(DeliveryRegion::class)->findOneBy(['id' => $payload['id'], 'people' => $company]);
            if (!$region instanceof DeliveryRegion) {
                throw new \InvalidArgumentException('People region was not found');
            }

            $this->manager->remove($region);

            foreach ($region->getRegionCity() as $regionCity) {
              $this->manager->remove($regionCity);
            }

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getRegions(People $people, ?array $payload = null): array
    {
      $page     = $this->request->query->get('page'  , 1);
      $limit    = $this->request->query->get('limit' , 10);
      $paginate = [
        'from'  => is_numeric($limit) ? ($limit * ($page - 1)) : 0,
        'limit' => !is_numeric($limit) ? 10 : $limit
      ];

      $members = [];
      $people  = $this->manager->getRepository(People::class )->find($people->getId());
      $regions = $this->manager->getRepository(DeliveryRegion::class)
        ->getAllPeopleRegions($people, null, $paginate);

      foreach ($regions as $region) {
        $cities       = [];
        $regionCities = $this->manager->getRepository(DeliveryRegionCity::class)
          ->getAllRegionCitiesByRegionId($region['id']);

        foreach ($regionCities as $city) {
          $cities[] = [
            'id'    => $city['id'],
            'city'  => $city['city'],
            'state' => $city['uf'],
          ];
        }

        $members[] = [
          'id'       => $region['id'],
          'name'     => $region['region'],
          'tax'      => $region['tax'],
          'deadline' => $region['deadline'],
          'cities'   => $cities
        ];
      }

      return [
        'members' => $members,
        'total'   => $this->manager->getRepository(DeliveryRegion::class)
          ->getAllPeopleRegions($people, null, null, true),
      ];
    }
}
