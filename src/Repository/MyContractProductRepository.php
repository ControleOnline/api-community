<?php

namespace App\Repository;

use App\Entity\MyContractProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyContractProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyContractProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyContractProduct[]    findAll()
 * @method MyContractProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyContractProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyContractProduct::class);
    }

    public function getContractProducts(int $contractId, ?array $search = null, ?array $paginate = null, ?bool $isCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();

        if ($isCount) {
          $sql = 'SELECT COUNT(DISTINCT cpr.id) AS total';
        }
        else {
          $sql  = 'SELECT cpr.id, pro.product AS product_name, ';
          $sql .= 'cpr.quantity, ';
          $sql .= 'pro.product_type, ';
          $sql .= 'pro.product_subtype, ';
          $sql .= 'cpr.product_price, ';
          $sql .= 'pro.billing_unit, ';
          $sql .= 'CONCAT(peo.name, \' \', peo.alias) AS payer_name, ';
          $sql .= 'COUNT(cpa.id) AS parcels, ';
          $sql .= 'cpa.duedate AS payment_duedate';
        }

        $sql .= ' FROM contract_product cpr';

        $sql .= ' INNER JOIN product_old pro ON pro.id = cpr.product_id';
        $sql .= ' LEFT JOIN contract_product_payment cpa ON cpa.product_id = cpr.id AND cpa.contract_id = cpr.contract_id';
        $sql .= ' LEFT JOIN people peo ON peo.id = cpa.payer_id';

        $sql .= ' WHERE cpr.contract_id = :contract_id';

        // search

        if (is_array($search)) {

        }

        if (!$isCount) {
          $sql .= ' GROUP BY cpr.id, pro.product, cpr.quantity, pro.product_type, cpr.product_price, peo.name, peo.alias';
          $sql .= ' ORDER BY cpr.id';
        }

        // pagination

        if (is_array($paginate) && !$isCount) {
            $sql .= sprintf(' LIMIT %s, %s', $paginate['from'], $paginate['limit']);
        }

        $stmt = $conn->prepare($sql);

        // query params

        $params = ['contract_id' => $contractId];

        if (is_array($search)) {

        }

        // get all

        $stmt->execute($params);
        $result = $stmt->fetchAll();

        if (empty($result)) {
            return $isCount ? 0 : [];
        }

        return $isCount ? (int) $result[0]['total'] : $result;
    }
}
