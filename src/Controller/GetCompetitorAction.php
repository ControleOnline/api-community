<?php

namespace App\Controller;

use App\Entity\SalesOrder;
use App\Entity\Quotation;
use App\Library\Competitor\CargoBR\Client as CargoClient;
use App\Library\Competitor\GoFretes\Client as GoClient;
use App\Library\Competitor\Central\Client as CentralClient;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GetCompetitorAction
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

    public function __invoke(SalesOrder $data, Request $request): JsonResponse
    {

        try {
            $this->manager->getConnection()->beginTransaction();
            $payload   = json_decode($request->getContent(), true);
            if (!$payload['competitor']) {
                throw new Exception('Competitor not found');
            } else {

                switch ($payload['competitor']) {
                    case 'cargo':
                        $client  = new CargoClient();
                        break;
                    case 'go':
                        $client  = new GoClient();
                        break;
                    case 'central':
                        $client  = new CentralClient();
                        break;
                    default:
                        throw new Exception('Competitor not found');
                        break;
                }

                $return = $client->quote($data);
                $otherInformations =  $data->getOtherInformations(true);                
                $otherInformations->competitor->{$payload['competitor']} = $return->competitor;
                $data->addOtherInformations('competitor', $otherInformations->competitor);

                $this->manager->persist($data);
                $this->manager->flush();
                $this->manager->getConnection()->commit();

                return new JsonResponse([
                    'response' => [
                        'data'    =>  $return,
                        'count'   => 1,
                        'success' => true,
                    ],
                ]);
            }
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
