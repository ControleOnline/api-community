<?php

namespace App\Repository;

use ControleOnline\Entity\QuoteDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method Quotation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Quotation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Quotation[]    findAll()
 * @method Quotation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteDetail::class);
    }
}
