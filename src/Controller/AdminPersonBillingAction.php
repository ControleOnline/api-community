<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

use App\Entity\People;
use App\Entity\Document;
use App\Entity\DocumentType;
use App\Entity\Person;

class AdminPersonBillingAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    public function __construct(EntityManagerInterface $manager, Security $security)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(Person $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {

            $methods = [
                Request::METHOD_PUT => 'updateBilling',
                Request::METHOD_GET => 'getBilling'   ,
            ];

            $payload   = json_decode($this->request->getContent(), true);
            $operation = $methods[$request->getMethod()];
            $result    = $this->$operation($data, $payload);

            return new JsonResponse([
                'response' => [
                    'data'    => $result,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);

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

    private function updateBilling(Person $person, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            $company = $this->manager->getRepository(People::class)->find($person->getId());

            // update billing

            if (isset($payload['billing'])) {
                if (!is_numeric($payload['billing'])) {
                    throw new \InvalidArgumentException('Billing param is not valid');
                }

                $billing = (float) $payload['billing'];

                $company->setBilling($billing);
            }

            // update billing days

            if (isset($payload['billingDays'])) {
                if (!in_array($payload['billingDays'], ['daily', 'weekly', 'biweekly', 'monthly'])) {
                    throw new \InvalidArgumentException('billingDays param is not valid');
                }

                $company->setBillingDays($payload['billingDays']);
            }

            // update paymentTerm

            if (isset($payload['paymentTerm'])) {
                if (!is_int($payload['paymentTerm'])) {
                    throw new \InvalidArgumentException('PaymentTerm param is not valid');
                }

                if ($payload['paymentTerm'] < 1 || $payload['paymentTerm'] > 31) {
                    throw new \InvalidArgumentException('PaymentTerm param is out of range');
                }

                $billing = (int) $payload['paymentTerm'];

                $company->setPaymentTerm($payload['paymentTerm']);
            }

            $this->manager->persist($company);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;

        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function getBilling(Person $person, ?array $payload = null): array
    {
        $company = $this->manager->getRepository(People::class )->find($person->getId());

        return [
            'billing'     => $company->getBilling(),
            'billingDays' => $company->getBillingDays(),
            'paymentTerm' => $company->getPaymentTerm(),
        ];
    }
}
