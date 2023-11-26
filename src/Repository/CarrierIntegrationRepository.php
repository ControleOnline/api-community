<?php

namespace App\Repository;

use App\Entity\CarrierIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CarrierIntegration|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarrierIntegration|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarrierIntegration[]    findAll()
 * @method CarrierIntegration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarrierIntegrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarrierIntegration::class);
    }
}
