<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\SalesOrder as Order;
use ControleOnline\Entity\SalesInvoiceTax;
use ControleOnline\Entity\SalesOrderInvoiceTax;
use ControleOnline\Entity\Config;
use NFePHP\CTe\Make;
use NFePHP\CTe\Tools;
use NFePHP\CTe\Common\Standardize;
use NFePHP\Common\Certificate;
use Symfony\Component\HttpKernel\KernelInterface;

class CreateDacteAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;
    private $tools;
    private $appKernel;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $appKernel)
    {
        $this->manager = $entityManager;
        $this->appKernel = $appKernel;
    }



    public function __invoke(Order $data, Request $request): JsonResponse
    {
        try {

            //tanto o config.json como o certificado.pfx podem estar
            //armazenados em uma base de dados, então não é necessário
            ///trabalhar com arquivos, este script abaixo serve apenas como
            //exemplo durante a fase de desenvolvimento e testes.

            $provider = $data->getProvider();
            $document = $provider->getOneDocument();

            $dacteKey = $this->manager->getRepository(Config::class)->findOneBy([
                'people'  => $provider,
                'config_key' => 'dacte-key'
            ]);

            $dacteKeyPass = $this->manager->getRepository(Config::class)->findOneBy([
                'people'  => $provider,
                'config_key' => 'dacte-key-pass'
            ]);
            if (!$dacteKey || !$dacteKeyPass)
                throw new \Exception("DACTE key cert is required", 1);



            $certPath = $this->appKernel->getProjectDir() . $dacteKey->getConfigValue();
            if (!is_file($certPath))
                throw new \Exception("DACTE key cert path is invalid", 1);


            /**
             * @var \ControleOnline\Entity\Address $providerAddress
             */
            $providerAddress = $provider->getAddress()[0];


            //carrega o conteudo do certificado.
            $content = file_get_contents($certPath);
            $arr = [
                "atualizacao" => date('Y-m-d H:m:i'),
                "tpAmb" => 2, //2 - Homologação / 1 - Produção
                "razaosocial" => $provider->getName(),
                "cnpj" => $document->getDocument(),
                //"cpf" => "00000000000",
                "siglaUF" => $providerAddress->getStreet()->getDistrict()->getCity()->getState()->getUf(),
                "schemes" => "PL_CTe_300",
                "versao" => '3.00',
                "proxyConf" => [
                    "proxyIp" => "",
                    "proxyPort" => "",
                    "proxyUser" => "",
                    "proxyPass" => ""
                ]
            ];
            //monta o config.json
            $configJson = json_encode($arr);


            //intancia a classe tools
            $this->tools = new Tools($configJson, Certificate::readPfx($content, $dacteKeyPass->getConfigValue()));

            $this->tools->model('57');

            $cte = new Make();

            //$dhEmi = date("Y-m-d\TH:i:s-03:00"); Para obter a data com diferença de fuso usar 'P'
            $dhEmi = date("Y-m-d\TH:i:sP");

            $numeroCTE = $this->getLastDacte();

            // CUIDADO: Observe que mesmo os parâmetros fixados abaixo devem ser preenchidos conforme os dados do CT-e, estude a composição da CHAVE para saber o que vai em cada campo
            $chave = $this->montaChave(
                '43',
                date('y', strtotime($dhEmi)),
                date('m', strtotime($dhEmi)),
                $arr['cnpj'],
                $this->tools->model(),
                '1',
                $numeroCTE,
                '1',
                '10'
            );

            $infCte = new \stdClass();
            $infCte->Id = "";
            $infCte->versao = "3.00";
            $cte->taginfCTe($infCte);

            $cDV = substr($chave, -1);      //Digito Verificador


            /**
             * @todo
             */
            $ide = new \stdClass();
            $ide->cUF = '43'; // Codigo da UF da tabela do IBGE
            $ide->cCT = '99999999'; // Codigo numerico que compoe a chave de acesso
            $ide->CFOP = '6932'; // Codigo fiscal de operacoes e prestacoes
            $ide->natOp = 'PRESTACAO DE SERVICO DE TRANSPORTE A ESTABELECIMENTO FORA DO ESTADO DE ORIGEM'; // Natureza da operacao

            /**
             * @todo
             */

            //$ide->forPag = '';              // 0-Pago; 1-A pagar; 2-Outros
            $ide->mod = '57'; // Modelo do documento fiscal: 57 para identificação do CT-e
            $ide->serie = '1'; // Serie do CTe
            $ide->nCT = $numeroCTE; // Numero do CTe
            $ide->dhEmi = $dhEmi; // Data e hora de emissão do CT-e: Formato AAAA-MM-DDTHH:MM:DD
            $ide->tpImp = '1'; // Formato de impressao do DACTE: 1-Retrato; 2-Paisagem.
            $ide->tpEmis = '1'; // Forma de emissao do CTe: 1-Normal; 4-EPEC pela SVC; 5-Contingência
            $ide->cDV = $cDV; // Codigo verificador
            $ide->tpAmb = '2'; // 1- Producao; 2-homologacao
            $ide->tpCTe = '0'; // 0- CT-e Normal; 1 - CT-e de Complemento de Valores;
            // 2 -CT-e de Anulação; 3 - CT-e Substituto
            $ide->procEmi = '0'; // Descricao no comentario acima
            $ide->verProc = '3.0'; // versao do aplicativo emissor
            $ide->indGlobalizado = '';
            //$ide->refCTE = '';             // Chave de acesso do CT-e referenciado            
            $ide->xMunEnv = 'FOZ DO IGUACU'; // Informar PAIS/Municipio para as operações com o exterior.
            $ide->UFEnv = 'RS'; // Informar 'EX' para operações com o exterior.
            $ide->modal = '01'; // Preencher com:01-Rodoviário; 02-Aéreo; 03-Aquaviário;04-
            $ide->tpServ = '0'; // 0- Normal; 1- Subcontratação; 2- Redespacho;
            $ide->cMunEnv = $this->getCodMunicipio($ide->xMunEnv, $ide->UFEnv); // Código do município (utilizar a tabela do IBGE)


            /**
             * @todo
             */
            // 3- Redespacho Intermediário; 4- Serviço Vinculado a Multimodal            
            $ide->xMunIni = 'FOZ DO IGUACU'; // Informar 'EXTERIOR' para operações com o exterior.
            $ide->UFIni = 'RS'; // Informar 'EX' para operações com o exterior.
            $ide->cMunFim = '3523909'; // Utilizar a tabela do IBGE. Informar 9999999 para operações com o exterior.
            $ide->cMunFim = $this->getCodMunicipio($ide->xMunIni, $ide->UFIni); // Código do município (utilizar a tabela do IBGE)


            /**
             * @todo
             */
            $ide->xMunFim = 'ITU'; // Informar 'EXTERIOR' para operações com o exterior.
            $ide->UFFim = 'SP'; // Informar 'EX' para operações com o exterior.
            $ide->cMunIni = $this->getCodMunicipio($ide->xMunFim, $ide->UFFim); // Código do município (utilizar a tabela do IBGE)

            $ide->retira = '1'; // Indicador se o Recebedor retira no Aeroporto; Filial,
            // Porto ou Estação de Destino? 0-sim; 1-não
            $ide->xDetRetira = ''; // Detalhes do retira
            $ide->indIEToma = '1';
            $ide->dhCont = ''; // Data e Hora da entrada em contingência; no formato AAAAMM-DDTHH:MM:SS
            $ide->xJust = '';                 // Justificativa da entrada em contingência

            $cte->tagide($ide);

            // Indica o "papel" do tomador: 0-Remetente; 1-Expedidor; 2-Recebedor; 3-Destinatário
            $toma3 = new \stdClass();
            $toma3->toma = '3';
            $cte->tagtoma3($toma3);
            //
            //$toma4 = new stdClass();
            //$toma4->toma = '4'; // 4-Outros; informar os dados cadastrais do tomador quando ele for outros
            //$toma4->CNPJ = '11509962000197'; // CNPJ
            //$toma4->CPF = ''; // CPF
            //$toma4->IE = 'ISENTO'; // Iscricao estadual
            //$toma4->xNome = 'RAZAO SOCIAL'; // Razao social ou Nome
            //$toma4->xFant = 'NOME FANTASIA'; // Nome fantasia
            //$toma4->fone = '5532128202'; // Telefone
            //$toma4->email = 'email@gmail.com';   // email
            //$cte->tagtoma4($toma4);




            /**
             * @todo
             */
            $enderToma = new \stdClass();
            $enderToma->xLgr = 'Avenida Independência'; // Logradouro
            $enderToma->nro = '482'; // Numero
            $enderToma->xCpl = ''; // COmplemento
            $enderToma->xBairro = 'Centro'; // Bairro
            $enderToma->cMun = '4308607'; // Codigo do municipio do IBEGE Informar 9999999 para operações com o exterior
            $enderToma->xMun = 'Garibaldi'; // Nome do município (Informar EXTERIOR para operações com o exterior.
            $enderToma->CEP = '95720000'; // CEP
            $enderToma->UF = $arr['siglaUF']; // Sigla UF (Informar EX para operações com o exterior.)
            $enderToma->cPais = '1058'; // Codigo do país ( Utilizar a tabela do BACEN )
            $enderToma->xPais = 'Brasil';                   // Nome do pais
            $cte->tagenderToma($enderToma);


            $emit = new \stdClass();
            $emit->CNPJ = $arr['cnpj']; // CNPJ do emitente
            //$emit->IE = '0100072968'; // Inscricao estadual
            //$emit->IEST = ""; // Inscricao estadual
            $emit->xNome = $provider->getName(); // Razao social
            $emit->xFant = $provider->getAlias(); // Nome fantasia
            $cte->tagemit($emit);



            $enderEmit = new \stdClass();
            $enderEmit->xLgr = $providerAddress->getStreet()->getStreet(); // Logradouro
            $enderEmit->nro = $providerAddress->getNumber(); // Numero
            $enderEmit->xCpl = $providerAddress->getComplement(); // Complemento
            $enderEmit->xBairro = $providerAddress->getStreet()->getDistrict()->getDistrict(); // Bairro
            $enderEmit->xMun = $providerAddress->getStreet()->getDistrict()->getCity()->getCity(); // Nome do municipio            
            $enderEmit->CEP = $providerAddress->getStreet()->getCep()->getCep(); // CEP
            $enderEmit->UF =  $providerAddress->getStreet()->getDistrict()->getCity()->getState()->getUf(); // Sigla UF
            $enderEmit->cMun = $this->getCodMunicipio($enderEmit->xMun, $enderEmit->UF); // Código do município (utilizar a tabela do IBGE)
            $enderEmit->fone = $provider->getPhone()[0]->getDdd() . $provider->getPhone()[0]->getPhone(); // Fone
            $cte->tagenderEmit($enderEmit);


            $retrieve = $data->getRetrievePeople();
            $retrieveDocument = $retrieve->getOneDocument();

            $rem = new \stdClass();
            $rem->CNPJ = $retrieveDocument->getDocument(); // CNPJ
            $rem->CPF = ''; // CPF
            //$rem->IE = '9057800426'; // Inscricao estadual
            $rem->xNome = $retrieve->getName();
            $rem->xFant = $retrieve->getAlias(); // Nome fantasia
            $rem->fone = ''; // Fone
            $rem->email = ''; // Email
            $cte->tagrem($rem);


            /**
             * @var \ControleOnline\Entity\Address $providerAddress
             */
            $retrieveAddress = $data->getAddressDestination();

            $enderReme = new \stdClass();
            $enderReme->xLgr = $retrieveAddress->getStreet()->getStreet(); // Logradouro
            $enderReme->nro = $retrieveAddress->getNumber(); // Numero
            $enderReme->xCpl = $retrieveAddress->getComplement(); // Complemento
            $enderReme->xBairro = $retrieveAddress->getStreet()->getDistrict()->getDistrict(); // Bairro
            $enderReme->xMun = $retrieveAddress->getStreet()->getDistrict()->getCity()->getCity(); // Nome do municipio (Informar EXTERIOR para operações com o exterior.)
            $enderReme->CEP = $retrieveAddress->getStreet()->getCep()->getCep(); // CEP
            $enderReme->UF = $retrieveAddress->getStreet()->getDistrict()->getCity()->getState()->getUf(); // Sigla UF (Informar EX para operações com o exterior.)
            $enderReme->cPais = '1058'; // Codigo do pais ( Utilizar a tabela do BACEN )             
            $enderReme->cMun = $this->getCodMunicipio($enderReme->xMun, $enderReme->UF); // Codigo Municipal (Informar 9999999 para operações com o exterior.)
            $enderReme->xPais = $retrieveAddress->getStreet()->getDistrict()->getCity()->getState()->getCountry()->getCountryname(); // Nome do pais
            $cte->tagenderReme($enderReme);




            $delivery = $data->getDeliveryPeople();
            $deliveryDocument = $delivery->getOneDocument();

            $dest = new \stdClass();
            $dest->CNPJ = $deliveryDocument->getDocument(); // CNPJ
            $dest->CPF = ''; // CPF
            //$rem->IE = '9057800426'; // Inscricao estadual
            $dest->xNome = $delivery->getName();
            $dest->xFant = $delivery->getAlias(); // Nome fantasia
            $dest->fone = ''; // Fone
            $dest->email = ''; // Email
            $dest->ISUF = ''; // Inscrição na SUFRAMA
            $cte->tagdest($dest);


            /**
             * @var \ControleOnline\Entity\Address $providerAddress
             */
            $destinationAddress = $data->getAddressDestination();


            $enderDest = new \stdClass();
            $enderDest->xLgr = $destinationAddress->getStreet()->getStreet(); // Logradouro
            $enderDest->nro = $destinationAddress->getNumber(); // Numero
            $enderDest->xCpl = $destinationAddress->getComplement(); // Complemento
            $enderDest->xBairro = $destinationAddress->getStreet()->getDistrict()->getDistrict(); // Bairro                        
            $enderDest->xMun = $destinationAddress->getStreet()->getDistrict()->getCity()->getCity(); // Nome do municipio (Informar EXTERIOR para operações com o exterior.)
            $enderDest->CEP = $destinationAddress->getStreet()->getCep()->getCep(); // CEP
            $enderDest->UF = $destinationAddress->getStreet()->getDistrict()->getCity()->getState()->getUf(); // Sigla UF (Informar EX para operações com o exterior.)
            $enderDest->cPais = '1058'; // Codigo do pais ( Utilizar a tabela do BACEN ) 
            $enderDest->xPais = $destinationAddress->getStreet()->getDistrict()->getCity()->getState()->getCountry()->getCountryname(); // Nome do pais
            $enderDest->cMun = $this->getCodMunicipio($enderDest->xMun, $enderDest->UF); // Código do município (utilizar a tabela do IBGE)
            $cte->tagenderDest($enderDest);


            $vPrest = new \stdClass();
            $vPrest->vTPrest = $data->getPrice(); // Valor total da prestacao do servico
            $vPrest->vRec = $data->getPrice();      // Valor a receber
            $cte->tagvPrest($vPrest);


            $comp = new \stdClass();
            $comp->xNome = 'FRETE VALOR'; // Nome do componente
            $comp->vComp = '3334.32';  // Valor do componente
            $cte->tagComp($comp);

            $icms = new \stdClass();
            $icms->cst = '00'; // 00 - Tributacao normal ICMS
            $icms->pRedBC = ''; // Percentual de redução da BC (3 inteiros e 2 decimais)
            $icms->vBC = 3334.32; // Valor da BC do ICMS
            $icms->pICMS = 12; // Alícota do ICMS
            $icms->vICMS = 400.12; // Valor do ICMS
            $icms->vBCSTRet = ''; // Valor da BC do ICMS ST retido
            $icms->vICMSSTRet = ''; // Valor do ICMS ST retido
            $icms->pICMSSTRet = ''; // Alíquota do ICMS
            $icms->vCred = ''; // Valor do Crédito Outorgado/Presumido
            $icms->vTotTrib = 754.38; // Valor de tributos federais; estaduais e municipais
            $icms->outraUF = false;    // ICMS devido à UF de origem da prestação; quando diferente da UF do emitente
            $icms->vICMSUFIni = 0;
            $icms->vICMSUFFim = 0;
            $icms->infAdFisco = 'Informações ao fisco';
            $cte->tagicms($icms);


            $cte->taginfCTeNorm();              // Grupo de informações do CT-e Normal e Substituto


            $infCarga = new \stdClass();
            $infCarga->vCarga = 130333.31; // Valor total da carga
            $infCarga->proPred = 'TUBOS PLASTICOS'; // Produto predominante
            $infCarga->xOutCat = 6.00; // Outras caracteristicas da carga
            $infCarga->vCargaAverb = 1.99;
            $cte->taginfCarga($infCarga);

            $infQ = new \stdClass();
            $infQ->cUnid = '01'; // Código da Unidade de Medida: ( 00-M3; 01-KG; 02-TON; 03-UNIDADE; 04-LITROS; 05-MMBTU
            $infQ->tpMed = 'ESTRADO'; // Tipo de Medida
            // ( PESO BRUTO; PESO DECLARADO; PESO CUBADO; PESO AFORADO; PESO AFERIDO; LITRAGEM; CAIXAS e etc)
            $infQ->qCarga = 18145.0000;  // Quantidade (15 posições; sendo 11 inteiras e 4 decimais.)
            $cte->taginfQ($infQ);
            $infQ->cUnid = '02'; // Código da Unidade de Medida: ( 00-M3; 01-KG; 02-TON; 03-UNIDADE; 04-LITROS; 05-MMBTU
            $infQ->tpMed = 'OUTROS'; // Tipo de Medida
            // ( PESO BRUTO; PESO DECLARADO; PESO CUBADO; PESO AFORADO; PESO AFERIDO; LITRAGEM; CAIXAS e etc)
            $infQ->qCarga = 31145.0000;  // Quantidade (15 posições; sendo 11 inteiras e 4 decimais.)
            $cte->taginfQ($infQ);

            $infNFe = new \stdClass();
            $infNFe->chave = '43160472202112000136550000000010571048440722'; // Chave de acesso da NF-e
            $infNFe->PIN = ''; // PIN SUFRAMA
            $infNFe->dPrev = '2016-10-30';                                       // Data prevista de entrega
            $cte->taginfNFe($infNFe);

            $infModal = new \stdClass();
            $infModal->versaoModal = '3.00';
            $cte->taginfModal($infModal);

            $rodo = new \stdClass();
            $rodo->RNTRC = '00739357';
            $cte->tagrodo($rodo);

            $aereo = new \stdClass();
            $aereo->nMinu = '123'; // Número Minuta
            $aereo->nOCA = ''; // Número Operacional do Conhecimento Aéreo
            $aereo->dPrevAereo = date('Y-m-d');
            $aereo->natCarga_xDime = ''; // Dimensões 1234x1234x1234 em cm
            $aereo->natCarga_cInfManu = []; // Informação de manuseio, com dois dígitos, pode ter mais de uma ocorrência.
            $aereo->tarifa_CL = 'G'; // M - Tarifa Mínima / G - Tarifa Geral / E - Tarifa Específica
            $aereo->tarifa_cTar = ''; // código da tarifa, deverão ser incluídos os códigos de três digítos correspondentes à tarifa
            $aereo->tarifa_vTar = 100.00; // valor da tarifa. 15 posições, sendo 13 inteiras e 2 decimais. Valor da tarifa por kg quando for o caso
            $cte->tagaereo($aereo);

            $autXML = new \stdClass();
            $autXML->CPF = '59195248471'; // CPF ou CNPJ dos autorizados para download do XML
            $cte->tagautXML($autXML);

            //Monta CT-e
            $cte->montaCTe();
            $chave = $cte->chCTe;

            $xml = $cte->getXML();
            $xml = $this->sign($xml);

            $invoiceTax = new SalesInvoiceTax();
            $invoiceTax->setInvoice($xml);
            $invoiceTax->setInvoiceNumber($numeroCTE);
            $this->manager->persist($invoiceTax);
            $this->manager->flush();


            $orderInvoiceTax = new SalesOrderInvoiceTax();
            $orderInvoiceTax->setOrder($data);
            $orderInvoiceTax->setInvoiceType(57);
            $orderInvoiceTax->setInvoiceTax($invoiceTax);
            $orderInvoiceTax->setIssuer($provider);

            $this->manager->persist($orderInvoiceTax);
            $this->manager->flush();

            return new JsonResponse([
                'response' => [
                    'data'    => $data->getId(),
                    'invoice_tax' => $invoiceTax->getId(),
                    'xml' => $xml,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'response' => [
                    'count'   => 0,
                    'error'   => $th->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }
    protected function getCodMunicipio($mun, $uf)
    {

        /**
         * @todo
         */
        $cod['sp'] = [
            'Guarulhos' => '4108304',
            'São Paulo' => '4108304'
        ];

        return $cod[$uf][$mun];
    }
    protected function getLastDacte()
    {
        return '127'; //@todo
    }

    protected function montaChave($cUF, $ano, $mes, $cnpj, $mod, $serie, $numero, $tpEmis, $codigo = '')
    {
        if ($codigo == '') {
            $codigo = $numero;
        }
        $forma = "%02d%02d%02d%s%02d%03d%09d%01d%08d";
        $chave = sprintf(
            $forma,
            $cUF,
            $ano,
            $mes,
            $cnpj,
            $mod,
            $serie,
            $numero,
            $tpEmis,
            $codigo
        );
        return $chave . $this->calculaDV($chave);
    }

    protected function sign($xml)
    {
        //Assina
        $xml = $this->tools->signCTe($xml);
    }
    protected function sendData($xml)
    {

        //Envia lote e autoriza
        $axmls[] = $xml;
        $lote = substr(str_replace(',', '', number_format(microtime(true) * 1000000, 0)), 0, 15);
        $res = $this->tools->sefazEnviaLote($axmls, $lote);

        //Converte resposta
        $stdCl = new Standardize($res);
        //Output array
        $arr = $stdCl->toArray();
        //print_r($arr);
        //Output object
        $std = $stdCl->toStd();

        if ($std->cStat != 103) { //103 - Lote recebido com Sucesso
            //processa erros
            print_r($arr);
        }

        //Consulta Recibo
        $res = $this->tools->sefazConsultaRecibo($std->infRec->nRec);
        $stdCl = new Standardize($res);
        $arr = $stdCl->toArray();
        $std = $stdCl->toStd();
        if ($std->protCTe->infProt->cStat == 100) { //Autorizado o uso do CT-e
            //adicionar protocolo
        }
        echo '<pre>';
        print_r($arr);
    }

    protected function calculaDV($chave43)
    {
        $multiplicadores = array(2, 3, 4, 5, 6, 7, 8, 9);
        $iCount = 42;
        $somaPonderada = 0;
        while ($iCount >= 0) {
            for ($mCount = 0; $mCount < count($multiplicadores) && $iCount >= 0; $mCount++) {
                $num = (int) substr($chave43, $iCount, 1);
                $peso = (int) $multiplicadores[$mCount];
                $somaPonderada += $num * $peso;
                $iCount--;
            }
        }
        $resto = $somaPonderada % 11;
        if ($resto == '0' || $resto == '1') {
            $cDV = 0;
        } else {
            $cDV = 11 - $resto;
        }
        return (string) $cDV;
    }
}
