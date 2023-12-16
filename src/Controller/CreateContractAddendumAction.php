<?php

namespace App\Controller;

use ControleOnline\Entity\MyContract;
use Doctrine\ORM\EntityManagerInterface;

class CreateContractAddendumAction
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

    public function __invoke(MyContract $data): MyContract
    {
        if ($data->getContractStatus() != 'Active') {
            throw new \Exception('This contract is not active');
        }

        // clone contract

        $contract = clone $data;
        $contract->setEndDate       (null);
        $contract->setContractStatus('Draft');
        $contract->setContractParent($data);

        $this->manager->persist($contract);

        // add provider

        $contractsPeople = $data->getContractPeople()
          ->filter(function($peopleContract) {
            return $peopleContract->getPeopleType() == 'Provider';
          });
        if (($contractPeople = $contractsPeople->first()) === false) {
          throw new \Exception('There is no provider');
        }
        else {
          $peopleContract = clone $contractPeople;

          $peopleContract->setContract($contract);

          $this->manager->persist($peopleContract);
        }

        // set original contract new status

        $data->setContractStatus('Amended');

        return $contract;
    }
}
