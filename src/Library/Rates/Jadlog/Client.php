<?php

namespace App\Library\Rates\Jadlog;

use GuzzleHttp\Client as GuzzClient;

use App\Library\Rates\Exception\ClientRequestException;
use App\Library\Rates\CarrierRatesInterface;
use App\Library\Rates\Model\User;
use App\Library\Rates\Model\Quotation;
use App\Library\Rates\Model\Rate;
use ControleOnline\Entity\SalesOrderInvoiceTax;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\Address;

class Client implements CarrierRatesInterface
{

  private $user          = null;
  private $defaultApiUrl = 'https://www.jadlog.com.br/embarcador/api/';
  private $defaultToken  = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOjcxMTIwLCJkdCI6IjIwMjEwNDA5In0.VkMP76uPQAP33mwSLDiqswZvWfkDZRpvSo9mUhv8iCw';

  public function setUser(User $user)
  {
    $this->user = $user;
  }

  /**
   *
   * @return int
   */
  public function getRates(Quotation $quotation): array
  {

    try {

      if ($this->user === null) {
        throw new \Exception('Public access denied');
      }

      $payload = [
        'cepori'      => $quotation->getOrigin(),
        'cepdes'      => $quotation->getDestination(),
        'frap'        => null,
        'peso'        => $quotation->getTotalWeight(),
        'cnpj'        => null,
        'conta'       => '005487',
        'contrato'    => '258',
        'modalidade'  => 3,
        'tpentrega'   => 'D',
        'tpseguro'    => 'S',
        'vldeclarado' => $quotation->getTotalPrice(),
        'vlcoleta'    => null,
      ];

      $options = [
        'json' => [
          'frete' => [$payload]
        ],
        'headers' => [
          'Content-Type' => 'application/json; charset=utf-8',
          'Authorization' => "Bearer " . $this->defaultToken
        ]
        
      ];

      $guzz = new GuzzClient();

      $response = $guzz->request('POST', $this->defaultApiUrl . 'frete/valor', $options);

      if ($response->getStatusCode() === 200) {
        
        $result = json_decode($response->getBody(), true);

        if (empty($result)) {
          return [];
        }

        $rates  = [];
        $number = 1;

        if (isset($result['frete'])) {
          $result = $result['frete'];
        }
        else if (!is_array($result) || count($result) === 0) {
          return [];
        }

        foreach ($result as $rate) {
          $rates[] = (new Rate)
            ->setCarrier ('Jadlog')
            ->setTable   ('Fracionada')
            ->setCode    (null)
            ->setPrice   ($rate['vltotal'])
            ->setNumber  ($number)
            ->setDeadline($rate['prazo'])
            ->setError   (
              isset($rate['error']) ? $rate['error']->descricao : ''
            )
          ;

          $number++;
        }

        return $rates;
      }

      throw new \Exception(
        sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
      );

    } catch (\Exception $e) {

      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        if (isset($contents->erro) && isset($contents->erro->descricao)) {
          throw new ClientRequestException($contents->erro->descricao);
        }
      }

      throw new ClientRequestException($e->getMessage());
    }
  }

  public function getOrderPeopleData(People $retrievePeople, People $retrieveContact, Address $address) {
    /**
     * @var People $rem
     */
    $rem       = $retrievePeople;
    $remCon    = $retrieveContact;
    $remAdd    = $address;
    $remStreet = $remAdd->getStreet();
    $remCity   = $remStreet->getDistrict()->getCity();
    $remCpf    = null;
    $remCnpj   = null;
    $remCep    = null;

    if ($remStreet) {
      $remCep = (string) $remStreet->getCep()->getCep();
      
      if (strlen($remCep) < 8) {
        $remCep = '0' . $remCep;
      }
    }

    $remDocs   = $rem->getDocument();

    $remEmail = $remCon->getOneEmail();

    if (!empty($remEmail)) {
      $remEmail = $remEmail->getEmail();
    }

    $remPhones = $remCon->getPhone();
    $remPhone = null;

    if (!empty($remPhones) && !empty($remPhones[0])) {
      $remPhone = $remPhones[0];
      $remPhone = '(' . $remPhone->getDdd() . ') ' . $remPhone->getPhone();
    }

    if (!empty($remDocs)) {
      /**
       * @var Document $doc
       */
      foreach($remDocs as $doc) {
        if ($doc->getDocumentType()->getDocumentType() === "CPF") {
          $remCpf = $doc->getDocument();
        }
        else if ($doc->getDocumentType()->getDocumentType() === "CNPJ") {
          $remCnpj = $doc->getDocument();
        }
      }
    }

    return [
      "people"  => $rem,
      "contact" => $remCon,
      "address" => $remAdd,
      "street"  => $remStreet,
      "city"    => $remCity,
      "cep"     => $remCep,
      "email"   => $remEmail,
      "phone"   => $remPhone,
      "cpf"     => $remCpf,
      "cnpj"    => $remCnpj
    ];
  }

  public function getOrderTag(SalesOrder $order) {
    try {
      $rem = $this->getOrderPeopleData(
        $order->getRetrievePeople(),
        $order->getRetrieveContact(),
        $order->getAddressOrigin()
      );

      $del = $this->getOrderPeopleData(
        $order->getDeliveryPeople(),
        $order->getDeliveryContact(),
        $order->getAddressDestination()
      );

      $docs = $order->getInvoiceTax();

      /**
       * @var SalesOrderInvoiceTax $doc
       */
      $orderNfe = null;

      if (!empty($docs)) {
        /**
         * @var SalesOrderInvoiceTax $doc
         */
        foreach($docs as $doc) {
          if ($doc->getInvoiceType() === 55) {
            $orderNfe = $doc;
            break;
          }
        }
      }

      if (!empty($orderNfe)) {
        $nfe = $orderNfe->getInvoiceTax();

        $xml = @simplexml_load_string($nfe->getInvoice());

        $nfeObj = $xml->NFe;

        if (empty($nfeObj) || isset($nfeObj->infNFe)) {
          $nfeObj = $nfeObj->infNFe;
        }

        $cfop     = (string) $nfeObj->det->prod->CFOP;
        $serie    = (string) $nfeObj->ide->serie;
        $danfeCte = (string) $nfeObj->attributes()->Id;

        $payload = [
          "conteudo"      => $order->getProductType(),
          'pedido'        => array((string) $order->getId()),
          'totPeso'       => $order->getCubage(),
          "totValor"      => $order->getPrice(),
          "obs"           => !empty($order->getComments()) && gettype($order->getComments()) === "string" ? $order->getComments() : null,
          'modalidade'    => 3,
          'contaCorrente' => '024515',
          "tpColeta"      => "K",
          "tipoFrete"     => 0,
          "cdUnidadeOri"  => "1344",
          "cdUnidadeDes"  => null,
          "cdPickupOri"   => null,
          "cdPickupDes"   => null,
          "servico"       => 0,
          "shipmentId"    => null,
          "vlColeta"      => null,
          "rem"           => array(
            "nome"     => $rem['people']->getFullName(),
            "cnpjCpf"  => !empty($rem['cpf']) ? $rem['cpf'] : $rem['cnpj'],
            "ie"       => "",
            "endereco" => $rem["street"]->getStreet(),
            "numero"   => $rem["address"]->getNumber(),
            "compl"    => $rem["address"]->getComplement(),
            "bairro"   => $rem["street"]->getDistrict()->getDistrict(),
            "cidade"   => $rem["city"]->getCity(),
            "uf"       => $rem["city"]->getState()->getUf(),
            "cep"      => $rem["cep"],
            "fone"     => $rem["phone"],
            "cel"      => null,
            "email"    => $rem["email"],
            "contato"  => $rem["contact"]->getFullName()
          ),
          "des"           => array(
            "nome"     => $del['people']->getFullName(),
            "cnpjCpf"  => !empty($del['cpf']) ? $del['cpf'] : $del['cnpj'],
            "ie"       => "",
            "endereco" => $del["street"]->getStreet(),
            "numero"   => $del["address"]->getNumber(),
            "compl"    => $del["address"]->getComplement(),
            "bairro"   => $del["street"]->getDistrict()->getDistrict(),
            "cidade"   => $del["city"]->getCity(),
            "uf"       => $del["city"]->getState()->getUf(),
            "cep"      => $del["cep"],
            "fone"     => $del["phone"],
            "cel"      => null,
            "email"    => $del["email"],
            "contato"  => $del["contact"]->getFullName()
          ),
          "dfe"           => array([
            "cfop"        => $cfop,
            "danfeCte"    => str_replace('NFe', '', $danfeCte),
            "nrDoc"       => $nfe->getInvoiceNumber(),
            "serie"       => $serie,
            "tpDocumento" => 2,
            "valor"       => $order->getPrice()
          ]),
          "volume"        => array([
            "altura"        => 10,
            "comprimento"   => 10,
            "identificador" => $order->getProductType(),
            "largura"       => 10,
            "peso"          => $order->getCubage()
          ])
        ];

        $options = [
          'json' => $payload,
          'headers' => [
            'Content-Type' => 'application/json; charset=utf-8',
            'Authorization' => "Bearer " . $this->defaultToken
          ]
        ];

        $guzz = new GuzzClient();

        $response = $guzz->request('POST', $this->defaultApiUrl . 'pedido/incluir', $options);
        
        if ($response->getStatusCode() === 200) {
          
          $result = json_decode($response->getBody(), true);

          if (empty($result)) {
            return [];
          }

          return $result;
        }

        throw new \Exception(
          sprintf('%s (%s): client request error', __FUNCTION__, $response->getStatusCode())
        );
      }
      else {
        throw new \Exception(
          sprintf('%s (%s): client request error', __FUNCTION__, "nfe not found")
        );
      }

    } catch (\Exception $e) {

      if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
        $response = $e->getResponse();

        $contents = $response->getBody() !== null ? json_decode($response->getBody()->getContents()) : null;

        if (isset($contents->erro) && isset($contents->erro->descricao)) {
          throw new ClientRequestException($contents->erro->descricao);
        }
      }

      throw new ClientRequestException($e->getMessage());
    }
  }
}
