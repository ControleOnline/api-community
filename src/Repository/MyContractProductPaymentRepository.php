<?php

namespace App\Repository;

use ControleOnline\Entity\MyContractProductPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyContractProductPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyContractProductPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyContractProductPayment[]    findAll()
 * @method MyContractProductPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyContractProductPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyContractProductPayment::class);
    }
}
