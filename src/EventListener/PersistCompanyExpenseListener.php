<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use ControleOnline\Entity\CompanyExpense;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\Status;

use ControleOnline\Entity\People;
use ControleOnline\Entity\ReceiveInvoice;
use ControleOnline\Entity\SalesOrderInvoice;

class PersistCompanyExpenseListener
{
  public function prePersist(CompanyExpense $companyExpense, LifecycleEventArgs $args)
  {
    $manager  = $args->getObjectManager();
    $ostatus  = $manager->getRepository(Status::class  )->findOneBy(['status' => 'delivered']);
    $istatus  = $manager->getRepository(Status::class)->findOneBy(['status' => ['open']]);
    $provider = $manager->getRepository(People::class)->find($companyExpense->getProvider()->getId());

    // create order

    ($order = new SalesOrder())
      ->setStatus  ($ostatus)
      ->setClient  ($companyExpense->getCompany ())
      ->setProvider($provider)
      ->setPayer   ($companyExpense->getCompany ())
      ->setPrice   ($companyExpense->getAmount  ())
    ;

    $manager->persist($order);

    // create first payment

    $firstInvoice = new ReceiveInvoice();
    $firstInvoice->setPrice   ($companyExpense->getAmount());
    $firstInvoice->setDueDate ($companyExpense->getDuedate());
    $firstInvoice->setStatus  ($istatus);
    $firstInvoice->setNotified(false);

    $manager->persist($firstInvoice);

    $orderInvoice = new SalesOrderInvoice();
    $orderInvoice->setInvoice($firstInvoice);
    $orderInvoice->setOrder  ($order);

    $manager->persist($orderInvoice);

    if ($companyExpense->isParceled()) {

      // calculate value of every parcel

      $amount = $companyExpense->getAmount() / $companyExpense->getParcels();

      // update first invoice

      $manager->persist(
        $firstInvoice->setPrice($amount)
      );

      // calculate initial duedate

      $duedate = (clone $companyExpense->getDuedate())->modify('+1 month');

      // create invoices

      for ($p = 2; $p <= $companyExpense->getParcels(); $p++) {
        $invoice = new ReceiveInvoice();
        $invoice->setPrice   ($amount);
        $invoice->setDueDate ((clone $duedate));
        $invoice->setStatus  ($istatus);
        $invoice->setNotified(false);

        $manager->persist($invoice);

        $orderInvoice = new SalesOrderInvoice();
        $orderInvoice->setInvoice($invoice);
        $orderInvoice->setOrder  ($order);

        $manager->persist($orderInvoice);

        // get next duedate

        $duedate = $duedate->modify('+1 month');
      }
    }

    $companyExpense->setOrder($order);

    /*
     * Provider entity link to document error in people entity.
     * Do not remove the next lines!
     */
    // >>>
    if (($document = $companyExpense->getProvider()->getDocument()) !== null) {
      $document
        ->setPeople(
          $manager->getRepository(People::class)
            ->find($companyExpense->getProvider()->getId())
        );
    }
    // <<<
  }
}
