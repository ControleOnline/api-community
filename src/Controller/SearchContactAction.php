<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\Address;

class SearchContactAction
{
    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $em   = null;

    /**
     * Request
     *
     * @var Request
     */
    private $rq   = null;

    /**
     * User entity
     *
     * @var \ControleOnline\Entity\User
     */
    private $user = null;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->em   = $entityManager;
        $this->user = $security->getUser();
    }

    public function __invoke(People $data, Request $request): JsonResponse
    {
        $this->rq = $request;

        try {

            $contact = [];
            $people  = null;

            // if i want the contact of one of my companies

            if (($company = $this->rq->query->get('myCompany', null)) !== null) {
                /**
                 * @var \ControleOnline\Entity\User $currentUser
                 */
                $currentUser  = $this->user;
                $clientPeople = $this->em->getRepository(People::class)->find($company);

                $isMyCompany = $currentUser->getPeople()->getPeopleCompany()->exists(
                    function ($key, $element) use ($clientPeople) {
                        return $element->getCompany() === $clientPeople;
                    }
                );
                if ($isMyCompany === true)
                    $people = $clientPeople;
            }
            else {
                // fetch people by document or current user
    
                $id       = $this->rq->get('id', null);
                $document = $this->rq->query->get('document', null);
                $email    = $this->rq->query->get('email', null);
    
                if (is_string($document) && empty($document) === false) {
                    $people = $this->getPeopleByDocument($document);
                }
                else if (is_string($email) && empty($email) === false) {
                    $people = $this->getPeopleByEmail($email);
                }
                else if (!empty($id)) {
                    $id = (int) $id;
                    
                    $people = $this->em->getRepository(People::class)
                        ->findOneBy(['id' => $id]);
                }
                else {
    
                    // here people = currentUser->getPeople()
    
                    $people = $data;
    
                    if (($peopleEmployee = $people->getPeopleCompany()->first()) !== false) {
                        if ($peopleEmployee->getCompany() instanceof People)
                            $people = $peopleEmployee->getCompany();
                    }
    
                }
            }

            if ($people instanceof People)
                $contact = $this->getContactByPeople($people);

            if (empty($contact))
                throw new \Exception('Contact not found');

            return new JsonResponse([
                'response' => [
                    'data'    => $contact,
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

    private function getContactByPeople(People $people): array
    {
        $contact    = $this->getContact($people);
        $peopleType = $people->getPeopleType();

        $document   = $people->getDocument()->first();

        $email      = $people->getOneEmail();
        $emailText  = !empty($email) ? $email->getEmail() : null;

        /**
         * @var Phone $phone
         */
        $phone     = null;
        $phoneText = null;
        $phones    = $people->getPhone();

        if (!empty($phones) && count($phones) > 0) {
            $phone     = $phones[0];
            $phoneText = $phone->getDdd() . $phone->getPhone();
        }
        

        return [
            'id'         => $people->getId(),
            'peopleType' => $people->getPeopleType(),
            'name'       => $people->getName(),
            'alias'      => $people->getAlias(),
            'document'   => !empty($document) ? [
                'id'     => $document->getDocument(),
                'type'   => $document->getDocumentType()->getDocumentType(),
            ] : null,
            'address'   => $this->getPeopleAddress($people),
            'email'     => $emailText,
            'phone'     => $phoneText,
            'contact'   => $people->getPeopleType() == 'F' ? [
                !empty($contact) ? $contact : ( $peopleType == 'F' ? [
                    'id'    => $people->getId(),
                    "name"  => $people->getName(),
                    "alias" => $people->getAlias(),
                    'email' => $emailText,
                    'phone' => $phoneText,
                ] : null)
            ] : $this->getEmployeesContact($people),
        ];
    }

    private function getPeopleAddress(People $people): ?array
    {
        if (($address = $people->getAddress()->first()) === false)
            return null;

        // when we search a specific people address

        if (($search = $this->rq->query->get('address', false)) !== false) {

            if (is_array($search)) {

                // fix country name 'cause in bd "brasil" is saved in english (with z)

                $search['country'] = strtolower($search['country']) == 'brasil' ? 'Brazil' : $search['country'];

                /**
                 * @var \ControleOnline\Repository\AddressRepository $addRepo
                 */
                $addRepo = $this->em->getRepository(Address::class);
                $address = $addRepo->findOneByCityStateCountryOfPeople($search['city'], $search['state'], $search['country'], $people);

                if ($address === null)
                    return null;
            }

        }

        $street   = $address->getStreet();
        $district = $street->getDistrict();
        $city     = $district->getCity();
        $state    = $city->getState();

        return [
            'id' => $address->getId(),
            'country'    => $this->fixCountryName($state->getCountry()->getCountryName()),
            'state'      => $state->getUF(),
            'city'       => $city->getCity(),
            'district'   => $district->getDistrict(),
            'postalCode' => $this->fixPostalCode($street->getCep()->getCep()),
            'street'     => $street->getStreet(),
            'number'     => $address->getNumber(),
            'complement' => $address->getComplement(),
        ];
    }

    private function getEmployeesContact(People $people): array
    {
        $employees = [];

        if ($people->getPeopleType() != 'J')
            return $employees;

        // if company has employees

        if ($people->getPeopleEmployee()->count() > 0) {

            foreach ($people->getPeopleEmployee() as $peopleEmployee) {
                // TODO: Corrigir o cadastro do cliente e deixar o contato ativo!
                //  if ($peopleEmployee->getEnabled() == false)
                //      continue;

                $employee = $peopleEmployee->getEmployee();
                $contact  = $this->getContact($employee);

                if (is_array($contact))
                    $employees[] = $contact;
            }

        } else {
            $contact = $this->getContact($people);

            if (is_array($contact))
                $employees[] = $contact;
        }

        return $employees;
    }

    private function getContact(People $people): ?array
    {
        $email  = '';
        $code   = '';
        $number = '';

        if ($people->getEmail()->count() == 0)
            return null;

        if ($people->getPhone()->count() == 0)
            return null;

        $email  = $people->getEmail()->first()->getEmail();
        $phone  = $people->getPhone()->first();
        $code   = $phone->getDdd();
        $number = $phone->getPhone();

        return [
            'id'    => $people->getId(),
            'name'  => $people->getName(),
            'alias' => $people->getAlias(),
            'email' => $email,
            'phone' => sprintf('%s%s', $code, $number),
        ];
    }

    private function getPeopleByEmail(string $email): ?People {
        $email = $this->em->getRepository(Email::class)->findOneBy(["email" => $email]);

        return $email instanceof Email ? $email->getPeople() : null;
    }

    private function getPeopleByDocument(string $document): ?People
    {
        $docType  = $this->getPeopleDocumentType($document);
        $document = $this->em->getRepository(Document::class)->findOneBy(['document' => $document, 'documentType' => $docType]);

        return $document instanceof Document ? $document->getPeople() : null;
    }

    private function getPeopleDocumentType(string $document): ?DocumentType
    {
        $filter = [
            'documentType' => $this->getDocumentType($document),
            'peopleType'   => $this->getDocumentType($document) == 'CNPJ' ? 'J' : 'F',
        ];

        return $this->em->getRepository(DocumentType::class)->findOneBy($filter);
    }

    private function getDocumentType(string $document): ?string
    {
        return strlen($document) == 14 ? 'CNPJ' : (strlen($document) == 11 ? 'CPF' : null);
    }

    private function fixCountryName(string $originalName): string
    {
        return strtolower($originalName) == 'brazil' ? 'Brasil' : $originalName;
    }

    private function fixPostalCode(int $postalCode): string
    {
        $code = (string)$postalCode;
        return strlen($code) == 7 ? '0' . $code : $code;
    }
}
