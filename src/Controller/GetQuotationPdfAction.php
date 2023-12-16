<?php

namespace App\Controller;

use ControleOnline\Entity\Quotation;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\PeopleDomain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use ApiPlatform\Core\Exception\InvalidValueException;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\Status;


use Dompdf\Dompdf;
use Dompdf\Options;

class GetQuotationPdfAction
{

    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */

    /**
     * Synfony Kernel
     *
     * @var KernelInterface
     */
    private $kernel;

    private $manager = null;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function __invoke(Quotation $data, Request $request)
    {
        $people_domain = $request->query->get('domain');
    
        $people_domain = $people_domain ?: $request->server->get('HTTP_HOST');

        $cleaned_url = strtok($request->getUri(), '?');
    
        return $this->getBody($data, $people_domain, $cleaned_url);
    }

    private function getBody(Quotation $data, $people_domain)
    {
        $config = $this->manager->getRepository(Config::class)->findOneBy([
            'people' => $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $people_domain])->getPeople(),
            'config_key' => 'proposal_body'
        ]);
    
        $body = $config ? $config->getConfigValue() : '';

        $originCity = $data->getCityOrigin();
        $originUf = $originCity->getState();

        $destinationCity = $data->getCityDestination();
        $destinationUf = $destinationCity->getState();

        /**
         * @var SalesOrder $order
         */
        $order = $data->getOrder();


        // id da cotação        
        $body = preg_replace('/\\{\\{\\s+quote_id\\s+\\}\\}/', $data->getId(), $body);

        // id do pedido
        $body = preg_replace('/\\{\\{\\s+id\\s+\\}\\}/', $data->getOrder()->getId(), $body);


        // origem
        $body = preg_replace(
            '/\\{\\{\\s+origem\\s+\\}\\}/',
            $originCity->getCity() . '-' . $originUf->getUf(),
            $body
        );

        // destino
        $body = preg_replace(
            '/\\{\\{\\s+destino\\s+\\}\\}/',
            $destinationCity->getCity() . '-' . $destinationUf->getUf(),
            $body
        );

        // valor total
        $body = preg_replace('/\\{\\{\\s+total\\s+\\}\\}/', $data->getTotal(), $body);

        // data da cotação
        $body = preg_replace(
            '/\\{\\{\\s+data\\s+\\}\\}/',
            $data->getQuoteDate()->format('d/m/Y'),
            $body
        );

        // validade
        $body = preg_replace(
            '/\\{\\{\\s+validade\\s+\\}\\}/',
            date_add($data->getQuoteDate(), date_interval_create_from_date_string('5 days'))->format('d/m/Y'),
            $body
        );

        // carro
        $body = preg_replace(
            '/\\{\\{\\s+carro\\s+\\}\\}/',
            $order->getProductType(),
            $body
        );

        // seguro
        $body = preg_replace(
            '/\\{\\{\\s+seguro\\s+\\}\\}/',
            $order->getInvoiceTotal(),
            $body
        );

        //preço
        $body = preg_replace(
            '/\\{\\{\\s+preco\\s+\\}\\}/',
            number_format($data->getTotal(), 2, ',', '.'),
            $body
        );

        $protocol = 'http://';

        if (isset($_SERVER['HTTPS'])) {
            $protocol = 'https://';
        }

        // api_link
        $body = preg_replace(
            '/\\{\\{\\s+api_link\\s+\\}\\}/',
            $protocol . $_SERVER['HTTP_HOST'],
            $body
        );

        $body = preg_replace(
            '/\\{\\{\\s+app_link\\s+\\}\\}/',
            $protocol . $people_domain,
            $body
        );

        $body = preg_replace("/\r|\n/", "", $body);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($body);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $html = $dompdf->output();

        $order = $data->getOrder();
        $order->setStatus($this->manager->getRepository(Status::class)
            ->findOneBy(array(
                'status' => 'proposal sent'
            )));

        $this->manager->persist($order);
        $this->manager->flush();

        return new JsonResponse([
            'response' => [
                'data'    => array(
                    'pdf' => base64_encode($html),
                    'html' => base64_encode($body)
                ),
                'count'   => 0,
                'error'   => '',
                'success' => true,
            ],
        ]);
    }
}
