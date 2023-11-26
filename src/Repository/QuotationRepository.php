<?php

namespace App\Repository;

use App\Entity\Quotation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method Quotation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Quotation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Quotation[]    findAll()
 * @method Quotation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuotationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quotation::class);
    }

    public function getRetrieveDeadline(Quotation $quotation): int
    {
        $sql = "
            SELECT

                reg.deadline

            FROM quote quo

            INNER JOIN quote_detail qud ON
                qud.quote_id = quo.id AND qud.region_origin_id IS NOT NULL AND qud.region_destination_id IS NOT NULL

            INNER JOIN delivery_region reg ON
                reg.id = qud.region_origin_id

            WHERE
                quo.id = :quotation_id
            
            LIMIT 1
        ";

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('deadline', 'deadline', 'integer');

        $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $nqu->setParameter('quotation_id', $quotation->getId());

        if (empty($nqu->getArrayResult()))
            return 0;

        return $nqu->getArrayResult()[0]['deadline'];
    }
}
