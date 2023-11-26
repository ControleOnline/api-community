<?php

namespace App\Repository;

use App\Entity\MyContractOrderInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyContractOrderInvoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyContractOrderInvoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyContractOrderInvoice[]    findAll()
 * @method MyContractOrderInvoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyContractOrderInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyContractOrderInvoice::class);
    }
}
