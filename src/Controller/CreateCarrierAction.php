<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\PeopleCarrier;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Carrier;
use ControleOnline\Entity\Contract;
use ControleOnline\Entity\ContractPeople;
use ControleOnline\Entity\Document;
use App\Service\PeopleService;

class CreateCarrierAction
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
   * People Service
   *
   * @var \App\Service\PeopleService
   */
  private $people   = null;

  public function __construct(EntityManagerInterface $manager, PeopleService $people)
  {
    $this->manager = $manager;
    $this->people  = $people;
  }

  public function __invoke(Request $request): JsonResponse
  {
    $this->request = $request;

    try {
      $carrier = json_decode($this->request->getContent(), true);

      $this->validateData($carrier);

      $this->manager->getConnection()->beginTransaction();

      $people = $this->createCarrier($carrier);

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return new JsonResponse([
        'response' => [
          'data'    => [
            'carrierId' => $people->getId(),
          ],
          'count'   => 1,
          'error'   => '',
          'success' => true,
        ],
      ], 201);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive()) {
        $this->manager->getConnection()->rollBack();
      }

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

  /**
   * @param array $data
   * {
   *   "name"    : "Razao Social",
   *   "alias"   : "Nome Fantasia",
   *   "document": "85588888888"
   * }
   * @return People
   */
  public function createCarrier(array $data): People
  {
    $document = $this->manager->getRepository(Document::class)->findOneBy(['document' => $data['document']]);
    if ($document instanceof Document) {
      if ($document->getPeople() instanceof People) {
        $this->createPeopleCarrier($document->getPeople());
        return $document->getPeople();
      } else {
        $params = [
          'name'  => $data['name'],
          'alias' => $data['alias'],
          'type'  => 'J'
        ];
        $people = $this->people->create($params, false);
        $peopleCarrier = $this->createPeopleCarrier($people);
        $this->manager->persist($document->setPeople($people));
        $this->manager->persist($people);

        return $people;
      }
    }

    // create carrier

    $params = [
      'name'      => $data['name'],
      'alias'     => $data['alias'],
      'type'      => 'J',
      'documents' => [
        ['document' => $data['document'], 'type' => 3],
      ],
    ];
    $people = $this->people->create($params, false);

    $this->manager->persist($people);

    // get my provider

    $peopleCarrier = $this->createPeopleCarrier($people);
    return $people;
  }

  private function createPeopleCarrier($people)
  {

    $provider = $this->getProvider();
/*
    $contracts = $this->manager->getRepository(Contract::class)->createQueryBuilder('C')
      ->select()
      ->innerJoin('\ControleOnline\Entity\ContractPeople', 'CP', 'WITH', 'CP.contract = C.id')
      ->where('CP.people = :people')
      ->andWhere('CP.people_type = :people_type')
      ->andWhere('C.contractStatus = :contractStatus')
      ->setParameters([
        'people' => $people,
        'people_type' => 'Beneficiary',
        'contractStatus' => 'active'
      ])
      ->groupBy('C.id')
      ->getQuery()
      ->getResult();


    foreach ($contracts as $contract) {
      if ($contract instanceof Contract) {
        //$contract->getProd
        $totalCarriers = 2;
      }
    }
*/
$totalCarriers = 2000;
    $peopleCarriers = count($this->manager->getRepository(PeopleCarrier::class)->findBy(['company' => $provider]));

    if ($peopleCarriers >= $totalCarriers)
      throw new \Exception('Carrier limit reached');


    $peopleCarrier = new PeopleCarrier();
    $peopleCarrier->setCompany($provider);
    $peopleCarrier->setCarrier($people);
    $peopleCarrier->setEnabled(true);

    $this->manager->persist($peopleCarrier);
    return $peopleCarrier;
  }

  private function validateData(array $data): void
  {
    if (!isset($data['document'])) {
      throw new \InvalidArgumentException('Carrier document is not defined');
    } else {
      $docType = $this->people->getDocumentTypeByDoc($data['document']);
      if ($docType !== 'CNPJ') {
        throw new \InvalidArgumentException('Carrier document is not valid');
      }
    }

    if (!isset($data['name']) || empty($data['name'])) {
      throw new \InvalidArgumentException('Carrier name is not valid');
    }

    if (!isset($data['alias']) || empty($data['alias'])) {
      throw new \InvalidArgumentException('Carrier alias is not valid');
    }
  }

  private function getProvider(): People
  {
    $providerId = $this->request->query->get('myProvider', null);
    if ($providerId === null) {
      throw new \InvalidArgumentException('Provider Id is not defined');
    }

    $provider = $this->manager->getRepository(People::class)->find($providerId);
    if ($provider === null) {
      throw new \InvalidArgumentException('Provider not found');
    }

    return $provider;
  }
}
