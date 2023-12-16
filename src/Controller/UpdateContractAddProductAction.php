<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Contract;
use ControleOnline\Entity\MyContract;
use ControleOnline\Entity\MyContractProduct;
use ControleOnline\Entity\MyContractProductPayment;
use ControleOnline\Entity\People;
use ControleOnline\Entity\ProductOld AS Product;

class UpdateContractAddProductAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager  = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }

    public function __invoke(MyContract $data, Request $request): JsonResponse
    {
        try {
          $this->manager->getConnection()->beginTransaction();

          $contract = $this->manager->getRepository(Contract::class)->find($data->getId());
          $payload  = json_decode($request->getContent(), true);

          // validate payload

          $this->validateData($data, $payload);

          // validate instances

          $product = $this->manager->getRepository(Product::class)->find($payload['product']);
          if ($product === null) {
            throw new \Exception('Product not found');
          }

          if ($product->getProductType() == 'Registration') {
            $exists = $this->manager->getRepository(MyContractProductPayment::class)
              ->findOneBy([
                'contract' => $data,
                'payer'    => $payload['payer'],
              ]);
            $exists = $exists !== null;

            if ($exists) {
              throw new \Exception('O produto já foi registrado para este pagador');
            }
          }

          // create contract product

          $contractProduct = new MyContractProduct();

          $contractProduct->setContract($data);
          $contractProduct->setProduct ($product);
          $contractProduct->setQuantity($payload['quantity']);
          $contractProduct->setPrice   ($payload['price']);

          $this->manager->persist($contractProduct);

          // create product parcels

          if ($product->getProductType() == 'Registration') {
            if (!isset($payload['payer'])) {
              throw new \Exception('Payer is not defined');
            }

            if (!isset($payload['parcels']) || !is_int($payload['parcels'])) {
              throw new \Exception('Parcels was not defined');
            }
            else {
              if (!($payload['parcels'] > 0)) {
                throw new \Exception('Parcels must be greater than 0');
              }
            }

            $payer = $this->manager->getRepository(People::class)->find($payload['payer']);
            if ($payer === null) {
              throw new \Exception('Payer not found');
            }

            $parcelValue = $contractProduct->getPrice() / $payload['parcels'];

            for ($parcel = 1; $parcel <= $payload['parcels']; $parcel++) {
              $productPayment = new MyContractProductPayment();

              $productPayment->setContract($data);
              $productPayment->setProduct ($contractProduct);
              $productPayment->setPayer   ($payer);
              $productPayment->setAmount  ($parcelValue);
              $productPayment->setSequence($parcel);

              $this->manager->persist($productPayment);
            }
          }

          $this->manager->flush();
          $this->manager->getConnection()->commit();

          return new JsonResponse([
            'response' => [
              'data'    => null,
              'error'   => '',
              'success' => true,
            ],
          ]);
        } catch (\Exception $e) {
          if ($this->manager->getConnection()->isTransactionActive())
              $this->manager->getConnection()->rollBack();

          return new JsonResponse([
            'response' => [
              'data'    => null,
              'error'   => $e->getMessage(),
              'success' => false,
            ],
          ]);
        }
    }

    private function validateData(MyContract $contract, array $data): void
    {
      if (!isset($data['product'])) {
        throw new \Exception('Product is not defined');
      }

      if (!isset($data['quantity']) || !is_int($data['quantity'])) {
        throw new \Exception('Quantity is not defined');
      }
      else {
        if (!($data['quantity'] > 0)) {
          throw new \Exception('Quantity must be greater than 0');
        }
      }

      if (!isset($data['price']) || !is_numeric($data['price'])) {
        throw new \Exception('Price is not defined');
      }
      else {
        if (!($data['price'] > -1)) {
          throw new \Exception('Price must be a positive number');
        }
      }
    }
}
