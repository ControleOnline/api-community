<?php

namespace App\Controller;



use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;



class SkyhubShippingQuoteAction
{

    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * @var \ControleOnline\Repository\InvoiceRepository
     */
    private $repository = null;



    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->manager    = $entityManager;
        $this->security   = $security;
        $this->repository = $this->manager->getRepository(\ControleOnline\Entity\Invoice::class);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;

        //$results = $this->getResults($this->getFilters());

        /* Requisição
        {
            "destinationZip": 22041001,
            "volumes": [
              {
                "sku": "SKU_PARCEIRO_1",
                "quantity": 2,
                "price": 15.20,
                "height": 0.55,
                "length": 0.63,
                "width": 0.21,
                "weight": 1.00
              },
              {
                "sku": "SKU_PARCEIRO_2",
                "quantity": 1,
                "price": 53.99,
                "height": 0.3,
                "length": 0.2,
                "width": 0.1,
                "weight": 1.75
              }
            ]
          }
          */


        
            $shippingQuotes[] =
            [
                "shippingCost"=> 1020.0,
                "deliveryTime"=> [
                  "total"=> 10,
                  "transit"=> 8,
                  "expedition"=> 2
                ],
                "shippingMethodId"=> "8-Correios",
                "shippingMethodName"=> "Sedex",
                "shippingMethodDisplayName"=> "Sedex"
              ];            

        return new JsonResponse([
            "shippingQuotes"=>$shippingQuotes
        ]);
    }
}
