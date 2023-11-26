<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\PeopleProfessional;
use App\Entity\People;
use App\Entity\Document;
use App\Service\PeopleService;

class CreateProfessionalAction
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
        $professional = json_decode($this->request->getContent(), true);

        $this->validateData($professional);

        $this->manager->getConnection()->beginTransaction();

        $people = $this->createProfessional($professional);

        $this->manager->flush();
        $this->manager->getConnection()->commit();

        return new JsonResponse([
          'response' => [
            'data'    => [
              'professionalId' => $people->getId(),
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
     *   "name"    : "Fulano",
     *   "alias"   : "Detal",
     *   "document": "2528554578"
     * }
     * @return People
     */
    public function createProfessional(array $data): People
    {
      $docType = $this->people->getDocumentTypeByDoc($data['document']);

      $document = $this->manager->getRepository(Document::class)
        ->findOneBy(['document' => $data['document']]);
      
      if ($document instanceof Document) {
        if ($document->getPeople() instanceof People) {
          throw new \InvalidArgumentException('O documento já foi cadastrado');
        }
        else {
          $params = [
            'name'  => $data['name'],
            'alias' => $data['alias'],
            'type'  => $docType === "CPF" ? 'F' : 'J'
          ];

          $people = $this->people->create($params, false);

          $this->manager->persist($document->setPeople($people));
          $this->manager->persist($people);

          return $people;
        }
      }

      
      // create professional

      $params = [
        'name'      => $data['name'],
        'alias'     => $data['alias'],
        'type'      => 'F',
        'documents' => [
            ['document' => $data['document'], 'type' => $docType === "CPF" ? 2 : 3],
        ],
      ];
      $people = $this->people->create($params, false);

      // set professional default values

      $people->setBilling    (10000);
      $people->setBillingDays('biweekly');
      $people->setPaymentTerm(5);

      $this->manager->persist($people);

      // get my provider

      $provider = $this->getProvider();

      $peopleProfessional = new PeopleProfessional();
      $peopleProfessional->setCompany($provider);
      $peopleProfessional->setProfessional($people);
      $peopleProfessional->setEnable (true);

      $this->manager->persist($peopleProfessional);

      return $people;
    }

    private function validateData(array $data): void
    {
      if (!isset($data['document'])) {
        throw new \InvalidArgumentException('document is not defined');
      }
      else {
        $docType = $this->people->getDocumentTypeByDoc($data['document']);
        if ($docType !== 'CPF' && $docType !== 'CNPJ') {
          throw new \InvalidArgumentException('document is not valid');
        }
      }

      if (!isset($data['name']) || empty($data['name'])) {
        throw new \InvalidArgumentException('name is not valid');
      }

      if (!isset($data['alias']) || empty($data['alias'])) {
        throw new \InvalidArgumentException('alias is not valid');
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
