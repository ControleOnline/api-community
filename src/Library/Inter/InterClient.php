<?php

namespace App\Library\Inter;

use App\Controller\GetProviderDataPerInvoiceId;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\People;
use ControleOnline\Entity\ReceiveInvoice;
use App\Service\AddressService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ctodobom\APInterPHP\BancoInter;
use ctodobom\APInterPHP\BancoInterException;
use ctodobom\APInterPHP\Cobranca\Boleto;
use ctodobom\APInterPHP\Cobranca\Pagador;
use Symfony\Component\HttpKernel\KernelInterface;

class InterClient
{
    /**
     * Order entity
     *
     * @var Invoice
     */
    private $invoice = null;

    /**
     * Inter configs
     *
     * @var array
     */
    private $params;

    /**
     * @var KernelInterface $appKernel
     */
    private $appKernel;

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var array
     */
    private $configs;

    /**
     * Address Service
     *
     * @var AddressService
     */
    private $address;

    /**
     * @param ReceiveInvoice $invoice
     * @param array $configs
     * @param KernelInterface $appKernel
     * @param EntityManager $entityManager
     */
    public function __construct(Invoice $invoice, array $configs, KernelInterface $appKernel, EntityManager $entityManager)
    {
        $this->invoice = $invoice;
        $this->configs = $configs;
        $this->em = $entityManager;
        $this->appKernel = $appKernel;
    }

    /**
     * @return array
     * @throws BancoInterException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function getPayment(): array
    {
        $payerId = $this->configs['payerId'];
        $contaBanco = $this->configs['conta_inter'];
        $providerId = $this->configs['providerId'];

        $invoiceId = $this->invoice->getId();

        /**
         * @var ReceiveInvoice $invEtt
         */
        $invEtt = $this->em->getRepository(ReceiveInvoice::class)->find($invoiceId);
        if (empty($invEtt)) {
            throw new Exception("Não foi possível localizar a fatura com o ID: " . $invoiceId);
        }

        // -------------------- Verifica se o boleto está pago, se sim, altera o status ID da invoice para "paid"
        $invoiceRealStatus = trim($this->invoice->getStatus()->getRealStatus());
        if ($invoiceRealStatus === 'pending') {
            $statusPaymentApi = $this->checkBilletPaidApiInter($contaBanco, $invoiceId);
            $ret['statusPaymentApi'] = $statusPaymentApi;
            if ($statusPaymentApi === "PAGO") {
                $invEtt->setStatus($this->em->getRepository(Status::class)->findOneBy(['status' => 'paid']));
            }
        }
        // --------------------------------------------------------------------------------

        $invoiceRealStatus = trim($this->invoice->getStatus()->getRealStatus());
        $paymentStatus = ($invoiceRealStatus === 'pending') ? 'created' : $invoiceRealStatus;

        // A T E N Ç Ã O
        //
        // Todos os dados verificáveis precisam ser válidos
        // Utilize sempre CPF/CNPJ, CEP, Cidade e Estado válidos
        // Para evitar importunar estranhos utilize seus próprios
        // dados ou de alguma pessoa que esteja ciente, pois as
        // cobranças sempre são cadastradas no sistema quente
        // do banco central e aparecerão no DDA dos sacados.
        //
        // Os dados de exemplo NÃO SÃO VÁLIDOS e se não forem
        // alterados o script de exemplo não funcionará.

        // dados de teste


        // -------------------------------- Captura dados da tabela 'invoice'
        $amount = $invEtt->getPrice();
        $paymentDate = ($invEtt->getPaymentDate() !== null) ? $invEtt->getPaymentDate()->format('dmY') : '';
        $billetDueDate = $invEtt->getDueDate()->format('Y-m-d'); // Para usar na data de vencimento do boleto
        $amount = (float)number_format($amount, 2, '.', '');

        // ------- Monta caminho do arquivo PDF do boleto
        $dirPath = 'data/invoices/inter';
        $pathRoot = $this->appKernel->getProjectDir();
        $fullPath = $pathRoot . '/' . $dirPath; // Pasta para salvar o arquivo usando API Bco Inter
        $fullPathAndNewFile = $pathRoot . '/' . $dirPath . '/boleto-' . $invoiceId . '-' . $billetDueDate . '.pdf'; // Arquivo do PDF completo com o nome do invoiceId + invoiceDate
        $partialPath = strstr($fullPathAndNewFile, "data/invoices", false); // Ex: 'data/invoices/inter/20672.pdf'
        // ------------------------------------------------------------------

        // 149377932
        // $fullPath .= '/' . $providerId . 'idPr'; // Ex: '/home/gilberto/co/api/data/boletos-inter/3idPr'
        // $partialPath = strstr($fullPath,"data/boletos",false); // Ex: 'data/boletos-inter/3idPr'
        // ------ Verifica se ja existe a pasta do beneficiário para os PDFs dos boletos
        // ------ Se não existir, cria uma
        // if (!is_dir($fullPath) && (!mkdir($fullPath, 0755) && !is_dir($fullPath))) {
        //      throw new RuntimeException(sprintf('Directory "%s" was not created', $fullPath));
        // }
        // ------------------------------------------------------

        // throw new Exception($fullPath . ' - ' . $fullPathAndNewFile);

        /**
         * @var People $peopleProviderEtt
         */
        $peopleProviderEtt = $this->em->getRepository(People::class)->find($providerId);
        if ($peopleProviderEtt === null) {
            throw new Exception("Dados 'People' do Beneficiário ID:$providerId não foram localizados");
        }
        if ($peopleProviderEtt->getOneDocument() === null) {
            throw new Exception("O Beneficiário ID:$providerId não possui um CNPJ Cadastrado");
        }
        $peopleProviderDocument = $peopleProviderEtt->getOneDocument()->getDocument();

        $payerEtt = $this->em->getRepository(People::class)->find($payerId);
        if (empty($payerEtt)) {
            throw new Exception("Dados 'People' do Pagador ID:$payerId não foram localizados");
        }

        $payerType = $payerEtt->getPeopleType(); // Ex: 'J','F'
        $payerName = preg_replace("/[^a-zA-Z0-9 ]+/", "", $payerEtt->getName()); // Ex: 'FRETE CLICK - PARCERIA MG'
        /**
         * @var Address $payerAddressEtt
         */
        $payerAddressEtt = $this->em->getRepository(Address::class)->findOneBy(['people' => $payerId]);
        if ($payerAddressEtt === null) {
            throw new Exception("Endereço 'Address' do Pagador ID:$payerId não foi localizado");
        }
        if ($payerEtt->getOneDocument() === null) {
            throw new Exception("O Pagador ID:$payerId não possui um CPF ou CNPJ Cadastrado");
        }

        $peopleDocument = $payerEtt->getOneDocument()->getDocument();

        $payerAddressNumber = $payerAddressEtt->getNumber();
        $payerAddressComplement = $payerAddressEtt->getComplement();
        $payerAddressStreet = $payerAddressEtt->getStreet()->getStreet();
        $payerAddressDistrict = $payerAddressEtt->getStreet()->getDistrict()->getDistrict();
        $payerAddressCity = $payerAddressEtt->getStreet()->getDistrict()->getCity()->getCity();
        $payerAddressState = $payerAddressEtt->getStreet()->getDistrict()->getCity()->getState()->getState();
        $payerAddressUF = $payerAddressEtt->getStreet()->getDistrict()->getCity()->getState()->getUf();
        $payerAddressCEP = $payerAddressEtt->getStreet()->getCep()->getCep();
        $payerAddressCEP = strlen($payerAddressCEP) === 7 ? '0' . $payerAddressCEP : $payerAddressCEP;

        /*
        $ret['invoiceId'] = $invoiceId;
        $ret['payerId'] = $payerId;
        $ret['payerType'] = $payerType;
        $ret['payerName'] = $payerName;
        $ret['peopleDocument'] = $peopleDocument;
        $ret['payerAddressNumber'] = $payerAddressNumber;
        $ret['payerAddressComplement'] = $payerAddressComplement;
        $ret['payerAddressStreet'] = $payerAddressStreet;
        $ret['payerAddressDistrict'] = $payerAddressDistrict;
        $ret['payerAddressCity'] = $payerAddressCity;
        $ret['payerAddressState'] = $payerAddressState;
        $ret['payerAddressUF'] = $payerAddressUF;
        $ret['payerAddressCEP'] = $payerAddressCEP;
        $ret['peopleProviderDocument'] = $peopleProviderDocument;
        $ret['contaBanco'] = $contaBanco;
        $ret['amount'] = $amount;
        $ret['billetDueDate'] = $billetDueDate;
        */

        $arqExiste = false;
        // ----------- Verifica se o arquivo do boleto já existe
        if (file_exists($fullPathAndNewFile)) {
            $arqExiste = true;
        }
        // --------------------------------------------

        // $deb['arqExiste'] = $arqExiste;
        // $deb['fullPathAndNewFile'] = $fullPathAndNewFile;
        // var_dump($ret);
        // throw new Exception("Debug: $invoiceRealStatus");
        // $arqExiste = true;

        if ($arqExiste === false && $invoiceRealStatus === 'pending') { // -------- Só gera o boleto caso ele não exista

            $banco = new BancoInter($contaBanco, $this->configs['certificado'], $this->configs['chavePrivada']);

            // Se a chave privada estiver encriptada no disco
            // $banco->setKeyPassword('senhadachave');

            $pagador = new Pagador();
            $cpfPagador = $peopleDocument;
            if ($payerType === 'F') {
                // $cpfPagador = "32115692861";
                $pagador->setTipoPessoa(Pagador::PESSOA_FISICA);
            } else { // 'J'
                // $cpfPagador = "19810123000116";
                $pagador->setTipoPessoa(Pagador::PESSOA_JURIDICA);
            }
            $pagador->setNome($payerName);
            $pagador->setEndereco($payerAddressStreet);
            $pagador->setNumero($payerAddressNumber);
            $pagador->setBairro($payerAddressDistrict);
            $pagador->setCidade($payerAddressCity);
            $pagador->setCep($payerAddressCEP);

            $pagador->setCnpjCpf($cpfPagador);
            $pagador->setUf($payerAddressUF);

            $boleto = new Boleto();
            $boleto->setCnpjCPFBeneficiario($peopleProviderDocument);
            $boleto->setPagador($pagador);
            $boleto->setSeuNumero($this->invoice->getId());
            $boleto->setDataEmissao(date('Y-m-d'));
            $boleto->setValorNominal($amount);
            // $boleto->setDataVencimento(date_add(new \DateTime('now'), new \DateInterval("P10D"))->format('Y-m-d'));
            $boleto->setDataVencimento($billetDueDate);
            $banco->createBoleto($boleto);

            /*
            echo "\nBoleto Criado\n";
            echo "\n seuNumero: " . $boleto->getSeuNumero();
            echo "\n nossoNumero: " . $boleto->getNossoNumero();
            echo "\n codigoBarras: " . $boleto->getCodigoBarras();
            echo "\n linhaDigitavel: " . $boleto->getLinhaDigitavel();
            */

            $pdf = $banco->getPdfBoleto($boleto->getNossoNumero(), $fullPath);
            $invEtt->setInvoiceBankId($boleto->getNossoNumero());
            // --------- Renomeia o boleto criado pelo banco inter
            rename($pdf, $fullPathAndNewFile);
            // --------------------------------------------------

        }

        // ------ Descomentar simula boleto pago
        // $invoiceRealStatus = 'paid';
        // $paymentStatus = 'paid';
        // -----------------

        $ret['orderId'] = $invoiceId;
        $ret['amount'] = str_replace('.', ',', $amount);
        $ret['paymentType'] = 'billet';
        $ret['paymentStatus'] = $paymentStatus;
        $ret['paidAmount'] = '';
        $ret['paymentDate'] = $paymentDate;
        $ret['invoiceUrl'] = $partialPath;
        $ret['status'] = $this->invoice->getStatus()->getStatus();
        $ret['invoiceRealStatus'] = $invoiceRealStatus;

        // ------------------- Atualiza campos na tabela 'invoice'
        $invEtt->setInvoiceType('BOLETO INTER');
        $this->em->persist($invEtt);
        $this->em->flush();
        // ---------------------------------------------------------

        /*
        try {
            echo "\nConsultando boleto\n";
            $boleto2 = $banco->getBoleto($boleto->getNossoNumero());
            var_dump($boleto2);
        } catch (BancoInterException $e) {
            echo "\n\n" . $e->getMessage();
            echo "\n\nCabeçalhos: \n";
            echo $e->reply->header;
            echo "\n\nConteúdo: \n";
            echo $e->reply->body;
        }

        try {
            echo "\nBaixando boleto\n";
            $banco->baixaBoleto($boleto->getNossoNumero(), INTER_BAIXA_DEVOLUCAO);
            echo "Boleto Baixado";
        } catch (BancoInterException $e) {
            echo "\n\n" . $e->getMessage();
            echo "\n\nCabeçalhos: \n";
            echo $e->reply->header;
            echo "\n\nConteúdo: \n";
            echo $e->reply->body;
        }

        try {
            echo "\nConsultando boleto antigo\n";
            $boleto2 = $banco->getBoleto("00571817313");
            var_dump($boleto2);
        } catch (BancoInterException $e) {
            echo "\n\n" . $e->getMessage();
            echo "\n\nCabeçalhos: \n";
            echo $e->reply->header;
            echo "\n\nConteúdo: \n";
            echo $e->reply->body;
        }

        try {
            echo "\nListando boletos vencendo nos próximos 10 dias (apenas a primeira página)\n";
            $listaBoletos = $banco->listaBoletos(date('Y-m-d'), date_add(new \DateTime('now'), new \DateInterval("P10D"))->format('Y-m-d'));
            var_dump($listaBoletos);
        } catch (BancoInterException $e) {
            echo "\n\n" . $e->getMessage();
            echo "\n\nCabeçalhos: \n";
            echo $e->reply->header;
            echo "\n\nConteúdo: \n";
            echo $e->reply->body;
        }
        */

        return $ret;
    }

    /**
     * <code>
     * Return:
     * "PAGO" → Quando está pago
     * "EMABERTO" → Quando o boleto foi emitido e está aguardando pagamento
     * "NÃO GERADO" → Quando o boleto ainda não foi gerado
     * "NÃO LOCALIZADO" → Não encontrou pelo nosso número ou API do banco inter deu problemas na consulta
     * </code>
     * @param $contaBanco
     * @param $invoiceId
     * @return string
     * @throws Exception
     */
    private function checkBilletPaidApiInter($contaBanco, $invoiceId): string
    {
        /**
         * @var ReceiveInvoice $invEtt
         */
        $invEtt = $this->em->getRepository(ReceiveInvoice::class)->find($invoiceId);
        if (empty($invEtt)) {
            throw new Exception("Não foi possível localizar a fatura com o ID: " . $invoiceId . " --> Method: checkBilletPaidApiInter()");
        }
        $nossoNumeroBoleto = $invEtt->getInvoiceBankId();
        if (!is_null($nossoNumeroBoleto)) {
            try {
                $banco = new BancoInter($contaBanco, $this->configs['certificado'], $this->configs['chavePrivada']);
                $situacao = $banco->getBoleto($nossoNumeroBoleto)->situacao;
            } catch (BancoInterException $e) {
                $situacao = 'NÃO LOCALIZADO';
            }
        } else {
            $situacao = 'NÃO GERADO';
        }
        // $situacao = 'PAGO'; // Simular retorno API boleto pago
        return $situacao;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method string getProjectDir()
    }
}
