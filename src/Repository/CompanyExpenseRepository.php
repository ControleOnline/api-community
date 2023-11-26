<?php

namespace App\Repository;

use App\Entity\CompanyExpense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CompanyExpense|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyExpense|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyExpense[]    findAll()
 * @method CompanyExpense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyExpense::class);
    }
}
