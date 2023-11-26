<?php

namespace App\Repository;

use App\Entity\MyContractModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyContractModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyContractModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyContractModel[]    findAll()
 * @method MyContractModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyContractModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyContractModel::class);
    }
}
