<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\MyContract;

class GetContractParticipantsAction
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

    public function __invoke(MyContract $data, Request $request): JsonResponse
    {
        $this->rq = $request;

        try {

            $participants = [];

            if (!$data->getContractPeople()->isEmpty()) {
                /**
                 * @var \ControleOnline\Entity\MyContractPeople $contractPeople
                 */
                foreach ($data->getContractPeople() as $contractPeople) {
                    $participants[] = [
                        'people'     => $this->getContactByPeople($contractPeople->getPeople()),
                        'role'       => $contractPeople->getPeopleType(),
                        'percentage' => $contractPeople->getContractPercentage(),
                    ];
                }
            }

            return new JsonResponse([
                'response' => [
                    'data'    => $participants,
                    'count'   => count($participants),
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
        if (($document = $people->getDocument()->first()) === false)
            throw new \Exception('Contact missing document');

        return [
            'id'       => $people->getId(),
            'name'     => $people->getFullName(),
            'alias'    => $people->getAlias(),
            'document' => [
                'id'   => $document->getDocument(),
                'type' => $document->getDocumentType()->getDocumentType(),
            ],
            'address' => $this->getPeopleAddress($people),
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
            'id'         => $address->getId(),
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
