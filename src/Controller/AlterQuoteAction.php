<?php

namespace App\Controller;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\Quotation;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\Order as Order;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class AlterQuoteAction
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

        $payload   = json_decode($request->getContent(), true);
        try {

            $quote_id = $payload['params'] ? $payload['params']['quote'] : null;

            if ($quote_id) {
                $this->manager->getConnection()->beginTransaction();
                /**
                 * @var \ControleOnline\Entity\Quotation $quote
                 */

                $quote = $this->manager->getRepository(Quotation::class)->find($quote_id);

                if (
                    $quote->getOrder()->getId() == $data->getId() &&
                    in_array($data->getStatus()->getStatus(), [
                        'quote',
                        'waiting client invoice tax',
                        'automatic analysis',
                        'analysis'
                    ])
                ) {

                    $data->setQuote($quote);
                    $data->setPrice($quote->getTotal());
                    $this->manager->persist($data);
                    $this->manager->flush();
                } else {

                    $data->setStatus($this->manager->getRepository(Status::class)->findOneBy(
                        ['status' => 'waiting client invoice tax']
                    ));
                    $data->setQuote($quote);
                    $data->setPrice($quote->getTotal());
                    $this->manager->persist($data);
                    $this->manager->flush();
                }

                $this->manager->getConnection()->commit();
            }

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        'order' => $data->getId(),
                        'quote' => $quote->getId(),
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
}
