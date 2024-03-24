<?php

namespace App\Controller;

use ControleOnline\Entity\Quotation;
use ControleOnline\Entity\User;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use ControleOnline\Entity\PurchasingOrder AS Order;
use ControleOnline\Entity\People;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PrestaShopAction
 * @package App\Controller
 * @Route("/presta-shop")
 */
class PrestaShopAction extends AbstractController
{
    public $em;

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/order/paid")
     */
    public function orderPaid(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->query->get('api-key');
            $req = json_decode($request->getContent(), true);

            if (!$apiKey || !$req) {
                return $this->json(null);
            }

            $this->em = $this->getDoctrine()->getManager();

            $User = $this->em->getRepository(User::class)->findOneBy(['apiKey' => $apiKey]);
            $People = $User->getPeople();

            if (($PeopleLink = $People->getPeopleCompany()->first()) !== false) {
                if ($PeopleLink->getCompany() instanceof People)
                    $People = $PeopleLink->getCompany();
            }

            $Quotation = $this->em->getRepository(Quotation::class)->findOneBy(['id' => $req['quote']]);

            $Order = $this->em->getRepository(Order::class)->findOneBy(['id' => $Quotation->getOrder()->getId()]);

            $ChooseQuoteAction = new ChooseQuoteAction($this->em);

            $req['payer'] = $People->getId();
            $req['retrieve']['id'] = $People->getId();
            $req['retrieve']['contact'] = $People->getId();
            $req['comments'] = "";

            $ChooseQuoteAction->updateOrder($Quotation->getOrder(), $req);

            $this->em->flush();
            $this->writeLog("Order:\n" . json_encode($Order));
            return $this->json($Order);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->json($e->getMessage());
        }
    }

    public function writeLog($message, $file = 'webhook.txt')
    {
        $fp = fopen(date('Y-m-d'). '-' . $file, 'a+');
        fwrite($fp, date('d/m/Y H:i') . "\n");
        fwrite($fp, $message . "\n");
        fwrite($fp, "------\n");
        fclose($fp);
    }

    public static function searchOrder(string $storeId, string $orderId, string $hash): ?object
    {
        try {

            $client = new Client(['verify' => false]);
            $response = $client->request('GET', self::$__search_url . $storeId . '/orders/#' . $orderId, [
                'headers' => [
                    'User-Agent' => 'testing/1.0',
                    'Accept' => 'application/json',
                    'X-Foo' => ['Bar', 'Baz'],
                    'Authentication' => 'bearer ' . $hash
                ]
            ]);

            $result = json_decode($response->getBody(), false);

            if (isset($result))
                return $result[0];

        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        return null;
    }
}
