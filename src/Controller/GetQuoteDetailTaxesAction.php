<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Quotation;
use App\Entity\QuoteDetail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Library\Quote\View\Group   as TaxesView;
use App\Library\Quote\Core\DataBag as TaxesData;

class GetQuoteDetailTaxesAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  public function __construct(EntityManagerInterface $manager)
  {
    $this->manager = $manager;
  }

  public function __invoke(Quotation $data): JsonResponse
  {
    try {
      $response = $this->manager->getRepository(QuoteDetail::class)->findBy(['quote' => $data]);
      $result['total'] = $response[0]->getQuote()->getTotal();
      foreach ($response as $detail) {
        //if (strpos($detail->getTaxName(), 'TRECHO')) {
          $result['taxes'][] = [
            'id'               => $detail->getId(),
            'delivery_tax_id'  => $detail->getDeliveryTax() ? $detail->getDeliveryTax()->getId() : null,
            'name'             => $detail->getTaxName(),
            'description'      => $detail->getTaxDescription(),
            'type'             => $detail->getTaxType(),
            'subType'          => $detail->getTaxSubtype(),
            'price'            => $detail->getPrice(),
            'weight'           => $detail->getFinalWeight(),
            'total'            => $detail->getPriceCalculated(),
            'minimumPrice'     => $detail->getminimumPrice()

          ];
        
      }

      return new JsonResponse([
        'response' => [
          'data'    => $result,
          'count'   => is_array($result) ? count($result['taxes']) : 0,
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }
}
