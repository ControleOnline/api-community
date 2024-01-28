<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Library\Rates\Jadlog\Client as JadlogClient;
use Symfony\Component\Security\Core\Security;
use \App\Library\Rates\RateServiceFactory;

use ControleOnline\Entity\File as FileEntity;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\Label;

class CreateNewLabel
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * Client
     *
     * @var JadlogClient
     */
    private $client = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->manager     = $entityManager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
        $this->client      = RateServiceFactory::create('Jadlog');
    }

    public function __invoke(Request $request): JsonResponse
    {

        /**
         * @var string $orderId
         */
        $orderId = $request->get('orderId', null);

        if (!empty($orderId)) {

            $repoLabel = $this->manager->getRepository(Label::class);

            /**
             * @var Label $label
             */
            $label = $repoLabel->findOneBy(array('orderId' => $orderId));

            if (empty($label)) {

                $repoOrder = $this->manager->getRepository(Order::class);

                /**
                 * @var Order $order
                 */
                $order = $repoOrder->findOneBy(array("id" => $orderId));

                if (!empty($order)) {

                    $result = $this->client->getOrderTag($order);

                    if (!empty($result) && isset($result['codigo'])) {

                        if (isset($result["shipmentId"])) {
                            $shipmentId = $result["shipmentId"];

                            if (isset($result["etiqueta"])) {

                                $jadLabel = $result["etiqueta"];

                                if (isset($jadLabel['volume'][0])) {
                                    $vol      = $jadLabel['volume'][0];

                                    $newLabel = new Label();

                                    $newLabel->setPeopleId($this->currentUser->getPeople()->getId());
                                    $newLabel->setOrderId($orderId);
                                    $newLabel->setShipmentId($shipmentId);
                                    $newLabel->setCarrierId($order->getQuote()->getCarrier()->getId());
                                    $newLabel->setCodBarra($vol['codbarra']);
                                    $newLabel->setLastMile($vol['lastMile']);
                                    $newLabel->setPosicao($vol['posicao']);
                                    $newLabel->setPrioridade($vol['prioridade']);
                                    $newLabel->setRota($vol['rota']);
                                    $newLabel->setRua($vol['rua']);
                                    $newLabel->setSeqVolume($vol['seqVolume']);
                                    $newLabel->setUnidadeDestino($vol['unidadeDestino']);

                                    $this->manager->getConnection()->beginTransaction();

                                    $this->manager->persist($newLabel);
                                    $this->manager->flush();

                                    $this->manager->getConnection()->commit();

                                    $template = $this->getLabelTemplate($newLabel);

                                    return new JsonResponse([
                                        'response' => [
                                            'data'    => [
                                                "template" => $template
                                            ],
                                            'success' => true,
                                        ]
                                    ], 200);
                                } else {
                                    return $this->error('api jadlog has result but without volume (' . json_encode($result) . ')');
                                }
                            } else {
                                return $this->error('api jadlog has result but without label (' . json_encode($result) . ')');
                            }
                        } else {
                            return $this->error('api jadlog shipmentId result error (' . json_encode($result) . ')');
                        }
                    } else {
                        return $this->error('api jadlog result error (' . json_encode($result) . ')');
                    }
                } else {
                    return $this->error('order not found');
                }
            } else {

                $template = $this->getLabelTemplate($label);

                return new JsonResponse([
                    'response' => [
                        'data'    => [
                            "template" => $template
                        ],
                        'success' => true,
                    ]
                ], 200);
            }
        } else {
            return $this->error('order id is empty');
        }
    }

    private function getRegex(array $variables): array
    {

        $reges  = array();
        $values = array();

        foreach ($variables as $key => $value) {

            $reges[]  = "#\{\{\\s*\\n*\\t*" . $key . "\\t*\\n*\\s*\}\}#";
            $values[] = $value;
        }

        return [
            "regex"  => $reges,
            "values" => $values
        ];
    }

    /**
     * @var Label $label
     */
    public function getLabelTemplate($label)
    {

        $template = '
            <style>
                .container {
                    display: -ms-grid;
                    display: grid;
                    -ms-grid-columns: 1fr 1fr 1fr 1fr 1fr 1fr 1fr;
                    grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr 1fr;
                    -ms-grid-rows: 35px 1fr 1fr 1fr 1fr 1fr 1fr 3fr;
                    grid-template-rows: 35px 1fr 1fr 1fr 1fr 1fr 1fr 3fr;
                    border: solid 3px black;
                    width: 600px;
                    height: 900px;
                    font-family: Arial, Helvetica, sans-serif;
                }

                .container>*:nth-child(1) {
                    -ms-grid-row: 1;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(2) {
                    -ms-grid-row: 1;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(3) {
                    -ms-grid-row: 1;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(4) {
                    -ms-grid-row: 1;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(5) {
                    -ms-grid-row: 1;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(6) {
                    -ms-grid-row: 1;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(7) {
                    -ms-grid-row: 1;
                    -ms-grid-column: 7;
                }

                .container>*:nth-child(8) {
                    -ms-grid-row: 2;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(9) {
                    -ms-grid-row: 2;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(10) {
                    -ms-grid-row: 2;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(11) {
                    -ms-grid-row: 2;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(12) {
                    -ms-grid-row: 2;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(13) {
                    -ms-grid-row: 2;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(14) {
                    -ms-grid-row: 2;
                    -ms-grid-column: 7;
                }

                .container>*:nth-child(15) {
                    -ms-grid-row: 3;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(16) {
                    -ms-grid-row: 3;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(17) {
                    -ms-grid-row: 3;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(18) {
                    -ms-grid-row: 3;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(19) {
                    -ms-grid-row: 3;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(20) {
                    -ms-grid-row: 3;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(21) {
                    -ms-grid-row: 3;
                    -ms-grid-column: 7;
                }

                .container>*:nth-child(22) {
                    -ms-grid-row: 4;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(23) {
                    -ms-grid-row: 4;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(24) {
                    -ms-grid-row: 4;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(25) {
                    -ms-grid-row: 4;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(26) {
                    -ms-grid-row: 4;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(27) {
                    -ms-grid-row: 4;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(28) {
                    -ms-grid-row: 4;
                    -ms-grid-column: 7;
                }

                .container>*:nth-child(29) {
                    -ms-grid-row: 5;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(30) {
                    -ms-grid-row: 5;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(31) {
                    -ms-grid-row: 5;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(32) {
                    -ms-grid-row: 5;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(33) {
                    -ms-grid-row: 5;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(34) {
                    -ms-grid-row: 5;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(35) {
                    -ms-grid-row: 5;
                    -ms-grid-column: 7;
                }

                .container>*:nth-child(36) {
                    -ms-grid-row: 6;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(37) {
                    -ms-grid-row: 6;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(38) {
                    -ms-grid-row: 6;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(39) {
                    -ms-grid-row: 6;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(40) {
                    -ms-grid-row: 6;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(41) {
                    -ms-grid-row: 6;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(42) {
                    -ms-grid-row: 6;
                    -ms-grid-column: 7;
                }

                .container>*:nth-child(43) {
                    -ms-grid-row: 7;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(44) {
                    -ms-grid-row: 7;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(45) {
                    -ms-grid-row: 7;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(46) {
                    -ms-grid-row: 7;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(47) {
                    -ms-grid-row: 7;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(48) {
                    -ms-grid-row: 7;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(49) {
                    -ms-grid-row: 7;
                    -ms-grid-column: 7;
                }

                .container>*:nth-child(50) {
                    -ms-grid-row: 8;
                    -ms-grid-column: 1;
                }

                .container>*:nth-child(51) {
                    -ms-grid-row: 8;
                    -ms-grid-column: 2;
                }

                .container>*:nth-child(52) {
                    -ms-grid-row: 8;
                    -ms-grid-column: 3;
                }

                .container>*:nth-child(53) {
                    -ms-grid-row: 8;
                    -ms-grid-column: 4;
                }

                .container>*:nth-child(54) {
                    -ms-grid-row: 8;
                    -ms-grid-column: 5;
                }

                .container>*:nth-child(55) {
                    -ms-grid-row: 8;
                    -ms-grid-column: 6;
                }

                .container>*:nth-child(56) {
                    -ms-grid-row: 8;
                    -ms-grid-column: 7;
                }

                /*=====conteúdo inter=====*/

                .title {
                    -ms-grid-column: 1;
                    -ms-grid-column-span: 7;
                    grid-column: 1/8;
                    text-align: center;
                    border: solid 3px black;
                    margin: 0;
                    height: 33px;
                    font-size: 13px;
                    font-family: Arial, Helvetica, sans-serif;
                }

                .title h2 {
                    margin-top: 0;
                }

                .destino {
                    -ms-grid-column: 1;
                    -ms-grid-column-span: 5;
                    grid-column: 1/6;
                    -ms-grid-row: 2;
                    -ms-grid-row-span: 2;
                    grid-row: 2/4;
                    border: solid 3px black;
                }

                .warehouse {
                    -ms-grid-column: 6;
                    grid-column: 6/6;
                    -ms-grid-row: 2;
                    -ms-grid-row-span: 3;
                    grid-row: 2/5;
                    border: solid 3px black;
                }

                .warehouse p {
                    font-size: 80px;
                    text-align: center;
                    margin-top: 0;
                    margin-bottom: 0;
                }

                .shipment-id {
                    -ms-grid-column: 7;
                    -ms-grid-column-span: 1;
                    grid-column: 7/8;
                    -ms-grid-row: 2;
                    -ms-grid-row-span: 6;
                    grid-row: 2/8;
                    border: solid 3px black;
                }

                .shipment-id img {
                    width: 100%;
                    margin-top: 100px;
                }

                .informacao-contato {
                    -ms-grid-column: 1;
                    -ms-grid-column-span: 4;
                    grid-column: 1/5;
                    -ms-grid-row: 4;
                    -ms-grid-row-span: 2;
                    grid-row: 4/6;
                    border: solid 3px black;
                }

                .quantidade {
                    -ms-grid-column: 5;
                    -ms-grid-column-span: 1;
                    grid-column: 5/6;
                    -ms-grid-row: 4;
                    -ms-grid-row-span: 2;
                    grid-row: 4/6;
                    border: solid 3px black;
                }

                .unidade {
                    -ms-grid-column: 1;
                    -ms-grid-column-span: 4;
                    grid-column: 1/5;
                    -ms-grid-row: 6;
                    -ms-grid-row-span: 2;
                    grid-row: 6/8;
                    border: solid 3px black;
                }

                .prioridade {
                    -ms-grid-column: 5;
                    -ms-grid-column-span: 1;
                    grid-column: 5/6;
                    -ms-grid-row: 6;
                    -ms-grid-row-span: 2;
                    grid-row: 6/8;
                    border: solid 3px black;
                }

                .prioridade p {
                    font-size: 90px;
                    margin: 0;
                    text-align: center;
                    margin-top: 50%;
                }

                .origem {
                    -ms-grid-column: 6;
                    -ms-grid-column-span: 1;
                    grid-column: 6/7;
                    -ms-grid-row: 5;
                    -ms-grid-row-span: 3;
                    grid-row: 5/8;
                    border: solid 3px black;
                    width: 110px;
                }

                .origem p {
                    -ms-grid-column: 6;
                    -ms-grid-column-span: 1;
                    grid-column: 6/7;
                    -ms-grid-row: 5;
                    -ms-grid-row-span: 3;
                    grid-row: 5/8;
                    border: solid 3px black;
                }

                .origem p {
                    -webkit-transform: rotate(90deg);
                    -ms-transform: rotate(90deg);
                    transform: rotate(90deg);
                    /* Equal to rotateZ(45deg) */
                    border: none;
                    margin-top: 100px;
                    width: 250px;
                    margin-left: -70%;
                }

                /*=====conteudo dentro da ultima linha =====*/
                .adicionais {
                    -ms-grid-column: 1;
                    -ms-grid-column-span: 7;
                    grid-column: 1/8;
                    -ms-grid-row: 8;
                    -ms-grid-row-span: 1;
                    grid-row: 8/9;
                    border: solid 3px black;
                }

                .adicionais img {
                    width: 100%;
                    margin-bottom: 0;
                }

                .adicionais p {
                    margin: 0;
                }

                .adicionais .numero-shipment {
                    text-align: left;
                    font-size: 18px;
                    margin: 0;
                    font-family: Arial, Helvetica, sans-serif;
                }

                .adicionais .prioridade-E {
                    text-align: left;
                    font-size: 18px;
                    margin: 0;
                    font-family: Arial, Helvetica, sans-serif;
                }

                .adicionais .rota {
                    text-align: center;
                    font-size: 20px;
                    margin: 0;
                    font-family: Arial, Helvetica, sans-serif;
                }

                .adicionais .cidade {
                    text-align: right;
                    margin-right: 15px;
                }

                .barcode,
                .barcode-2 {
                    text-align: center;
                }

                .barcode>div,
                .barcode-2>div {
                    margin: 0 auto !important;
                }

                .barcode-2 {
                    -webkit-transform: rotate(90deg);
                    -ms-transform: rotate(90deg);
                    transform: rotate(90deg);
                    margin-top: 100%;
                    width: 90px;
                }
            </style>

            <div class="container">

                <div class="title">
                    <h7>JADLOG - DPD GROUP</h7>
                </div>
                <div class="destino">
                    <p>{{ nome_destinatario }}<br>
                        {{ endereco_destinatario }}
                    </p>
                </div>

                <div class="warehouse">
                    <p>{{ rua_warehouse }}</p><br>
                    <p>{{ posicao_warehouse }}</p>
                </div>

                <div class="shipment-id">
                    <div class="barcode-2">
                        {{ codigo_shipment }}
                    </div>
                </div>

                <div class="informacao-contato">
                    <p>{{ contato_nome }}<br>
                        {{ contato_telefone }}<br>
                        {{ contato_info }}
                    </p>
                </div>

                <div class="quantidade">
                    <p>Packages<br>
                        001/001<br>
                        Weight<br>
                        {{ volume_peso }}
                    </p>
                </div>

                <div class="unidade">
                    <p>{{ unidade_destino_nome }}<br>
                        {{ unidade_destino_codigo }}
                    </p>
                </div>

                <div class="prioridade">
                    <p>{{ numero_prioridade }}</p>
                </div>

                <div class="origem">
                    <p>{{ nome_origem }}<br>
                        {{ endereco_origem }}
                    </p>
                </div>

                <div class="adicionais">
                    <p class="numero-shipment">{{ shipment_id }}</p>
                    <p class="track">track</p>
                    <p class="prioridade-E">{{ numero_prioridade }}</p>
                    <p class="sort">{{ sort_type }}</p>
                    <p class="rota">{{ rota }}</p>
                    <p class="cidade">{{ rota_lastmille }}</p>
                    <div class="barcode">
                        {{ codigo_remessa }}
                        <p>' . $label->getCodBarra() . '</p>
                    </div>
                </div>
            </div>
        ';

        $vars = [
            "nome_destinatario"      => "[NO DATA]",
            "endereco_destinatario"  => "[NO DATA]",
            "rua_warehouse"          => "[NO DATA]",
            "posicao_warehouse"      => "[NO DATA]",
            "contato_nome"           => "[NO DATA]",
            "contato_telefone"       => "[NO DATA]",
            "contato_info"           => "[NO DATA]",
            "volume_peso"            => "[NO DATA]",
            "unidade_destino_nome"   => "[NO DATA]",
            "unidade_destino_codigo" => "[NO DATA]",
            "numero_prioridade"      => "[NO DATA]",
            "nome_origem"            => "[NO DATA]",
            "endereco_origem"        => "[NO DATA]",
            "shipment_id"            => "[NO DATA]",
            "sort_type"              => "[NO DATA]",
            "rota"                   => "[NO DATA]",
            "rota_lastmille"         => "[NO DATA]",
            "codigo_shipment"        => '[NO DATA]',
            "codigo_remessa"         => '[NO DATA]'
        ];


        $repoOrder = $this->manager->getRepository(Order::class);

        /**
         * @var Order $order
         */
        $order = $repoOrder->findOneBy(array("id" => $label->getOrderId()));

        if (!empty($order)) {
            $rem = $this->client->getOrderPeopleData(
                $order->getRetrievePeople(),
                $order->getRetrieveContact(),
                $order->getAddressOrigin()
            );

            $del = $this->client->getOrderPeopleData(
                $order->getDeliveryPeople(),
                $order->getDeliveryContact(),
                $order->getAddressDestination()
            );

            $vars["nome_destinatario"] = $del["people"]->getFullName();

            $vars["endereco_destinatario"] =
                $del["street"]->getStreet() . ', ' . $del["address"]->getNumber() . '<br/>' .
                $del["street"]->getDistrict()->getDistrict() . '<br/>' .
                $del["cep"] . ' ' . $del["city"]->getCity() . ' ' . $del["city"]->getState()->getUf();

            $vars["rua_warehouse"]     = $label->getRua();
            $vars["posicao_warehouse"] = $label->getPosicao();

            $vars["contato_nome"]     = $del["contact"]->getFullName();
            $vars["contato_telefone"] = $del["phone"];
            $vars["contato_info"]     = $del["email"];

            $vars["volume_peso"] = $order->getCubage();

            $vars["unidade_destino_nome"]   = $label->getUnidadeDestino();
            $vars["unidade_destino_codigo"] = '';
            $vars["numero_prioridade"]      = $label->getPrioridade();

            $vars["nome_origem"] = $rem["people"]->getFullName();

            $vars["endereco_origem"] =
                $rem["street"]->getStreet() . ', ' . $rem["address"]->getNumber() . '<br/>' .
                $rem["street"]->getDistrict()->getDistrict() . '<br/>' .
                $rem["cep"] . ' ' . $rem["city"]->getCity() . ' ' . $rem["city"]->getState()->getUf();

            $vars["shipment_id"]    = $label->getShipmentId();
            $vars["sort_type"]      = "";
            $vars["rota"]           = $label->getRota();
            $vars["rota_lastmille"] = $label->getLastMile();

            $barcode = new \Com\Tecnick\Barcode\Barcode();

            $barcodeShipment = $barcode->getBarcodeObj(
                "C128A",
                $label->getShipmentId(),
                -1,
                70
            )->setBackgroundColor('white');

            $barcodeRemessa = $barcode->getBarcodeObj(
                "C128A",
                $label->getCodBarra(),
                -1,
                80
            )->setBackgroundColor('white');

            $vars["codigo_shipment"] = $barcodeShipment->getHtmlDiv();
            $vars["codigo_remessa"]  = $barcodeRemessa->getHtmlDiv();
        }

        $reges = $this->getRegex($vars);

        return preg_replace($reges["regex"], $reges["values"], $template);
    }

    public function error(?string $message, ?\Exception $e = null)
    {

        if ($this->manager->getConnection()->isTransactionActive())
            $this->manager->getConnection()->rollBack();

        return new JsonResponse([
            'response' => [
                'data'    => [],
                'count'   => 0,
                'error'   => !empty($message) ? $message : $e->getMessage(),
                'success' => false,
            ],
        ]);
    }
}
