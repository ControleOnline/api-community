<?php

namespace App\Controller;

use App\Entity\MyContract;
use Doctrine\ORM\EntityManagerInterface;

class UpdateCancelContractAction
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
        if (!in_array($data->getContractStatus(), ['Draft', 'Active']))
            throw new \Exception('This contract can not be canceled');

        return $data;
    }
}
