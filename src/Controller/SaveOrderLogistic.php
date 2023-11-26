<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\OrderLogistic;
use Symfony\Component\Security\Core\Security;
use App\Entity\Document;
use App\Entity\DocumentType;
use App\Entity\Particulars;
use App\Entity\ParticularsType;
use App\Entity\People;
use ControleOnline\Entity\PurchasingOrder;
use App\Entity\SalesOrder;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\User;
use DateTime;

class SaveOrderLogistic
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->manager = $entityManager;
        $this->security = $security;
    }

    /**
   *
   * @Route("/order_logistics/create","POST")
   */
    public function saveStretch(Request $request): JsonResponse
    {
        try {
            
            $data = new OrderLogistic();
            /**
             * Current user
             *
             * @var \ControleOnline\Entity\User
             */
            $inCharge = $this->security->getUser();

            $payload = json_decode($request->getContent(), true);

            if (isset($payload['estimatedShippingDate'])) {
                $data->setEstimatedShippingDate(new DateTime($payload['estimatedShippingDate']));
            }

            if (isset($payload['shippingDate'])) {
                $data->setShippingDate(new DateTime($payload['shippingDate']));
            }
            
            if (isset($payload['estimatedArrivalDate'])) {
                $data->setEstimatedArrivalDate(new DateTime($payload['estimatedArrivalDate']));
            }
            
            if (isset($payload['arrivalDate'])) {
                $data->setArrivalDate(new DateTime($payload['arrivalDate']));
            }
            
            if (isset($payload['originType'])) {
                $data->setOriginType($payload['originType']);
            }
            
            if (isset($payload['originRegion'])) {
                $data->setOriginRegion($payload['originRegion']);
            }
            
            if (isset($payload['originState'])) {
                $data->setOriginState($payload['originState']);
            }
            
            if (isset($payload['originCity'])) {
                $data->setOriginCity($payload['originCity']);
            }

            if (isset($payload['originAdress'])) {
                $data->setOriginAddress($payload['originAdress']);
            }

            if (isset($payload['originLocator'])) {
                $data->setOriginLocator($payload['originLocator']);
            }
            

            if (isset($payload['price'])) {
                $data->setPrice($payload['price']);
            }
            
            if (isset($payload['amountPaid'])) {
                $data->setAmountPaid($payload['amountPaid']);
            }
            

            if (isset($payload['order'])) {
                $purchasingOrder = $this->manager->getRepository(SalesOrder::class)->find($payload['order']);
                $data->setOrder($purchasingOrder);
            }

            if (isset($payload['provider'])) {
                $provider = $this->manager->getRepository(People::class)->find($payload['provider']);
                $data->setProvider($provider);
            }

            if (isset($payload['status'])) {
                $status = $this->manager->getRepository(Status::class)->find($payload['status']);
                $data->setStatus($status);
            }


            if (isset($payload['destinationType'])) {
                $data->setDestinationType($payload['destinationType']);
            }

            if (isset($payload['destinationRegion'])) {
                $data->setDestinationRegion($payload['destinationRegion']);
            }

            if (isset($payload['destinationState'])) {
                $data->setDestinationState($payload['destinationState']);
            }

            if (isset($payload['destinationCity'])) {
                $data->setDestinationCity($payload['destinationCity']);
            }

            if (isset($payload['destinationAdress'])) {
                $data->setDestinationAdress($payload['destinationAdress']);
            }

            if (isset($payload['destinationLocator'])) {
                $data->setDestinationLocator($payload['destinationLocator']);
            }

            if (isset($payload['destinationProvider'])) {
                $destinationProvider = $this->manager->getRepository(People::class)->find($payload['destinationProvider']);
                $data->setDestinationProvider($destinationProvider);
            }

            if (isset($inCharge)) {
                $data->setInCharge($inCharge->getPeople());
            }

            if (isset($payload['lastModified'])) {
                $lastModified = DateTime::createFromFormat('Y-m-d H:i:s', $payload['lastModified']);
                $data->setLastModified($lastModified);
            }
            // dd($data);

            $this->manager->persist($data);
            $this->manager->flush();
            // dd($data);
            return new JsonResponse([
                'response' => [
                    'data'    => $data->getId(),
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
   *
   * @Route("/order_logistics/update","PUT")
   */
    public function updateStretch(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            
            $data = $this->manager->getRepository(OrderLogistic::class)->find($payload['id']);
            // dd($data);
            /**
             * Current user
             *
             * @var \ControleOnline\Entity\User
             */
            $inCharge = $this->security->getUser();


            if (isset($payload['estimatedShippingDate'])) {
                $data->setEstimatedShippingDate(new DateTime($payload['estimatedShippingDate']));
            }

            if (isset($payload['shippingDate'])) {
                $data->setShippingDate(new DateTime($payload['shippingDate']));
            }
            
            if (isset($payload['estimatedArrivalDate'])) {
                $data->setEstimatedArrivalDate(new DateTime($payload['estimatedArrivalDate']));
            }
            
            if (isset($payload['arrivalDate'])) {
                $data->setArrivalDate(new DateTime($payload['arrivalDate']));
            }
            
            if (isset($payload['originType'])) {
                $data->setOriginType($payload['originType']);
            }
            
            if (isset($payload['originRegion'])) {
                $data->setOriginRegion($payload['originRegion']);
            }
            
            if (isset($payload['originState'])) {
                $data->setOriginState($payload['originState']);
            }
            
            if (isset($payload['originCity'])) {
                $data->setOriginCity($payload['originCity']);
            }

            if (isset($payload['originAdress'])) {
                $data->setOriginAddress($payload['originAdress']);
            }

            if (isset($payload['originLocator'])) {
                $data->setOriginLocator($payload['originLocator']);
            }
            

            if (isset($payload['price'])) {
                $data->setPrice($payload['price']);
            }
            
            if (isset($payload['amountPaid'])) {
                $data->setAmountPaid($payload['amountPaid']);
            }
            

            if (isset($payload['order'])) {
                $purchasingOrder = $this->manager->getRepository(SalesOrder::class)->find($payload['order']);
                $data->setOrder($purchasingOrder);
            }

            if (isset($payload['provider'])) {
                $provider = $this->manager->getRepository(People::class)->find($payload['provider']);
                $data->setProvider($provider);
            }

            if (isset($payload['status'])) {
                $status = $this->manager->getRepository(Status::class)->find($payload['status']);
                $data->setStatus($status);
            }


            if (isset($payload['destinationType'])) {
                $data->setDestinationType($payload['destinationType']);
            }

            if (isset($payload['destinationRegion'])) {
                $data->setDestinationRegion($payload['destinationRegion']);
            }

            if (isset($payload['destinationState'])) {
                $data->setDestinationState($payload['destinationState']);
            }

            if (isset($payload['destinationCity'])) {
                $data->setDestinationCity($payload['destinationCity']);
            }

            if (isset($payload['destinationAdress'])) {
                $data->setDestinationAdress($payload['destinationAdress']);
            }

            if (isset($payload['destinationLocator'])) {
                $data->setDestinationLocator($payload['destinationLocator']);
            }

            if (isset($payload['destinationProvider'])) {
                $destinationProvider = $this->manager->getRepository(People::class)->find($payload['destinationProvider']);
                $data->setDestinationProvider($destinationProvider);
            }

            if (isset($inCharge)) {
                $data->setInCharge($inCharge->getPeople());
            }

            if (isset($payload['lastModified'])) {
                $lastModified = DateTime::createFromFormat('Y-m-d H:i:s', $payload['lastModified']);
                $data->setLastModified($lastModified);
            }
            // dd($data);

            $this->manager->persist($data);
            $this->manager->flush();
            // dd($data);
            return new JsonResponse([
                'response' => [
                    'data'    => $data->getId(),
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
}
