<?php

namespace App\Controller;

use App\Controller\AbstractCustomResourceAction;
use App\Entity\People;

class UpdateCompanyExpenseAction extends AbstractCustomResourceAction
{
  public function index(): ?array
  {
    $expense = $this->payload();
    $where   = ['id' => $expense->getId()];
    $values  = [
      'category_id' => $expense->getCategory()->getId(),
      'provider_id' => $expense->getProvider()->getId(),
      'amount'      => $expense->getAmount(),
      'description' => $expense->getDescription(),
      'duedate'     => $expense->getDuedate()->format('Y-m-d'),
      'payment_day' => $expense->getDuedate()->format('d'),
      'parcels'     => $expense->getParcels(),
    ];

    try {

      $this->manager()->getConnection()->executeQuery('START TRANSACTION', []);

      $queryBuilder = $this->manager()->getConnection()->createQueryBuilder();

      $queryBuilder->update('company_expense');

      foreach ($values as $key => $value) {
        $queryBuilder->set($key, ':' . $key);
        $queryBuilder
          ->setParameter($key, $value);
      }

      if (!empty($where)) {
        foreach ($where as $key => $value) {
          $queryBuilder->andWhere(
            sprintf('%s = :%s', $key, $key)
          );
          $queryBuilder
            ->setParameter($key, $value);
        }
      }

      $queryBuilder->execute();

      $this->manager()->getConnection()->executeQuery('COMMIT', []);

      return [
        'id' => $expense->getId(),
      ];

    } catch (\Exception $e) {
      if ($this->manager()->getConnection()->isTransactionActive()) {
        $this->manager()->getConnection()->executeQuery('ROLLBACK', []);
      }

      throw new \Exception($e->getMessage());
    }
  }
}
