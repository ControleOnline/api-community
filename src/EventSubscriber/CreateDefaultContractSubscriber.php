<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class CreateDefaultContractSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * Request
     *
     * @var Request
     */
    private $request;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->tokenStorage = $tokenStorage;
        $this->manager      = $entityManager;
        $this->request      = $requestStack->getCurrentRequest();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['createDefaultContract', EventPriorities::PRE_WRITE],
        ];
    }

    public function createDefaultContract(ViewEvent $event)
    {
        /**
         * @var \App\Entity\MyContract $entity
         */
        $entity = $event->getControllerResult();
        if (is_object($entity) && (get_class($entity) !== \App\Entity\MyContract::class)) {
            return;
        }

        if (Request::METHOD_POST !== $event->getRequest()->getMethod()) {
            return;
        }

        if (empty($this->request->query->get('myProvider', null)))
            return;

        $peopleRepository = $this->manager->getRepository(\App\Entity\People::class);
        $peopleProvider   = $peopleRepository->find($this->request->query->get('myProvider'));
        if (!$peopleProvider instanceof \App\Entity\People)
            return;

        $contractModel    = $this->manager->getRepository(\App\Entity\MyContractModel::class)->find(1);
        if ($contractModel === null)
            return;

        // modify contract

        $entity->setContractModel($contractModel);
        $entity->setContractStatus('Draft');
        $entity->setStartDate(new \DateTime('now'));

        // create contract basic components

        /*
        $provider = new \App\Entity\MyContractPeople();

        $provider->setContract  ($entity);
        $provider->setPeople    ($peopleProvider);
        $provider->setPeopleType('Provider');

        $this->manager->persist($provider);
        */
    }
}
