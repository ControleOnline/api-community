<?php

namespace ControleOnline\MessageHandler\iFood;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderProduct;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Product;
use ControleOnline\Entity\Status;
use ControleOnline\Message\iFood\OrderMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class OrderMessageHandler
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private HttpClientInterface $httpClient;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        HttpClientInterface $httpClient
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->params = $params;
        $this->httpClient = $httpClient;
    }

    public function __invoke(OrderMessage $message)
    {
        $event = $message->getEvent();

        // Verificar se é um evento de novo pedido (PLACED)
        if (isset($event['code']) && $event['code'] === 'PLC') {
            $this->processNewOrder($event);
        } else {
            $this->logger->info('Evento ignorado', ['code' => $event['code'] ?? 'unknown']);
        }
    }

    private function processNewOrder(array $event): void
    {
        $orderId = $event['orderId'] ?? null;
        $merchantId = $event['merchantId'] ?? null;

        if (!$orderId || !$merchantId) {
            $this->logger->error('Dados do pedido incompletos', ['event' => $event]);
            return;
        }

        // Evitar duplicatas
        $existingOrder = $this->entityManager->getRepository(Order::class)->findOneBy(['mainOrderId' => $orderId]);
        if ($existingOrder) {
            $this->logger->info('Pedido já processado', ['orderId' => $orderId]);
            return;
        }

        // Buscar detalhes do pedido via API
        $orderDetails = $this->fetchOrderDetails($orderId);
        if (!$orderDetails) {
            $this->logger->error('Não foi possível obter detalhes do pedido', ['orderId' => $orderId]);
            return;
        }

        // Criar ou buscar entidades relacionadas
        $client = $this->createOrGetClient($orderDetails['customer'] ?? []);
        $provider = $this->createOrGetProvider($merchantId);
        $status = $this->entityManager->getRepository(Status::class)->findOneBy(['status' => 'quote']) ?? $this->createStatus();
        $deliveryAddress = $this->createDeliveryAddress($orderDetails['delivery'] ?? []);

        // Criar o pedido
        $order = new Order();
        $order->setMainOrderId($orderId); // Usar mainOrderId para o orderId do iFood
        $order->setClient($client);
        $order->setProvider($provider);
        $order->setStatus($status);
        $order->setAlterDate(new \DateTimeImmutable());
        $order->setApp('iFood');
        $order->setOrderType('delivery');
        $order->setAddressDestination($deliveryAddress);

        // Calcular o preço total do pedido
        $totalPrice = $orderDetails['total']['orderAmount'] ?? 0;
        $order->setPrice($totalPrice);

        // Armazenar informações adicionais (ex.: método de pagamento, taxa de entrega)
        $order->setOtherInformations([
            'iFoodOrderId' => $orderId,
            'merchantId' => $merchantId,
            'payment' => $orderDetails['payments'] ?? [],
            'deliveryFee' => $orderDetails['total']['deliveryFee'] ?? 0,
            'subTotal' => $orderDetails['total']['subTotal'] ?? 0,
        ]);

        // Processar produtos
        if (isset($orderDetails['items'])) {
            foreach ($orderDetails['items'] as $item) {
                $product = $this->createOrGetProduct($item);
                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($product);
                $orderProduct->setQuantity($item['quantity'] ?? 1);
                $orderProduct->setPrice($item['unitPrice'] ?? 0.0);
                $orderProduct->setTotal($item['totalPrice'] ?? 0.0);
                $this->entityManager->persist($orderProduct);

                // Tratar adicionais (options)
                if (isset($item['options'])) {
                    foreach ($item['options'] as $option) {
                        $optionProduct = $this->createOrGetProduct($option);
                        $additionalProduct = new OrderProduct();
                        $additionalProduct->setOrder($order);
                        $additionalProduct->setProduct($optionProduct);
                        $additionalProduct->setQuantity($option['quantity'] ?? 1);
                        $additionalProduct->setPrice($option['unitPrice'] ?? 0.0);
                        $additionalProduct->setTotal($option['totalPrice'] ?? 0.0);
                        $additionalProduct->setOrderProduct($orderProduct); // Relacionar como componente
                        $this->entityManager->persist($additionalProduct);
                    }
                }
            }
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info('Pedido processado com sucesso', ['orderId' => $orderId]);
    }

    private function fetchOrderDetails(string $orderId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://merchant-api.ifood.com.br/order/v1.0/orders/' . $orderId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['IFOOD_TOKEN'],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Erro na API do iFood', ['status' => $response->getStatusCode()]);
                return null;
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao buscar detalhes do pedido', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function createOrGetClient(array $customerData): ?People
    {
        if (empty($customerData['name']) || empty($customerData['phone'])) {
            $this->logger->warning('Dados do cliente incompletos', ['customer' => $customerData]);
            return null;
        }

        $phone = $customerData['phone']['number'] ?? $customerData['phone'];
        $client = $this->entityManager->getRepository(People::class)->findOneBy(['phone' => $phone]);

        if (!$client) {
            $client = new People();
            $client->setName($customerData['name']);
            $client->setPhone($phone);
            $client->setType('Person'); // Ajustar conforme sua lógica
            $this->entityManager->persist($client);
        }

        return $client;
    }

    private function createOrGetProvider(string $merchantId): ?People
    {
        $provider = $this->entityManager->getRepository(People::class)->findOneBy(['externalId' => $merchantId]);

        if (!$provider) {
            $provider = new People();
            $provider->setName('iFood Merchant ' . $merchantId);
            $provider->setExternalId($merchantId);
            $provider->setType('Company');
            $this->entityManager->persist($provider);
        }

        return $provider;
    }

    private function createDeliveryAddress(array $deliveryData): ?Address
    {
        if (empty($deliveryData['address']['streetName']) || empty($deliveryData['address']['city'])) {
            $this->logger->warning('Dados de endereço incompletos', ['delivery' => $deliveryData]);
            return null;
        }

        $address = new Address();
        $address->setStreetName($deliveryData['address']['streetName']);
        $address->setNumber($deliveryData['address']['streetNumber'] ?? '');
        $address->setComplement($deliveryData['address']['complement'] ?? '');
        $address->setPostalCode($deliveryData['address']['postalCode'] ?? '');
        // Assumindo que City, District, e State são entidades relacionadas
        // Você precisará buscar ou criar essas entidades com base em $deliveryData['address']['city']
        $this->entityManager->persist($address);

        return $address;
    }

    private function createOrGetProduct(array $itemData): Product
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['name' => $itemData['name']]);

        if (!$product) {
            $product = new Product();
            $product->setName($itemData['name']);
            $product->setPrice($itemData['unitPrice'] ?? 0.0);
            $product->setType('Food'); // Ajustar conforme sua lógica
            $this->entityManager->persist($product);
        }

        return $product;
    }

    private function createStatus(): Status
    {
        $status = new Status();
        $status->setStatus('quote');
        $this->entityManager->persist($status);
        return $status;
    }
}
