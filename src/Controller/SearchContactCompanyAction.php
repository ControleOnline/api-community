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
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Language;

class SearchContactCompanyAction
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

    private $email;

    /**
     * User entity
     *
     * @var \ControleOnline\Entity\User
     */
    private $user = null;

    private $payload;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->em   = $entityManager;
        $this->user = $security->getUser();
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->rq = $request;
        $this->payload = json_decode($this->rq->getContent());
        $data = [];

        try {
            $myCompany  = $this->rq->query->get('myCompany', null);
            $id         = $this->rq->query->get('id', null);
            $this->email    = $this->rq->query->get('email', isset($this->payload) ? $this->payload->email : null);


            if ($id) {
                $contact    = $this->getPeopleById($id);
                $data = $contact;
            } else {
                $this->email  = isset($this->payload) && isset($this->payload->contact) ? $this->payload->contact->email : $this->email;
                $contact   = $this->email ? $this->getPeopleByEmail($this->email) : null;
                if ($contact instanceof People)
                    $data = $this->getCompanyByContact($contact, $myCompany);
            }

            if (empty($contact))
                throw new \Exception('Contact not found');

            return new JsonResponse([
                'response' => [
                    'data'    => $data,
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


    private function getPeopleById($id)
    {

        $contact = $this->em->getRepository(People::class)->find($id);
        /**
         * @var Phone $phone
         */
        $phone     = null;
        $phoneText = null;
        $phones    = $contact->getPhone();

        if (!empty($phones) && count($phones) > 0) {
            $phone     = $phones[0];
            $phoneText = $phone->getDdd() . $phone->getPhone();
        }

        $document   = $contact->getDocument()->first();
        $address = $this->getPeopleAddress($contact);
        if (!$address) {
            $address = $this->getPeopleAddress($contact);
        }

        return [
            'id'         => $contact->getId(),
            'peopleType' => $contact->getPeopleType(),
            'name'       => $contact->getName(),
            'alias'      => $contact->getAlias(),
            'document'   => !empty($document) ? [
                'id'     => $document->getDocument(),
                'type'   => $document->getDocumentType()->getDocumentType(),
            ] : null,
            'address'   => $address,
            'contact'   =>
            [
                'id'            => $contact->getId(),
                'document'      => $contact->getDocument()->first(),
                "name"          => $contact->getName(),
                "alias"         => $contact->getAlias(),
                'email'         => $contact->getOneEmail(),
                'phone'         => $phoneText,
            ],
        ];
    }

    private function getCompanyByContact(People $contact, $myCompany): array
    {
        $company = [];
        if (count($contact->getPeopleCompany()) == 1) {
            $company = $contact->getPeopleCompany()->first()->getCompany();
        } elseif (count($contact->getPeopleCompany()) > 1) {
            foreach ($contact->getPeopleCompany() as $companies) {
                $mycompanies[] = [
                    'id' => $companies->getCompany()->getId(),
                    'name' => $companies->getCompany()->getName(),
                    'alias' => $companies->getCompany()->getAlias(),
                ];
                if ($companies->getCompany()->getId() == $myCompany) {
                    $company = $companies->getCompany();
                }
            }
            if (!$company) {
                $company = $contact->getPeopleCompany()->first()->getCompany();
            }
        } else {
            $company = new People();
            $company->setPeopleType('J');
            $company->setName('');
            $company->setAlias('');
            $company->setEnabled(1);
            $company->setLanguage(
                $this->em->getRepository(Language::class)
                    ->findOneBy([
                        'language' => 'pt-BR'
                    ])
            );

            $people_employe = new PeopleLink();
            $people_employe->setPeople($contact);
            $people_employe->setCompany($company);


            $this->em->persist($company);
            $this->em->persist($people_employe);
            $this->em->flush();
        }



        /**
         * @var Phone $phone
         */
        $phone     = null;
        $phoneText = null;
        $phones    = $contact->getPhone();

        if (!empty($phones) && count($phones) > 0) {
            $phone     = $phones[0];
            $phoneText = $phone->getDdd() . $phone->getPhone();
        }

        $document   = $company->getDocument()->first();
        $address = $this->getPeopleAddress($company);
        if (!$address) {
            $address = $this->getPeopleAddress($contact);
        }

        return [
            'id'         => $company->getId(),
            'peopleType' => $company->getPeopleType(),
            'name'       => $company->getName(),
            'alias'      => $company->getAlias(),
            'document'   => !empty($document) ? [
                'id'     => $document->getDocument(),
                'type'   => $document->getDocumentType()->getDocumentType(),
            ] : null,
            'address'   => $address,
            'companies' => isset($mycompanies) ? $mycompanies : null,
            'contact'   =>
            [
                'id'            => $contact->getId(),
                'document'      => $contact->getDocument()->first(),
                "name"          => $contact->getName(),
                "alias"         => $contact->getAlias(),
                'email'         =>  $this->email,
                'phone'         => $phoneText,
            ],
        ];
    }

    private function getPeopleAddress(People $people): ?array
    {
        if (($address = $people->getAddress()->first()) === false)
            return null;

        // when we search a specific people address

        if (!empty($this->payload) && ($search = $this->payload->address) !== false) {

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

    private function getPeopleByEmail(string $email): ?People
    {
        $email = $this->em->getRepository(Email::class)->findOneBy(["email" => $email]);

        return $email instanceof Email ? $email->getPeople() : null;
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
