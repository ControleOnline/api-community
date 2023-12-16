<?php

namespace App\Controller;

use ControleOnline\Entity\Client;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Email;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Phone;
use App\Service\PeopleService;

class UpdateClientAction
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
     * Client entity
     *
     * @var \ControleOnline\Entity\Client
     */
    private $client   = null;

    public function __construct(EntityManagerInterface $manager, PeopleService $people)
    {
        $this->manager = $manager;
        $this->people  = $people;
    }

    public function __invoke(Client $data, Request $request): JsonResponse
    {
        $this->request = $request;
        $this->client  = $data;

        try {
            $client = json_decode($this->request->getContent(), true);

            $this->validateData($client);

            $this->updateClient($client);

            return new JsonResponse([
                'response' => [
                    'data'    => $this->client->getId(),
                    'count'   => 1,
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

    /**
     * @param array $data
     * @return array
     */
    public function updateClient(array $data)
    {
        /**
         * @var \ControleOnline\Repository\ClientRepository
         */
        $myRepo = $this->manager->getRepository(Client::class);

        // update client

        if (isset($data['document'])) {
            if (($cnpj = $this->getClientCNPJ()) !== null) {
                if ($cnpj->getDocument() != $data['document']) {
    
                    if ($this->people->documentExist($data['document'], 3))
                        throw new \Exception('O CNPJ informado já está em uso');

                    $myRepo->updateData($this->client->getId(), ['document' => $data['document']]);
                }
            }
        }

        if (isset($data['name'])) {
            $myRepo->updateData($this->client->getId(), ['name' => $data['name']]);
        }

        if (isset($data['alias'])) {
            $myRepo->updateData($this->client->getId(), ['alias' => $data['alias']]);
        }

        if (isset($data['contact'])) {
            /**
             * @var \ControleOnline\Entity\People
             */
            $contact = $this->manager->getRepository(People::class)->find($data['contact']['id']);
            if ($contact === null)
                throw new \Exception('Contact not found');

            // verify if contact id belongs to client

            if (!$this->contactBelongsToClient($contact))
                throw new \Exception('Contact does not belong to you');

            // update contact name

            if (isset($data['contact']['name'])) {
                $myRepo->updateData($contact->getId(), ['name' => $data['contact']['name']]);
            }

            // update contact alias

            if (isset($data['contact']['alias'])) {
                $myRepo->updateData($contact->getId(), ['alias' => $data['contact']['alias']]);
            }

            // update contact email

            if (isset($data['contact']['email'])) {
    
                /**
                 * @var \ControleOnline\Entity\Email
                 */
                $_email = $this->manager->getRepository(Email::class)->findOneBy(['email' => $data['contact']['email']]);
    
                if (!$contact->getEmail()->isEmpty()) {
                    /**
                     * @var \ControleOnline\Entity\Email
                     */
                    $email = $contact->getEmail()->first();
    
                    if ($email->getEmail() != $data['contact']['email'] && $_email !== null)
                        throw new \Exception('O email informado já está em uso');

                    $myRepo->updateData($contact->getId(), ['email' => $data['contact']['email']]);
                }
                else {
                    if ($_email !== null)
                        throw new \Exception('O email informado já está em uso');

                    $email = new Email();
                    $email->setEmail ($data['contact']['email']);
                    $email->setPeople($contact);
                    $email->setConfirmed(0);
    
                    $this->manager->persist($email);
                    $this->manager->flush();
                }
            }

            // update contact phone
    
            if (isset($data['contact']['phone'])) {
                /**
                 * @var \ControleOnline\Entity\Phone
                 */
                $_phone = $this->manager->getRepository(Phone::class)
                    ->findOneBy([
                        'ddd'   => substr($data['contact']['phone'], 0, 2),
                        'phone' => substr($data['contact']['phone'], 2)
                    ]);
    
                if (!$contact->getPhone()->isEmpty()) {
                    /**
                     * @var \ControleOnline\Entity\Phone
                     */
                    $phone  = $contact->getPhone()->first();
                    $number = $phone->getDdd() . $phone->getPhone();
    
                    if ($number != $data['contact']['phone'] && $_phone !== null)
                        throw new \Exception('O telefone informado já está em uso');
    
                    $myRepo->updateData(
                        $contact->getId(),
                            [
                                'phone' => [
                                    'ddd'   => substr($data['contact']['phone'], 0, 2),
                                    'phone' => substr($data['contact']['phone'], 2)
                                ]
                            ]
                    );
                }
                else {
                    if ($_phone !== null)
                        throw new \Exception('O telefone informado já está em uso');
    
                    $phone = new Phone();
                    $phone->setDdd  (substr($data['contact']['phone'], 0, 2));
                    $phone->setPhone(substr($data['contact']['phone'], 2));
                    $phone->setPeople($contact);
                    $phone->setConfirmed(0);
    
                    $this->manager->persist($phone);
                    $this->manager->flush();
                }
            }
        }
    }

    private function contactBelongsToClient(People $contact): bool
    {
        $isMyContact = $this->client->getPeopleEmployee()->exists(
            function ($key, $element) use ($contact) {
                return $element->getEmployee() === $contact;
            }
        );

        return $isMyContact;
    }

    private function validateData(array $data): void
    {
        if (isset($data['document']) && !empty($data['document'])) {
            $docType = $this->people->getDocumentTypeByDoc($data['document']);
            if ($docType === null)
                throw new \Exception('O documento não é válido');

            if ($docType != 'CNPJ') {
                throw new \Exception('Document is not valid');
            }
        }

        if (isset($data['name' ]) && empty($data['name'])) {
            throw new \Exception('Name param is not valid');
        }

        if (isset($data['alias']) && empty($data['alias'])) {
            throw new \Exception('Alias param is not valid');
        }

        if (isset($data['contact'])) {
            if (!is_array($data['contact']) || empty($data['contact']))
                throw new \Exception('Contact param is not valid');

            if (!isset($data['contact']['id']) || empty($data['contact']['id']))
                throw new \Exception('Contact id param is not defined');

            if (isset($data['contact']['name']) && empty($data['contact']['name']))
                throw new \Exception('Name contact param is not valid');

            if (isset($data['contact']['alias']) && empty($data['contact']['alias']))
                throw new \Exception('Alias contact param is not valid');

            if (isset($data['contact']['email']) && !empty($data['contact']['email']))
                if (!filter_var($data['contact']['email'], FILTER_VALIDATE_EMAIL))
                    throw new \Exception('Email contact param is not valid');
    
            if (isset($data['contact']['phone']) && !empty($data['contact']['phone']))
                if (preg_match('/^[0-9]{6,11}$/', $data['contact']['phone']) !== 1)
                    throw new \Exception('Phone contact param is not valid');
        }
    }

    private function getClientCNPJ(): ?Document
    {
        foreach ($this->client->getDocument() as $document) {
            if ($document->getDocumentType()->getDocumentType() == 'CNPJ') {
                return $document;
            }
        }

        return null;
    }
}
