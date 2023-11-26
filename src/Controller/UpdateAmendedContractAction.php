<?php 

namespace App\Controller;

use App\Entity\MyContract;
use Doctrine\ORM\EntityManagerInterface;

class UpdateAmendedContractAction
{
    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(MyContract $data): MyContract
    {
        if (in_array($data->getContractStatus(), ['Draft', 'Amended'])) {
            throw new \Exception('This contract cannot be amended');
        }

        $data->setContractStatus('Amended');

        // Aqui você pode adicionar lógica adicional, se necessário, para lidar com contratos emendados

        $this->manager->persist($data);
        $this->manager->flush();

        return $data;
    }
}
