<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\Status;
use App\Entity\Quotation;

use App\Entity\SalesOrder as Order;

class UpdateSalesOrderDeadlineAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->manager = $entityManager;
    }

    public function __invoke(Order $data, Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);

            $newDateString = $payload['deadline'];

            if (!isset($newDateString)) {
                throw new \InvalidArgumentException('New order deadline was not defined', 400);
            }

            $status = $data->getStatus();




            if ($status->getStatus() !== 'on the way' && $status->getStatus() !== 'retrieved' && $status->getStatus() !== 'delivered') {
                throw new \InvalidArgumentException('Order status was not suported', 400);
            }

            //$alterData = $data->getAlterDate();
            $quote     = $data->getQuote();
            $deadLine  = $quote->getDeadline();
            $deadLine = $deadLine > 0 ? $deadLine : 0;

            /**
             * @var \DateTime $actualDeadline
             */
            $today = new \DateTime('now');
            //$actualDeadline = new \DateTime($alterData->format('Y-m-d'));
            //$actualDeadline->add(new \DateInterval(sprintf('P%dD', $deadLine)));

            $newDate = \DateTime::createFromFormat('d/m/Y', $newDateString);

            $diff     = date_diff($today, $newDate);
            $diffDays = (int) $diff->format('%a');
            $quotation = $this->manager->getRepository(Quotation::class);
            $retrieveDays = $quotation->getRetrieveDeadline($quote);
            $newDeadline = $diffDays;

            //if ($actualDeadline < $newDate) {
            //$newDeadline = $deadLine + $diffDays;                
            //} else {
            //$diffDays = $diffDays + 1;
            //$newDeadline = $deadLine - $diffDays;
            //}            

            $quote->setDeadline($newDeadline);
            //$data->setAlterDate($alter_date->sub(new \DateInterval('P' . $retrieveDays . 'D')));
            //$data->setAlterDate($today);
            $data->setComments($data->getComments() . ' ');

            if ($status->getStatus() === 'delivered') {
                $data->setStatus($this->manager->getRepository(Status::class)->findOneBy(['status' => 'on the way']));
            }


            $this->manager->persist($data);
            $this->manager->persist($quote);
            $this->manager->flush();

            return new JsonResponse([
                'response' => [
                    'data'    => array(
                        'newDeadline' => $newDeadline,
                        'retrieveDays' => $retrieveDays,
                        'status' => $status->getStatus(),
                        'actualDeadline' => $today->format('d/m/Y'),
                        'newDate' => $newDate->format('d/m/Y'),
                        'diffDays' => $diffDays
                    ),
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
