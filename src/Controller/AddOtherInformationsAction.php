<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\InvalidValueException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Order as Order;
use ControleOnline\Entity\Document;


use App\Service\AddressService;
use App\Service\PeopleService;


use ControleOnline\Entity\People;
use stdClass;

class AddOtherInformationsAction
{

    private $manager = null;

    private $address = null;

    private $people = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        AddressService $address,
        PeopleService $people
    ) {
        $this->manager = $entityManager;
        $this->address = $address;
        $this->people  = $people;
    }

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        try {
            // dd((array)$data->getOtherInformations(true));
            if ($content = $request->getContent()) {
                $params = json_decode($content, true);
                if (!$params['other_informations']){
                    throw new InvalidValueException('You need just a one information');
                }
                
                $this->manager->getConnection()->beginTransaction();
                // ja estava comentado    $this->updateOrder($data, $params);

                // $k = isset($params['other_informations_type']) ? $params['other_informations_type'] : null;
                // if ($k) {
                //     $otherInformations =  (array)$data->getOtherInformations(true);
                //     foreach ($params['other_informations'] as $key => $information) {
                //         if (!isset($otherInformations[$k]))
                //             $otherInformations[$k] = new stdClass();
                //         $otherInformations[$k] = $information;
                //     }
                //     $data->addOtherInformations($k, $otherInformations[$k]);
                // } else {
                //     foreach ($params['other_informations'] as $key => $information) {
                //         $data->addOtherInformations($key, $information);
                //     }
                // }
                $infoName = isset($params['other_informations_type']) ? $params['other_informations_type'] : null;
                $infoValue = $params['other_informations'];
                if (!$infoName) {
                    throw new InvalidValueException('You need just a one information');
                }
                $data->addOtherInformations($infoName, $infoValue);

                $this->manager->persist($data);
                $this->manager->flush();
                $this->manager->getConnection()->commit();
            }

            return new JsonResponse(['@id' => $data->getId()], 200);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }

            return new JsonResponse([
                'response' => [
                    'data'    => null,
                    'error'   => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file' => basename($e->getFile()),
                    'success' => false,
                ],
            ]);
        }
    }
}
