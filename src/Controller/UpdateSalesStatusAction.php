<?php

namespace App\Controller;

use ControleOnline\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\Order as Order;
use ControleOnline\Entity\Task;
use ControleOnline\Entity\Category;
use ControleOnline\Entity\TaskInteration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class UpdateSalesStatusAction
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
    private $request = null;

    /**
     * Update Order Status actions
     *
     * @var array
     */
    private $updates = [
        'approve_order',
        'cancel_order',
        'release_payment',
        'add_retrieve',
        'add_delivered',
        'waiting_retrieve',
        'stop_order',
        'restart_order',
        'approve_declaration'
    ];

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

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->manager = $entityManager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
    }

    public function __invoke(Order $data, Request $request): Order
    {
        $this->request = $request;

        try {

            $payload = json_decode($this->request->getContent(), true);

            if (!isset($payload['update']) || empty($payload['update']))
                throw new \Exception('Update param is not defined');

            if (!in_array($payload['update'], $this->updates))
                throw new \Exception(sprintf('Update "%s" is not valid', $payload['update']));

            switch ($payload['update']) {
                case 'waiting_retrieve':
                    if ($data->getStatus()->getStatus() != 'retrieved')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting retrieve']);
                    if ($status === null)
                        throw new \Exception('Order status "waiting retrieve" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);
                    break;
                case 'restart_order':

                    if ($data->getStatus()->getStatus() != 'pending')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting payment']);
                    if ($status === null)
                        throw new \Exception('Order status "automatic analysis" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);

                    break;


                case 'approve_declaration':

                    if ($data->getStatus()->getStatus() != 'waiting client invoice tax' && $data->getStatus()->getStatus() != 'pending')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting payment']);
                    if ($status === null)
                        throw new \Exception('Order status "waiting payment" not found');

                    $data->addOtherInformations('declaration', 'declaration');
                    $data->setStatus($status);
                    $data->setNotified(0);

                    break;

                case 'approve_order':

                    if ($data->getStatus()->getStatus() != 'analysis')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting payment']);
                    if ($status === null)
                        throw new \Exception('Order status "waiting payment" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);

                    break;

                case 'stop_order':

                    if ($data->getStatus()->getRealStatus() != 'open' && $data->getStatus()->getRealStatus() != 'pending')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'pending']);
                    if ($status === null)
                        throw new \Exception('Order status "pending" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);

                    break;
                case 'add_delivered':
                    if ($data->getStatus()->getStatus() != 'on the way')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'delivered']);
                    if ($status === null)
                        throw new \Exception('Order status "delivered" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);
                    break;
                case 'add_retrieve':
                    if ($data->getStatus()->getStatus() != 'waiting retrieve')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'retrieved']);
                    if ($status === null)
                        throw new \Exception('Order status "retrieved" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);
                    break;
                case 'release_payment':
                    if ($data->getStatus()->getStatus() != 'waiting payment')
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting retrieve']);
                    if ($status === null)
                        throw new \Exception('Order status "waiting retrieve" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);


                    $category = $this->manager->getRepository(Category::class)->findOneBy(['name' => ['Financeiro']]);
                    if (!$category) {
                        $category = new Category();
                        $category->setName('Financeiro');
                        $category->setContext('support');
                        $this->manager->persist($category);
                        $this->manager->flush();
                    }

                    $task = new Task();
                    $task->setClient($data->getClient());
                    $task->setDueDate(new \DateTime('now'));
                    $task->setOrder($data);
                    $task->setProvider($data->getProvider());
                    $task->setTaskFor($this->currentUser->getPeople());
                    $task->setRegisteredBy($this->currentUser->getPeople());
                    $task->setCategory($category);
                    $task->setName('Pagamento liberado manualmente');
                    $task->setType('support');
                    $task->setTaskStatus($this->manager->getRepository(Status::class)->findOneBy(['status' => ['closed']]));

                    $taskInteration = new TaskInteration();
                    $taskInteration->setType('comment');
                    $taskInteration->setVisibility('private');
                    $taskInteration->setBody('Pagamento liberado por ' . $this->currentUser->getUsername());
                    $taskInteration->setTask($task);
                    $taskInteration->setRegisteredBy($this->currentUser->getPeople());



                    $this->manager->persist($task);
                    $this->manager->persist($taskInteration);
                    $this->manager->flush();

                    break;
                case 'cancel_order':

                    if (!in_array($data->getStatus()->getRealStatus(), ['open', 'pending']))
                        throw new \Exception('Order status can not be modified');

                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'canceled']);
                    if ($status === null)
                        throw new \Exception('Order status "canceled" not found');

                    $data->setStatus($status);
                    $data->setNotified(0);

                    break;
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }

        return $data;
    }
}
