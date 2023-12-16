<?php

namespace App\Controller;

use ControleOnline\Entity\Client;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleSalesman;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class GetClientCollectionAction
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
     * Clients repository
     *
     * @var ClientRepository
     */
    private $clients  = null;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->manager  = $entityManager;
        $this->security = $security;
        $this->clients  = $this->manager->getRepository(Client::class);
    }

    public function __invoke(Request $request)
    {
        $this->request = $request;

        $type     = $this->request->query->get('type', null);
        $fromDate = $this->request->query->get('from', null);
        $toDate   = $this->request->query->get('to'  , null);

        /**
         * @var \ControleOnline\Entity\User $myUser
         */
        $myUser   = $this->security->getUser();

        /**
         * @var \ControleOnline\Entity\People $salesman
         */
        $salesman = $myUser->getPeople();

        return $this->clients
            ->getSalesmanClientCollection($type, $fromDate, $toDate, $this->getMyProvider(), $salesman);
    }

    private function getMyProvider(): ?People
    {
        $providerId = $this->request->query->get('myProvider', null);
        if ($providerId === null)
            return null;

        $peopleRepo = $this->manager->getRepository(People::class);
        $provider   = $peopleRepo->find($providerId);
        if ($provider === null)
            return null;

        /**
         * @var \App\Repository\PeopleSalesmanRepository
         */
        $salesman = $this->manager->getRepository(PeopleSalesman::class);
        /**
         * @var \ControleOnline\Entity\User
         */
        $myUser   = $this->security->getUser();

        if (!$salesman->companyIsMyProvider($myUser->getPeople(), $provider))
            return null;

        return $provider;
    }
}
