<?php

namespace App\Controller;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\Quotation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class GetAcceptOrderPayerAction extends AbstractController
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
   *
   * @Route("/accept-order-payer/{id}","GET")
   */
    public function AcceptOrder(Request $request): JsonResponse
    {

        try {
            /**
             * @var string $id
             */
            $id = $request->get('id', null);


            /**
             * @var Quotation $order
             */
            $quote = $this->manager->getRepository(Quotation::class)->find($id);

            if (empty($quote)) {
                throw new BadRequestHttpException('Quote is not found');
            }
            /**
             * @var Order $order
             */
            $order = $quote->getOrder();

            if (empty($order)) {
                throw new BadRequestHttpException('Order is not found');
            }

            $carModel = $order->getProductType();
            $other_informations = $order->getOtherInformations(true);

            $document = "";
            $email = "";
            $phone = "";
            $address = null;

            $payer = $order->getClient();

            if (!empty($payer)) {

                $document = $payer->getOneDocument();

                if (!empty($document)) {
                    $document = $document->getDocument();
                } else {
                    $document = "";
                }

                $email = $payer->getOneEmail();

                if (!empty($email)) {
                    $email = $email->getEmail();
                } else {
                    $email = "";
                }

                $phone = $payer->getPhone();

                if (!empty($phone) && count($phone) > 0) {
                    $phone = $phone[0]->getDdd() . $phone[0]->getPhone();
                } else {
                    $phone = "";
                }

                $address = $payer->getAddress();
            }

            $postal_code = "";
            $street = "";
            $number = "";
            $complement = "";
            $district = "";
            $city = "";
            $state = "";
            $country = "";

            if (!empty($address) && isset($address[0])) {
                /**
                 * @var Address $ad0
                 */
                $ad0 = $address[0];

                $country = "Brasil";

                $number = $ad0->getNumber();
                $complement = $ad0->getComplement();

                $street = $ad0->getStreet();

                if (!empty($street)) {

                    $postal_code = $street->getCep();
                    $district = $street->getDistrict();
                    $street = $street->getStreet();

                    if (!empty($postal_code)) {
                        $postal_code = $postal_code->getCep();
                    } else {
                        $postal_code = "";
                    }

                    if (!empty($district)) {
                        $city = $district->getCity();
                        $district = $district->getDistrict();

                        if (!empty($city)) {
                            $state = $city->getState();
                            $city = $city->getCity();

                            if (!empty($state)) {
                                $state = $state->getUf();
                            } else {
                                $state = "";
                            }
                        } else {
                            $city = "";
                        }
                    } else {
                        $district = "";
                    }
                } else {
                    $street = "";
                }
            }

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        "carModel" => $carModel,
                        "other_informations" => [
                            "carColor" => isset($other_informations->carColor) ? $other_informations->carColor : null,
                            "carNumber" => isset($other_informations->carNumber) ? $other_informations->carNumber : null,
                            "renavan" => isset($other_informations->renavan) ? $other_informations->renavan : null,
                        ],
                        "contractId" => $order->getContract() ? $order->getContract()->getId() : null,
                        "peopleId" => empty($payer) ? "" : $payer->getId(),
                        "personType" => empty($payer) ? "PF" : ($payer->getPeopleType() == "J" ? "PJ" : "PF"),
                        "name" => empty($payer) ? "" : $payer->getName(),
                        "alias" => empty($payer) ? "" : $payer->getAlias(),
                        "document" => $document,
                        "email" => $email,
                        "phone" => $phone,
                        "address" => [
                            "postal_code" => $postal_code,
                            "street" => $street,
                            "number" => $number,
                            "complement" => $complement,
                            "district" => $district,
                            "city" => $city,
                            "state" => $state,
                            "country" => $country
                        ]
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
}
