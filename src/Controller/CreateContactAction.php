<?php

namespace App\Controller;

use ControleOnline\Entity\Document;
use ControleOnline\Entity\DocumentType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use App\Service\AddressService;
use App\Service\PeopleService;

class CreateContactAction
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
    private $request = null;

    /**
     * People Service
     *
     * @var \App\Service\PeopleService
     */
    private $people  = null;

    /**
     * Address Service
     *
     * @var \App\Service\AddressService
     */
    private $address = null;

    public function __construct(EntityManagerInterface $manager, PeopleService $people, AddressService $address)
    {
        $this->manager = $manager;
        $this->people  = $people;
        $this->address = $address;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;

        try {


            $contact = json_decode($this->request->getContent(), true);

            $this->validateData($contact);

            $this->manager->getConnection()->beginTransaction();

            if (isset($contact['payer'])) {
                $result = $this->getPeopleByPayer($contact);
            } else {
                $result = $this->createContact($contact);
            }

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        'companyId' => $result['company'] instanceof People ? $result['company']->getId() : null,
                        'contactId' => $result['contact'] instanceof People ? $result['contact']->getId() : null,
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


    public function getPeopleByPayer(array $data)
    {
        $result['contact'] = $this->manager->getRepository(People::class)->find($data['payer']);
        $result['company'] = null;
        return $result;
    }

    /**
     * @param array $data
     * [
     *   'name'     => '',
     *   'alias'    => '',
     *   'document' => '',
     *   'contact'  => [
     *     'name'  => '',
     *     'email' => '',
     *     'phone' => '',
     *   ],
     *   'address'  => [
     *     'country'    => '',
     *     'state'      => '',
     *     'city'       => '',
     *     'district'   => '',
     *     'complement' => '',
     *     'street'     => '',
     *     'number'     => '',
     *     'postalCode' => '',
     *   ],
     * ]
     * @return array
     */
    public function createContact(array $data): array
    {
        $company = null;
        $contact = null;

        

        if ($data['peopleType'] == 'PF' || !$data['name'] || !$data['alias']) {            

            $company = $this->people->create([
                'name'      => $data['contact']['name'],
                'alias'     => '',
                'type'      => 'F',
                'document' => $data['document'],
                'email'     => $data['contact']['email'],
                'phone'     => [
                    'ddd'   => substr($data['contact']['phone'], 0, 2),
                    'phone' => substr($data['contact']['phone'], 2)
                ],
            ], false);

            $address = $this->address->createFor($company, $data['address']);
            if ($address === null)
                throw new \Exception('O endereço não é válido');

            $this->manager->persist($company);
            $this->manager->persist($address);
            
        } else {            
            // create company            
            $company = $this->people->create([
                'name'      => $data['name'],
                'alias'     => $data['alias'],                
                'type'      => 'J',
                'document'  => $data['document']
            ], false);
            
            $address = $this->address->createFor($company, $data['address']);
            if ($address === null)
                throw new \Exception('O endereço não é válido');

            // create contact
            
            $contact = $this->people->create([
                'name'      => $data['contact']['name'],
                'alias'     => '',
                'type'      => 'F',
                'email'     => $data['contact']['email'],
                'phone'     => [
                    'ddd'   => substr($data['contact']['phone'], 0, 2),
                    'phone' => substr($data['contact']['phone'], 2)
                ],
            ], false);

            // create contract

            $contract = new PeopleLink();
            $contract->setPeople($contact);
            $contract->setCompany($company);
            $contract->setEnabled(true);

            $this->manager->persist($company);
            $this->manager->persist($address);
            $this->manager->persist($contact);
            $this->manager->persist($contract);
        }
        
        return [
            'company' => $company,
            'contact' => $contact === null ? $company : $contact,
        ];
    }

    private function validateData(array $data): void
    {
        //if (!isset($data['document']))
        //    throw new \Exception('Document param is not defined');

        //$docType = $this->people->getDocumentTypeByDoc($data['document']);
        //if ($docType === null)
        //    throw new \Exception('O documento não é válido');

        //if ($docType == 'CNPJ') {
        //    if (!isset($data['name'])  || empty($data['name' ]))
        //        throw new \Exception('Name param is not defined');

        //    if (!isset($data['alias']) || empty($data['alias']))
        //        throw new \Exception('Alias param is not defined');
        //}

        if ((!isset($data['contact']) || !is_array($data['contact'])) && !isset($data['payer']))
            throw new \Exception('Contact param is not defined');
        else if (!isset($data['payer'])) {
            if (!isset($data['contact']['name']) || empty($data['contact']['name']))
                throw new \Exception('Contact name param is not defined');

            if (!isset($data['contact']['email']) || empty($data['contact']['email']))
                throw new \Exception('Contact email param is not defined');
            else if (!filter_var($data['contact']['email'], FILTER_VALIDATE_EMAIL))
                throw new \Exception('Contact email param is not valid');

            if (!isset($data['contact']['phone']) || empty($data['contact']['phone']))
                throw new \Exception('Contact phone param is not defined');
            else if (preg_match('/^[0-9]{6,11}$/', $data['contact']['phone']) !== 1)
                throw new \Exception('Contact phone param is not valid');
        }
    }
}
