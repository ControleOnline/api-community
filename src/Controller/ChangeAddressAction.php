<?php

namespace App\Controller;

use ControleOnline\Entity\Address;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\Street;
use ControleOnline\Entity\District;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\State;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class ChangeAddressAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(Request $request, Order $data): JsonResponse
    {

        $order = $this->manager->getRepository(Order::class)
            ->find($data->getId());


        $payload   = json_decode($request->getContent(), true);


        try {
            $this->manager->getConnection()->beginTransaction();

            $order->addOtherInformations($payload['type'] . '_address_type', $payload['address_type']['value']);

            if ($payload['address_type']['value'] == 'meeting') {
                $this->addQuoteCity($data, $payload);
            } else {
                $address  =  $this->addAddress($payload);
                if ($payload['type'] == 'delivery') {
                    $order->setAddressDestination($address);
                } else {
                    $order->setAddressOrigin($address);
                }
                $this->manager->persist($order);
                $this->manager->flush();
            }



            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        'order' => $order->getId(),
                        'address' => !empty($address) ? $address->getId() : null
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
        } catch (\Throwable $e) {
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


    private function addQuoteCity(Order $order, $address)
    {

        $state = $this->manager->getRepository(State::class)
            ->findOneBy([
                "uf" => $address["state"]
            ]);

        if (empty($state)) {
            throw new BadRequestHttpException('state is not valid');
        }

        $city = $this->manager->getRepository(City::class)
            ->findOneBy([
                "city" => $address["city"],
                "state" => $state
            ]);

        if (empty($city)) {
            $city = new City();

            $city->setState($state);
            $city->setCity($address["city"]);

            $this->manager->persist($city);
            $this->manager->flush();
        }

        $quote = $order->getQuote();

        if ($address['type'] == 'delivery') {
            $quote->setCityDestination($city);
        } else {
            $quote->setCityOrigin($city);
        }

        $this->manager->persist($quote);
        $this->manager->flush();
    }

    private function addAddress($address)
    {
        $cep = $this->manager->getRepository(Cep::class)
            ->findOneBy([
                "cep" => preg_replace("/[^0-9]/", "", $address["postal_code"])
            ]);

        if (empty($cep)) {
            $cep = new Cep();

            $cep->setCep(preg_replace("/[^0-9]/", "", $address["postal_code"]));

            $this->manager->persist($cep);
            $this->manager->flush();
        }

        $state = $this->manager->getRepository(State::class)
            ->findOneBy([
                "uf" => $address["state"]
            ]);

        if (empty($state)) {
            throw new BadRequestHttpException('state is not valid');
        }

        $city = $this->manager->getRepository(City::class)
            ->findOneBy([
                "city" => $address["city"],
                "state" => $state
            ]);

        if (empty($city)) {
            $city = new City();

            $city->setState($state);
            $city->setCity($address["city"]);

            $this->manager->persist($city);
            $this->manager->flush();
        }

        $district = $this->manager->getRepository(District::class)
            ->findOneBy([
                "district" => $address["district"],
                "city" => $city
            ]);

        if (empty($district)) {
            $district = new District();

            $district->setCity($city);
            $district->setDistrict($address["district"]);

            $this->manager->persist($district);
            $this->manager->flush();
        }

        $street = $this->manager->getRepository(Street::class)
            ->findOneBy([
                "street" => $address["street"],
                "cep" => $cep,
                "district" => $district
            ]);

        if (empty($street)) {
            $street = new Street();

            $street->setCep($cep);
            $street->setStreet($address["street"]);
            $street->setDistrict($district);
            $street->setCep($cep);

            $this->manager->persist($street);
            $this->manager->flush();
        }

        $newAddress = new Address();
        $newAddress->setNickname('Endereço Principal');
        $newAddress->setNumber($address["number"]);
        $newAddress->setComplement($address["complement"]);
        $newAddress->setStreet($street);

        $this->manager->persist($newAddress);
        $this->manager->flush();

        return $newAddress;
    }
}
