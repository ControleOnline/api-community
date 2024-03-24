<?php

namespace App\Controller;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Particulars;
use ControleOnline\Entity\ParticularsType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleClient;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\PeopleSalesman;
use App\Service\PeopleService;
use App\Service\AddressService;
use App\Service\PeopleRoleService;

class CreateClientAction
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

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * PeopleClient Repository
     *
     * @var \ControleOnline\Repository\PeopleClientRepository
     */
    private $clients  = null;

    /**
     * People Entity
     *
     * @var \ControleOnline\Entity\People
     */
    private $client   = null;

    /**
     * People Entity
     *
     * @var \ControleOnline\Entity\People
     */
    private $contact  = null;

    /**
     * Address Service
     *
     * @var \App\Service\AddressService
     */
    private $address = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    public function __construct(EntityManagerInterface $manager, PeopleService $people, Security $security, AddressService $address, PeopleRoleService $roles)
    {
        $this->manager     = $manager;
        $this->people      = $people;
        $this->address     = $address;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
        $this->clients     = $this->manager->getRepository(PeopleClient::class);
        $this->peopleRoles = $roles;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;

        try {
            $client = json_decode($this->request->getContent(), true);

            $this->validateData($client);

            $this->manager->getConnection()->beginTransaction();

            $this->createClient($client);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        'clientId'  => $this->client  === null ? null : $this->client->getId(),
                        'contactId' => $this->contact === null ? null : $this->contact->getId(),
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

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
     *   "name"    : "Cliente Meu",
     *   "alias"   : "SouSeu Cliente",
     *   "document": "23303007000120",
     *   "contact" : {
     *     "name" : "Contato novo 1",
     *     "alias": "Sobrenome novo 1",
     *     "email": "souseu32@gmail.com",
     *     "phone": "14889652118"
     *  },
     *  "address"  : {
     *     "country"   : "",
     *     "state"     : "",
     *     "city"      : "",
     *     "district"  : "",
     *     "complement": "",
     *     "street"    : "",
     *     "number"    : "",
     *     "postalCode": "",
     *   }
     * }
     * @return array
     */
    public function createClient(array $data)
    {
        // verify if client belongs to other salesman

        /*
        if ($this->clients->clientBelongsToOtherSalesman($data['document'], $this->getMySalesCompany()))
            throw new \Exception('Este cliente já pertence a outro vendedor');
        */

        $client = null;

        if (!isset($data['document'])) {
            $client = $this->createMyClient($data);
        } else {
            if (($client = $this->getClientByDocument($data['document'])) === null) {
                $client = $this->createMyClient($data);
            }
        }

        if ($client !== null) {
            if ($this->peopleRoles->isSalesman($this->currentUser->getPeople())) {
                $this->makePeopleMyClient($client);
            }
        }

        $this->client = $client;
    }

    private function getMySalesCompany(): People
    {
        return ($this->currentUser->getPeople()->getPeopleCompany()->first())
            ->getCompany();
    }

    private function createMyClient(array $data): ?People
    {
        if (!isset($data['document'])) {
            return $this->createPFClient($data);
        } else {
            if ($this->people->getDocumentTypeByDoc($data['document']) == 'CNPJ') {
                return $this->createPJClient($data);
            } else {
                if ($this->people->getDocumentTypeByDoc($data['document']) == 'CPF') {
                    return $this->createPFClient($data);
                }
            }
        }

        return null;
    }

    private function createPFClient(array $data): People
    {
        $people = [
            'name'      => $data['name'],
            'alias'     => $data['alias'],
            'type'      => 'F',
            'documents' => null,
            'email'     => null,
            'phone'     => null,
        ];

        if (!isset($data['document'])) {
            $people['email'] = $data['contact']['email'];
        } else {
            $people['email']     = $data['contact']['email'];
            $people['documents'] = [
                ['document' => $data['document'], 'type' => 2],
            ];
        }

        if (isset($data['contact']['phone'])) {
            $people['phone'] = [
                'ddd'   => substr($data['contact']['phone'], 0, 2),
                'phone' => substr($data['contact']['phone'], 2)
            ];
        }

        // create customer

        $client = $this->people->create($people, false);

        if (isset($data['paymentDay'])) {
            $client->setBillingDays('monthly');
            $client->setPaymentTerm($data['paymentDay']);
        }

        if (isset($data['particulars'])) {
            foreach ($data['particulars'] as $particular) {
                if (isset($particular['id']) && isset($particular['value'])) {
                    $type = $this->manager->getRepository(ParticularsType::class)->find($particular['id']);
                    if ($type !== null) {
                        $particulars = new Particulars();
                        $particulars->setType($type);
                        $particulars->setPeople($client);
                        $particulars->setValue($particular['value']);

                        $this->manager->persist($particulars);
                    }
                }
            }
        }

        if (isset($data['docrg'])) {
            $docnumber = preg_replace('/[^0-9]/', '', $data['docrg']);
            if (!is_numeric($docnumber))
                throw new \Exception('O R.G. não é válido');

            $docnumber    = (int) $docnumber;
            $documentType = $this->manager->getRepository(DocumentType::class)
                ->findOneBy([
                    'documentType' => 'R.G',
                    'peopleType'   => 'F'
                ]);

            if ($documentType !== null) {
                $document = new Document();
                $document->setDocument($docnumber);
                $document->setDocumentType($documentType);
                $document->setPeople($client);

                $this->manager->persist($document);
            }
        }

        if (isset($data['birthday'])) {
            $client->setFoundationDate(
                \DateTime::createFromFormat('Y-m-d', $data['birthday'])
            );
        }

        // create address

        if (isset($data['address']) && !empty($data['address'])) {
            $address = $this->address->createFor($client, $data['address']);
            if ($address === null)
                throw new \Exception('O endereço não é válido');

            $this->manager->persist($address);
        }

        // create client contact relationship

        $this->manager->persist($client);

        return $client;
    }

    private function createPJClient(array $data): People
    {
        // create client

        $client = $this->people->create([
            'name'      => $data['name'],
            'alias'     => $data['alias'],
            'type'      => 'J',
            'documents' => [
                ['document' => $data['document'], 'type' => 3],
            ],
        ], false);

        if (isset($data['paymentDay'])) {
            $client->setBillingDays('monthly');
            $client->setPaymentTerm($data['paymentDay']);
        }

        if (isset($data['particulars'])) {
            foreach ($data['particulars'] as $particular) {
                if (isset($particular['id']) && isset($particular['value'])) {
                    $type = $this->manager->getRepository(ParticularsType::class)->find($particular['id']);
                    if ($type !== null) {
                        $particulars = new Particulars();
                        $particulars->setType($type);
                        $particulars->setPeople($client);
                        $particulars->setValue($particular['value']);

                        $this->manager->persist($particulars);
                    }
                }
            }
        }

        if (isset($data['birthday'])) {
            $client->setFoundationDate(
                \DateTime::createFromFormat('Y-m-d', $data['birthday'])
            );
        }

        // create client address

        if (isset($data['address']) && !empty($data['address'])) {
            $address = $this->address->createFor($client, $data['address']);
            if ($address === null)
                throw new \Exception('O endereço não é válido');

            $this->manager->persist($address);
        }

        // create contact

        $people = [
            'name'  => $data['contact']['name'],
            'alias' => $data['contact']['alias'],
            'type'  => 'F',
            'email' => $data['contact']['email'],
            'phone' => null,
        ];

        if (isset($data['contact']['phone'])) {
            $people['phone'] = [
                'ddd'   => substr($data['contact']['phone'], 0, 2),
                'phone' => substr($data['contact']['phone'], 2)
            ];
        }

        $contact = $this->people->create($people, false);

        $this->contact = $contact;

        // create client contact relationship

        $peopleLink = new PeopleLink();
        $peopleLink->setCompany($client);
        $peopleLink->setPeople($contact);
        $peopleLink->setEnabled(true);

        $this->manager->persist($client);
        $this->manager->persist($contact);
        $this->manager->persist($peopleLink);

        return $client;
    }

    private function makePeopleMyClient(People $client): void
    {
        // get salesman company

        $company  = $this->getMySalesmanCompany();

        // get my provider

        $provider = $this->getMyProvider();

        if ($company->getId() == $provider->getId()) {
            if (!$this->clientRelationshipExists($company, $client)) {
                $salesmanClient = new PeopleClient();
                $salesmanClient->setCompanyId($company->getId());
                $salesmanClient->setClient($client);
                $salesmanClient->setEnabled(false);

                $this->manager->persist($salesmanClient);

                return;
            }
        }

        // create salesman client relationship

        if (!$this->clientRelationshipExists($company, $client)) {
            $salesmanClient = new PeopleClient();
            $salesmanClient->setCompanyId($company->getId());
            $salesmanClient->setClient($client);
            $salesmanClient->setEnabled(false);

            $this->manager->persist($salesmanClient);
        }

        // create provider client relationship

        if (!$this->clientRelationshipExists($provider, $client)) {
            $providerClient = new PeopleClient();
            $providerClient->setCompanyId($provider->getId());
            $providerClient->setClient($client);
            $providerClient->setEnabled(true);

            $this->manager->persist($providerClient);
        }
    }

    private function clientRelationshipExists(People $company, People $client): bool
    {
        return ($this->manager->getRepository(PeopleClient::class)
            ->findOneBy(['company_id' => $company->getId(), 'client' => $client])) === null ? false : true;
    }

    private function getClientByDocument(string $document): ?People
    {
        $document = $this->manager->getRepository(Document::class)
            ->findOneBy([
                'document' => $document,
            ]);

        return $document === null ? null : $document->getPeople();
    }

    private function getMySalesmanCompany(): People
    {
        /**
         * @var \ControleOnline\Repository\PeopleRepository
         */
        $repository = $this->manager->getRepository(People::class);

        $companies  = $repository->createQueryBuilder('P')
            ->select()
            ->innerJoin('\ControleOnline\Entity\PeopleLink', 'PE', 'WITH', 'PE.company = P.id')
            ->innerJoin('\ControleOnline\Entity\PeopleSalesman', 'PS', 'WITH', 'PS.salesman = PE.company')
            ->where('PE.employee = :employee')
            ->setParameters([
                'employee' => $this->currentUser->getPeople()
            ])
            ->groupBy('P.id')
            ->getQuery()
            ->getResult();

        if (empty($companies))
            throw new \Exception('Sua empresa não está cadastrada no sistema');

        return $companies[0];
    }

    private function getMyProvider(): People
    {
        $providerId = $this->request->query->get('myProvider', null);
        if ($providerId === null)
            throw new \Exception('Provider Id is not defined');

        $peopleRepo = $this->manager->getRepository(People::class);
        $provider   = $peopleRepo->find($providerId);

        if ($provider === null)
            throw new \Exception('Provider not found');

        /**
         * @var \ControleOnline\Repository\PeopleSalesmanRepository
         */
        $salesman   = $this->manager->getRepository(PeopleSalesman::class);
        /**
         * @var \ControleOnline\Entity\User
         */
        $myUser     = $this->security->getUser();

        if (!$salesman->companyIsMyProvider($myUser->getPeople(), $provider))
            throw new \Exception('Your Company does not work with this Provider');

        return $provider;
    }

    private function validateData(array $data): void
    {
        if (!isset($data['document'])) {
            if (!isset($data['contact']) || !is_array($data['contact'])) {
                throw new \Exception('O email não foi informado');
            } else {
                if (!isset($data['contact']['email']) || empty($data['contact']['email'])) {
                    throw new \Exception('O campo email não pode estar vazio');
                } else if (!filter_var($data['contact']['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('O email informado não é válido');
                }
            }
        } else {
            $docType = $this->people->getDocumentTypeByDoc($data['document']);
            if ($docType === null) {
                throw new \Exception('O documento não é válido');
            }

            if (in_array($docType, ['CNPJ', 'CPF'])) {
                if (!isset($data['name'])  || empty($data['name'])) {
                    throw new \Exception('Name param is not defined');
                }

                if (!isset($data['alias']) || empty($data['alias'])) {
                    throw new \Exception('Alias param is not defined');
                }
            } else {
                throw new \Exception('Document is not valid');
            }
        }

        if (isset($data['paymentDay'])) {
            if (!is_int($data['paymentDay']))
                throw new \Exception('Payment day is not defined');

            if (($data['paymentDay'] >= 1 && $data['paymentDay'] <= 31) == false)
                throw new \Exception('Payment day is not valid');
        }

        if (isset($data['birthday'])) {
            if (!is_string($data['birthday']))
                throw new \Exception('Birthday is not defined');

            if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $data['birthday']) !== 1)
                throw new \Exception('Birthday is not valid');
        }

        if (isset($data['docrg']) && empty($data['docrg']))
            throw new \Exception('Document RG is not valid');

        if (!isset($data['contact']) || !is_array($data['contact']))
            throw new \Exception('Contact param is not defined');
        else {
            if (!isset($data['contact']['name']) || empty($data['contact']['name']))
                throw new \Exception('Contact name param is not defined');

            if (!isset($data['contact']['email']) || empty($data['contact']['email']))
                throw new \Exception('Contact email param is not defined');
            else if (!filter_var($data['contact']['email'], FILTER_VALIDATE_EMAIL))
                throw new \Exception('Contact email param is not valid');

            if (isset($data['contact']['phone'])) {
                if (empty($data['contact']['phone'])) {
                    throw new \Exception('Contact phone param is not defined');
                } else if (preg_match('/^[0-9]{6,11}$/', $data['contact']['phone']) !== 1) {
                    throw new \Exception('Contact phone param is not valid');
                }
            }
        }

        if (isset($data['particulars'])) {
            if (!is_array($data['particulars'])) {
                throw new \Exception('Particulars is not defined');
            }
        }
    }
}
