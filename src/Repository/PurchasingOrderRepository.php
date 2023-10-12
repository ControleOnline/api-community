<?php

namespace App\Repository;

use App\Entity\PurchasingOrder AS Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchasingOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

        /**
         * @param int $invoiceId
         * @return Order[]
         * @throws DBALException
         */
    public function findOrdersByInvoice($invoiceId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'SELECT o.*, os.status, os.real_status
            FROM orders o
                     INNER JOIN order_invoice oi on o.id = oi.order_id AND oi.invoice_id = :invoice_id
                     INNER JOIN status os on o.status_id = os.id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['invoice_id' => $invoiceId]);

        return $stmt->fetchAll();
    }

    public function getByProspectContacts(int $limit = 10): array
    {
      $sql = "
        SELECT
            ord.id               AS order_id,
            ord.client_id        AS client_id,
            pec.name             AS client_name,
            (
                SELECT email FROM email WHERE email.people_id = ord.client_id LIMIT 1
            )                    AS client_email1,
            (
                SELECT CONCAT(ddd,phone) FROM phone WHERE phone.people_id = ord.client_id LIMIT 1
            )                    AS client_phone1,
            (
                SELECT email FROM email WHERE email.people_id = pem.employee_id LIMIT 1
            )                    AS client_email2,
            (
                SELECT CONCAT(ddd,phone) FROM phone WHERE phone.people_id = pem.employee_id LIMIT 1
            )                    AS client_phone2,
            pep.id               AS provider_id,
            pep.name             AS provider_name,
            pec.people_type      AS client_type,
            (
                SELECT state.UF
                FROM quote
                	INNER JOIN city  ON city.id  = quote.city_origin_id
                	INNER JOIN state ON state.id = city.state_id
                WHERE quote.order_id = ord.id
                LIMIT 1
            )                    AS uf_origin
        FROM orders ord
        	INNER JOIN people pec ON
            	pec.id = ord.client_id
        	INNER JOIN people pep ON
            	pep.id = ord.provider_id
            LEFT JOIN people_employee pem ON
            	pem.company_id = ord.client_id
        WHERE
          ord.notified            = 0
          AND ord.status_id = 1
          AND ord.client_id      IN (
            SELECT
              ord2.client_id AS id
            FROM orders ord2
            WHERE
              ord2.client_id       IS NOT null
              AND ord2.provider_id IS NOT null
            GROUP BY
              ord2.client_id
            HAVING
              COUNT(ord2.client_id) = 1
          )
        HAVING
        	(
            (client_email1 IS NOT null AND client_phone1 IS NOT null)
            OR
            (client_email2 IS NOT null AND client_phone2 IS NOT null)
          )
        LIMIT :limit
      ";

      $rsm = new ResultSetMapping();

      $rsm->addScalarResult('order_id'     , 'order_id'     );
      $rsm->addScalarResult('client_id'    , 'client_id'    );
      $rsm->addScalarResult('client_name'  , 'client_name'  );
      $rsm->addScalarResult('client_email1', 'client_email1');
      $rsm->addScalarResult('client_phone1', 'client_phone1');
      $rsm->addScalarResult('client_email2', 'client_email2');
      $rsm->addScalarResult('client_phone2', 'client_phone2');
      $rsm->addScalarResult('provider_id'  , 'provider_id'  );
      $rsm->addScalarResult('provider_name', 'provider_name');
      $rsm->addScalarResult('client_type'  , 'client_type'  );
      $rsm->addScalarResult('uf_origin'    , 'uf_origin'    );

      $nqu = $this->getEntityManager()->createNativeQuery($sql, $rsm);

      $nqu->setParameter('limit', $limit);

      $res = $nqu->getArrayResult();
      if (empty($res))
        return [];

      $out = [];

      foreach ($res as $order) {
        $email = $order['client_email1'];
        $phone = $order['client_phone1'];

        if ($order['client_email2'] !== null && $order['client_phone2'] !== null) {
          $email = $order['client_email2'];
          $phone = $order['client_phone2'];
        }

        $out[] = [
          'order'    => $order['order_id'],
          'ufOrigin' => $order['uf_origin'],
          'client'   => [
            'email' => $email,
            'name'  => $order['client_name'],
            'phone' => $phone,
          ],
          'provider' => [
            'id'   => $order['provider_id'  ],
            'name' => $order['provider_name'],
          ],
        ];
      }

      return $out;
    }
}
