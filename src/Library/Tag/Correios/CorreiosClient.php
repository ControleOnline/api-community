<?php

namespace App\Library\Tag\Correios;

use \PhpSigep\Config;
use \PhpSigep\Bootstrap;
use \PhpSigep\Model\AccessData;
use \PhpSigep\Model\Etiqueta;
use \PhpSigep\Model\PreListaDePostagem;
use \PhpSigep\Model\Remetente;
use \PhpSigep\Model\SolicitaEtiquetas;
use \PhpSigep\Model\Dimensao;
use \PhpSigep\Model\ServicoDePostagem;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use App\Library\Tag\AbstractTag;

class CorreiosClient  extends AbstractTag
{
    /*
     * PDF variables
     * 
     */
    protected static $CdAdministrativoPDF    = '19233663';
    protected static $numContratoPDF         = '9912471296';
    protected static $EUsuarioPDF            = '34190533000103';
    protected static $ESenhaPDF              = 'jnx055';


    protected function configPdf()
    {
        //$this->accessData = new \PhpSigep\Model\AccessDataHomologacao();

        $this->accessData = new AccessData();
        $this->accessData->setUsuario(self::$EUsuarioPDF);
        $this->accessData->setSenha(self::$ESenhaPDF);
        $this->accessData->setCodAdministrativo(self::$CdAdministrativoPDF);
        $this->accessData->setNumeroContrato(self::$numContratoPDF);
        $this->accessData->setCartaoPostagem('0067599079');
        $this->accessData->setCnpjEmpresa('34028316000103');

        $this->accessData->setDiretoria(new \PhpSigep\Model\Diretoria(\PhpSigep\Model\Diretoria::DIRETORIA_DR_BRASILIA));

        $config = new Config();
        $config->setAccessData($this->accessData);
        $config->setEnv(Config::ENV_PRODUCTION);
        //$config->setEnv(\PhpSigep\Config::ENV_DEVELOPMENT);

        $config->setCacheOptions(
            array(
                'storageOptions' => array(
                    // Qualquer valor setado neste atributo será mesclado ao atributos das classes 
                    // "\PhpSigep\Cache\Storage\Adapter\AdapterOptions" e "\PhpSigep\Cache\Storage\Adapter\FileSystemOptions".
                    // Por tanto as chaves devem ser o nome de um dos atributos dessas classes.
                    'enabled' => false,
                    'ttl' => 43200, // "time to live" de 10 segundos
                    'cacheDir' => getcwd() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache', // Opcional. Quando não inforado é usado o valor retornado de "sys_get_temp_dir()"
                ),
            )
        );
        Bootstrap::start($config);
    }

    public function getPdf(Order $orderData)
    {
        $people = $orderData->getProvider();

        $logo = $this->getPeopleFilePath($people);

        $this->configPdf();

        //Parametro opcional indica qual layout utilizar para a chancela. Ex.: CartaoDePostagem::TYPE_CHANCELA_CARTA, CartaoDePostagem::TYPE_CHANCELA_CARTA_2016
        $layoutChancela = array(\PhpSigep\Pdf\CartaoDePostagem::TYPE_CHANCELA_CARTA);
        $data = $this->getPdfTagData($orderData);
        if ($data) {
            $pdf = new \PhpSigep\Pdf\CartaoDePostagem2016($data, time(), $logo, $layoutChancela);
            $pdf->render();
        }
    }

    protected function getPdfTagData(Order $orderData)
    {
        // remover após corrigir problema da biblioteca
        error_reporting(0);

        $invoices = $orderData->getInvoiceTax();
        $invoice = $invoices[0];

        $destinatario = new \PhpSigep\Model\Destinatario();
        $destinatario->setNome($orderData->getDeliveryContact()->getName());
        $destinatario->setLogradouro($orderData->getAddressDestination()->getStreet()->getStreet());
        $destinatario->setNumero($orderData->getAddressDestination()->getNumber());
        $destinatario->setComplemento($orderData->getAddressDestination()->getComplement());

        $destino = new \PhpSigep\Model\DestinoNacional();
        $destino->setBairro($orderData->getAddressDestination()->getStreet()->getDistrict()->getDistrict());
        $destino->setCep(preg_replace("/^(\d{5})(\d{3})$/", "\\1-\\2", str_pad($orderData->getAddressDestination()->getStreet()->getCep()->getCep(), 8, '0', STR_PAD_LEFT)));
        $destino->setCidade($orderData->getAddressDestination()->getStreet()->getDistrict()->getCity()->getCity());
        $destino->setUf($orderData->getAddressDestination()->getStreet()->getDistrict()->getCity()->getState()->getState());

        $destino->setNumeroNotaFiscal($invoice ? $invoice->getInvoiceTax()->getInvoiceNumber() : 0);
        $destino->setNumeroPedido($orderData->getId());

        // Estamos criando uma etique falsa, mas em um ambiente real voçê deve usar o método
        // {@link \PhpSigep\Services\SoapClient\Real::solicitaEtiquetas() } para gerar o número das etiquetas

        /*$quote = $orderData->getQuote();
        $detail = $quote->getQuoteDetail()[0];
        $deliveryTax = $detail->getDeliveryTax();
        $groupTax = $deliveryTax->getGroupTax();
        $code = $groupTax->getCode();*/
        $code = ServicoDePostagem::SERVICE_PAC_41068;

        $params = new SolicitaEtiquetas();
        $params->setQtdEtiquetas(1);
        $params->setServicoDePostagem($code);
        $params->setAccessData($this->accessData);
        //$numero_etiqueta = Real::solicitaEtiquetas($params);

        $numero_etiqueta = $orderData->getId();

        /*if ($numero_etiqueta->hasError()) {
            throw new InvalidValueException($numero_etiqueta->getErrorMsg());
        }*/

        $etiqueta = new Etiqueta();
        /*$etiqueta->setEtiquetaSemDv($numero_etiqueta->toArray()['result'][0]['etiquetaSemDv']);*/
        $etiqueta->setEtiquetaSemDv($numero_etiqueta);

        // ***  DADOS DA ENCOMENDA QUE SERÁ DESPACHADA *** //
        $volumes = 0;
        foreach ($orderData->getOrderPackage() as $product) {
            foreach (range(1, $product->getQtd()) as $p) {
                $volumes++;
                $products[] = $product;
            }
        }

        $v = 0;
        foreach ($products as $product) {
            $v++;
            $dimensao = new Dimensao();
            $dimensao->setAltura($product->getHeight());
            $dimensao->setLargura($product->getWidth());
            $dimensao->setComprimento($product->getDepth());
            $dimensao->setDiametro(0);
            $dimensao->setTipo(Dimensao::TIPO_PACOTE_CAIXA);



            //$servicoAdicional = new \PhpSigep\Model\ServicoAdicional();
            //$servicoAdicional->setCodigoServicoAdicional(\PhpSigep\Model\ServicoAdicional::SERVICE_REGISTRO);
            //$servicoAdicional->setCodigoServicoAdicional(\PhpSigep\Model\ServicoAdicional::SERVICE_AVISO_DE_RECEBIMENTO);

            // Se não tiver valor declarado informar 0 (zero)			
            $servicoAdicional2 = new \PhpSigep\Model\ServicoAdicional();
            $servicoAdicional2->setCodigoServicoAdicional(\PhpSigep\Model\ServicoAdicional::SERVICE_REGISTRO);
            $servicoAdicional2->setCodigoServicoAdicional(\PhpSigep\Model\ServicoAdicional::SERVICE_VALOR_DECLARADO_PAC);
            $servicoAdicional2->setValorDeclarado($orderData->getInvoiceTotal());


            $encomenda = new \PhpSigep\Model\ObjetoPostal();
            $encomenda->setServicosAdicionais(array(
                //$servicoAdicional, 
                $servicoAdicional2
            ));

            $encomenda->setDestinatario($destinatario);
            $encomenda->setDestino($destino);
            $encomenda->setDimensao($dimensao);
            $encomenda->setEtiqueta($etiqueta);
            $encomenda->setPeso($orderData->getCubage());
            $encomenda->setObservacao('Volume ' . $v . ' de ' . $volumes);
            $encomenda->setServicoDePostagem(new ServicoDePostagem($code));
            $encomendas[] = $encomenda;
        }


        // ***  FIM DOS DADOS DA ENCOMENDA QUE SERÁ DESPACHADA *** //
        // *** DADOS DO REMETENTE *** //
        $remetente = new Remetente();
        $remetente->setNome($orderData->getRetrievePeople()->getName());
        $remetente->setLogradouro($orderData->getAddressOrigin()->getStreet()->getStreet());
        $remetente->setNumero($orderData->getAddressOrigin()->getNumber());
        $remetente->setComplemento($orderData->getAddressOrigin()->getComplement());
        $remetente->setBairro($orderData->getAddressOrigin()->getStreet()->getDistrict()->getDistrict());
        $remetente->setCep(preg_replace("/^(\d{5})(\d{3})$/", "\\1-\\2", str_pad($orderData->getAddressOrigin()->getStreet()->getCep()->getCep(), 8, '0', STR_PAD_LEFT)));
        $remetente->setUf($orderData->getAddressOrigin()->getStreet()->getDistrict()->getCity()->getState()->getState());
        $remetente->setCidade($orderData->getAddressOrigin()->getStreet()->getDistrict()->getCity()->getCity());

        // *** FIM DOS DADOS DO REMETENTE *** //
        $plp = new PreListaDePostagem();
        $plp->setAccessData($this->accessData);
        $plp->setEncomendas($encomendas);
        $plp->setRemetente($remetente);

        return $plp;
    }
}
