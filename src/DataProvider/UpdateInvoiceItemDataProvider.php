<?php
namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use App\Resource\UpdateInvoice;
use ControleOnline\Entity\Invoice;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateInvoiceItemDataProvider implements
  ItemDataProviderInterface, RestrictedDataProviderInterface
{
  private $request;

  public function __construct(RequestStack $request, EntityManagerInterface $manager)
  {
    $this->manager = $manager;
    $this->request = $request->getCurrentRequest();
  }

  public function supports(
    string $resourceClass,
    string $operationName = null,
    array  $context = []
  ): bool
  {
    return UpdateInvoice::class === $resourceClass;
  }

  public function getItem(
    string $resourceClass, $id, string $operationName = null, array $context = []
  ): ?UpdateInvoice
  {
    if ($this->manager->find(Invoice::class, $id) === null) {
      return null;
    }

    $payload = json_decode($this->request->getContent());
    if (!isset($payload->company)) {
      return null;
    }

    $invoice = new UpdateInvoice;

    $invoice->id      = $id;
    $invoice->company = $payload->company;

    return $invoice;
  }
}
