<?php

namespace App\Controller;

use ControleOnline\Entity\Filesb;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleEmployee;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\User;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Library\Utils\Formatter;
use App\Library\Utils\Str;

class AdminFilesController extends AbstractController
{
    /**
     * @var Request
     */
    private $request = null;

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
    private $appKernel;

    public function __construct(EntityManagerInterface $entityManager, Security $security, KernelInterface $appKernel)
    {
        $this->manager = $entityManager;
        $this->security = $security;
        $this->appKernel = $appKernel;
        // $this->checkApiToken(); // Usado somente quando não está utilizando '@ApiResource'
    }

    /**
     * @throws ConnectionException
     */
    public function __invoke(Request $request): JsonResponse
    {

        $ret = array();

        try {

            $method = $request->getMethod();
            $route = $request->getRequestUri();
            $id = $request->get('id', null);
            $apiItemOperationName = $request->get('_api_item_operation_name', null);
            $apiCollectionOperationName = $request->get('_api_collection_operation_name', null);

            // dump($request->files->get('file'));

            // ------------- /filesb -> Método POST -> Cria Registro
            if ($apiItemOperationName === 'cria_arquivo') {
                $ret = $this->postCreateDocFiles($request);
            }
            // ------------- /filesb/{id} -> Método GET -> Pega Registro Único
            if ($apiItemOperationName === 'get_unique_by_id') {
                $ret = $this->getUniqueDocFiles($id, false);
            }
            // ------------- /filesb/{id} -> Método POST -> Atualiza Registro Único
            if ($apiItemOperationName === 'atualiza_files') {
                $ret = $this->postUpdateUniqueDocFiles($request, $id);
            }
            // ------------- /filesb/{id} -> Método DELETE -> Apaga registro único
            if ($apiItemOperationName === 'delete_files') {
                $ret = $this->deleteUniqueFiles($id);
            }
            // ------------- /filesb -> Método GET -> Pega Coleção
            if ($apiCollectionOperationName === 'pega_colecao') {
                $ret = $this->getCollecFiscalFiles($request, $id);
            }



            if (empty($ret)) {
                throw new Exception("Método: $method com o endpoint: $route não é permitido ($apiItemOperationName) ($apiCollectionOperationName)");
            }
        } catch (Exception $e) {

            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }
            $ret['response']['data'] = [];
            $ret['response']['count'] = 0;
            $ret['response']['success'] = false;
            $ret['response']['message'] = $e->getMessage();
        }

        return new JsonResponse($ret, 200);
    }

    private function checkApiToken(): void
    {
        if ($this->security->getUser() === null) {
            $ret['message'] = 'Authentication Required';
            $data_de_agora = gmdate('D, d M Y H:i:s');
            header("Expires: $data_de_agora GMT");
            header("Last-Modified: $data_de_agora GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($ret);
            exit;
        }
    }



    /**
     * Valida datas completas ou somente datas de mês e ano
     * Valida e já retorna a data no formato AAAA-MM-DD para inserir no BD
     *
     * @param string $data {Ex: '09/2021' ou '01/09/2021'}
     * @return string {Ex: '2021-09-01'}
     * @throws Exception
     */
    private function valFullStringDateOrOnlyMonthAndYear(string $data): string
    {
        $errorMessage = "O parâmetro 'date_period' deve estar no formato MM/AAAA ou DD/MM/AAAA";
        $qtdCharData = strlen($data);
        if (!in_array($qtdCharData, [7, 10])) { // Aceita somente data completa ou data de Mês e Ano
            throw new Exception($errorMessage);
        }
        $day = 1;
        if ($qtdCharData === 7) { // Quando é uma data de Mês e Ano
            $month = (int)substr($data, 0, 2);
            $year = (int)substr($data, 3, 4);
        } else { // Quando é uma data de Dia, Mês e Ano
            $month = (int)substr($data, 3, 2);
            $year = (int)substr($data, 6, 4);
        }
        if (!checkdate($month, $day, $year)) {
            throw new Exception($errorMessage);
        }
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $year = (string)$year;
        if (strlen($year) < 4) {
            throw new Exception($errorMessage);
        }
        return "$year-$month-$day";
    }



    /**
     * Verifica se o parâmetro 'company_id' pertence há uma compania válida do usuário do token
     * <code>
     * retorno:
     * ['companyName'] = Ex: 'Contabion'
     * </code>
     * @return array
     */
    private function findMyIdInMyCompanies($companyId): ?array
    {

        $companyId = is_numeric($companyId) ? (string)$companyId : $companyId;

        $ret = null;
        /**
         * @var User
         */
        $currentUser = $this->security->getUser();
        /**
         * @var People
         */
        $userPeople = $currentUser->getPeople();
        /**
         * @var PeopleEmployee $peopleCompany
         */
        foreach ($userPeople->getPeopleCompany() as $peopleCompany) {
            if ($peopleCompany->getEnabled()) {
                $people = $peopleCompany->getCompany();
                if ($companyId === (string)$people->getId()) {
                    $ret['companyName'] = $people->getName();
                }
            }
        }

        return $ret;
    }

    /**
     * @throws Exception
     */
    private function deleteUniqueFiles($id): array
    {

        $ret = $this->getUniqueDocFiles($id, true);
        $filesEtt = $this->manager->getRepository(Filesb::class)->find($id);
        if (empty($filesEtt)) {
            throw new Exception("Registro 'files' com o ID: $id, não foi encontrado.");
        }
        $this->manager->remove($filesEtt);
        $this->manager->flush();

        // ---------------- Apaga Arquivo de GUIA e/ou Recibo caso existam
        $rootPath = $this->appKernel->getProjectDir();
        $guideFile = $ret['response']['data']['file_name_guide'];
        if (!is_null($guideFile)) { // -------- Arquivo Guia
            $guideFile = $rootPath . '/' . $guideFile;
            if (file_exists($guideFile)) {
                $files = new FileSystem();
                $files->remove($guideFile);
            }
        }
        $receiptFile = $ret['response']['data']['file_name_receipt'];
        if (!is_null($receiptFile)) { // -------- Arquivo Recibo
            $receiptFile = $rootPath . '/' . $receiptFile;
            if (file_exists($receiptFile)) {
                $files = new FileSystem();
                $files->remove($receiptFile);
            }
        }
        // ----------------------------------------------------------------

        $ret['response']['count'] = 1;
        $ret['response']['success'] = true;
        $ret['response']['message'] = 'Registro <strong>Apagado</strong> com sucesso.';

        return $ret;
    }

    /**
     * Pega Coleção
     *
     * Method=GET
     * Route=/filesb
     *
     * @param Request $request
     * @return array
     * @throws Exception
     */
    private function getCollecFiscalFiles(Request $request): array
    {

        $page = $request->query->get('page', 1);
        $itemsPerPage = $request->query->get('itemsPerPage', 10);
        $myProvider = $request->query->get('myProvider', null);
        $context = $request->query->get('context', null);
        if (is_null($myProvider)) {
            throw new Exception("O parâmetro 'myProvider' deve ser informado.");
        }

        $filesEtt = $this->manager->getRepository(Filesb::class)->getFilesCollection($myProvider, $context);
        if (empty($filesEtt)) {
            throw new Exception("Nenhum registro encontrado para empresa selecionada de ID: $myProvider");
        }
        $qtdFiles = count($filesEtt);
        $data = array();

        foreach ($filesEtt as $key => $val) {
            $data[$key]['id'] = $val['id'];
            $data[$key]['type'] = $val['type'];
            $data[$key]['name'] = $val['name'];
            $data[$key]['company'] = $val['company'];
            $data[$key]['status'] = [
                'id' =>    $val['status_id'],
                'status' =>    $val['status'],
                'real_status' =>    $val['real_status'],
                'color' =>    $val['color'],
            ];
            $data[$key]['date_period'] = $val['date_period'];
            $data[$key]['file_name_guide'] = $val['file_name_guide'];
            $data[$key]['file_name_receipt'] = $val['file_name_receipt'];
        }
        $ret['response']['data'] = $data;
        $ret['response']['count'] = $qtdFiles;
        $ret['response']['success'] = true;
        $ret['response']['message'] = 'Coleção recuperada';

        return $ret;
    }

    /**
     * Atualiza Registro Único utilizando o método POST
     *
     * Method=POST
     * Route=/filesb/{id}
     *
     * @param Request $request
     * @param $id
     * @return array
     * @throws Exception
     */
    private function postUpdateUniqueDocFiles(Request $request, $id): array
    {

        /**
         * @var Files $filesEtt
         */
        $filesEtt = $this->manager->getRepository(Filesb::class)->find($id);
        if (empty($filesEtt)) {
            throw new Exception("Registro 'files' com o ID: $id, não foi encontrado.");
        }

        $file = $request->files->get('file'); // Arquivo da Guia, para o modo de atualização não é obrigatório
        $fileb = $request->files->get('fileb'); // Parâmetro do arquivo do Recibo de Pagamento, não é obrigatório

        $dataPost = $request->get('data', null);
        if (!is_null($dataPost)) {
            $dataPost = json_decode($dataPost, true);
            if (is_null($dataPost)) {
                throw new Exception("O Parâmetro 'data' deve incluir um JSON válido");
            }
        }

        // --------- No modo de atualização, não obriga os campos a serem informados e atualiza somente o que é repassado

        $status = $dataPost['status'] ?? null;
        $type = $dataPost['type'] ?? null;
        $name = $dataPost['name'] ?? null;
        $detachReceipt = $dataPost['detach_receipt'] ?? null;
        $peopleId = $dataPost['people_id'] ?? null;
        $datePeriod = $dataPost['date_period'] ?? null;

        $this->manager->getConnection()->beginTransaction();

        if (!is_null($peopleId)) {
            if (!is_numeric($peopleId)) {
                throw new Exception("O Parâmetro 'people_id' ID: $peopleId, deve ser um valor numérico");
            }
            $filesEtt->setPeopleId($peopleId);
            // ---------- Verifica se peopleId encontra um dado válido
            $peopleEtt = $this->manager->getRepository(People::class)->find($peopleId);
            if (empty($peopleEtt)) {
                throw new Exception("O Parâmetro 'people_id' ID: $peopleId, não encontrou uma empresa válida.");
            }
        } else {
            $peopleId = $filesEtt->getPeopleId();
        }
        if (!is_null($type)) { // 'imposto', 'declaracao'

            $filesEtt->setType($type);
        }
        if (!is_null($name)) { // 'das', 'pis', 'confins'

            $filesEtt->setName($name);
        }
        if (!is_null($status)) { // 'sim', 'nao'
            $filesEtt->setStatus($this->manager->getRepository(Status::class)->find($status));
        }
        if (!is_null($datePeriod)) { // '10/2021' MM/AAAA
            $datePediodStr = $this->valFullStringDateOrOnlyMonthAndYear($datePeriod);
            /**
             * @var DateTimeInterface $datePeriod
             */
            $filesEtt->setDatePeriod(DateTime::createFromFormat('Y-m-d', $datePediodStr));
        }

        $fileNameGuideBD = null;
        $fileNameReceipt = null;

        if (!is_null($file)) { // Arquivo da GUIA, se existir, atualiza
            $filesEtt->setContent($file->getContent());
        }
        if (!is_null($fileb)) { // Arquivo do RECIBO, se existir, atualiza
            $filesEtt->setContent($fileb->getContent());
        }

        if ($detachReceipt === 'sim') { // Solicitação para desanexar o recibo
            $filesEtt->setFileNameReceipt(null);
        }

        // ------------------------------------------------------------------

        // dump($peopleId);

        $ret['response']['data']['company_id'] = $filesEtt->getCompanyId();
        $ret['response']['data']['id'] = $id;
        $ret['response']['data']['empresa']['id'] = $peopleId;
        $ret['response']['data']['empresa']['name'] = $this->generateCompanyName($peopleId);
        $ret['response']['data']['type'] = $type;
        $ret['response']['data']['name'] = $name;
        $ret['response']['data']['date_period'] = $datePeriod;
        $ret['response']['data']['status'] = $status;
        $ret['response']['data']['file_name_guide'] = $fileNameGuideBD;
        $ret['response']['data']['file_name_receipt'] = $fileNameReceipt;
        $ret['response']['data']['detach_receipt'] = $detachReceipt;
        $ret['response']['count'] = 1;
        $ret['response']['success'] = true;
        $ret['response']['message'] = 'Dados salvos com sucesso.';

        $this->manager->persist($filesEtt);
        $this->manager->flush();
        $this->manager->getConnection()->commit(); // Comita caso não ocorra nenhum erro

        return $ret;
    }

    /**
     * @throws Exception
     */
    private function generateCompanyName($peopleId): string
    {

        /**
         * @var People $peopleEtt
         */
        $peopleEtt = $this->manager->getRepository(People::class)->find($peopleId);
        if (empty($peopleEtt)) {
            throw new Exception("Registro 'people' da empresa de ID: $peopleId, não foi encontrado.");
        }
        $peopleName = $peopleEtt->getName();
        $peopleDocument = $peopleEtt->getOneDocument()->getDocument();
        $peopleDocument = Formatter::document($peopleDocument);
        return "$peopleName - $peopleDocument";
    }

    /**
     * Method=GET
     * Route=/filesb/{id}
     *
     * @param $id
     * @param bool $fullPath ('true' retorna os nomes dos arquivos de Guia e Recibo com os caminhos completos)
     * @return array
     * @throws Exception
     */
    private function getUniqueDocFiles($id, bool $fullPath): array
    {

        /**
         * @var Files $filesEtt
         */
        $filesEtt = $this->manager->getRepository(Filesb::class)->find($id);
        if (empty($filesEtt)) {
            throw new Exception("Registro 'files' com o ID: $id, não foi encontrado.");
        }
        $companyId = $filesEtt->getCompanyId();
        $peopleId = $filesEtt->getPeopleId();
        $type = $filesEtt->getType();
        $name = $filesEtt->getName();
        $status = $filesEtt->getStatus();
        $datePeriod = $filesEtt->getDatePeriod()->format('m/Y');
        $fileNameGuide = $filesEtt->getFileNameGuide();
        $fileNameReceipt = $filesEtt->getFileNameReceipt();

        $empresaNome = $this->generateCompanyName($peopleId);

        if (!$fullPath) {
            // -------------- Retira o path dos arquivos de recibo e guia e deixa somente os nomes dos arquivos
            if (!is_null($fileNameGuide)) {
                preg_match("/(.*?)\/([^\/]*?)$/", $fileNameGuide, $piece); // Pega o conteúdo após a última barra
                $fileNameGuide = $piece[2];
            }
            if (!is_null($fileNameReceipt)) {
                preg_match("/(.*?)\/([^\/]*?)$/", $fileNameReceipt, $piece2); // Pega o conteúdo após a última barra
                $fileNameReceipt = $piece2[2];
            }
        }

        $ret['response']['data']['company_id'] = $companyId;
        $ret['response']['data']['id'] = $id;
        $ret['response']['data']['empresa']['id'] = $peopleId;
        $ret['response']['data']['empresa']['name'] = $empresaNome;
        $ret['response']['data']['type'] = $type;
        $ret['response']['data']['name'] = $name;
        $ret['response']['data']['date_period'] = $datePeriod;
        $ret['response']['data']['status'] = $status;
        $ret['response']['data']['file_name_guide'] = $fileNameGuide;
        $ret['response']['data']['file_name_receipt'] = $fileNameReceipt;
        $ret['response']['count'] = 1;
        $ret['response']['success'] = true;
        $ret['response']['message'] = '';

        return $ret;
    }

    /**
     * Method=POST
     * Route=/filesb
     *
     * Cria registros na tabela "docs"
     *
     * data {
     *      people_id {323} opcional caso repasse o 'document'
     *      document {'19.810.123/0001-16'} opcional caso não repasse o 'people_id'
     *      type {'imposto','declaracao'} fixo
     *      name {'das','pis','confins'} fixo
     *      date_period {'10/10/2021','09/2021'} variável
     *      status {'nao','sim'} fixo (Opcional)
     * }
     * @throws ConnectionException
     * @throws Exception
     */
    private function postCreateDocFiles(Request $request): array
    {

        $file = $request->files->get('file'); // Arquivo da Guia
        if (is_null($file)) {
            throw new Exception("O Parâmetro 'file' deve obrigatoriamente incluir o arquivo da Guia a ser enviado");
        }
        $dataPost = $request->get('data', null);
        $dataPost = json_decode($dataPost, true);
        if (is_null($dataPost)) {
            throw new Exception("O Parâmetro 'data' deve incluir um JSON válido");
        }

        $fileb = $request->files->get('fileb'); // Parâmetro do arquivo do Recibo de Pagamento, não é obrigatório


        $status = $this->manager->getRepository(Status::class)->find($dataPost['status']);

        // ---------------------------------
        $document = $dataPost['document'] ?? null;
        $peopleName = null;
        $peopleId = null;

        // ---------------- Valida o parâmetro opcional 'document'
        if (!is_null($document)) { // Quando 'document' contiver um CNPJ
            $document = str_replace(array('.', '/', '\\', '-'), '', $document); // Remove .,/,-
            $people = $this->manager->getRepository(Filesb::class)->getPeopleByDocumentTypeCNPJ($document);
            if (!empty($people)) { // Quando o 'document' CNPJ encontra com sucesso uma empresa
                $peopleName = $people[0]['name'];
                $peopleId = $people[0]['id'];
                $document = Formatter::document($document);
            } else {
                throw new Exception("O CNPJ Infomado em 'document' não encontrou uma empresa cadastrada");
            }
        }

        if ($document === null) { // Quando não houver um 'document' CNPJ, o parâmetro 'people_id' se torna obrigatório
            // ---------------- Valida o parâmetro opcional 'people_id'
            $peopleId = $dataPost['people_id'] ?? null;
            if (is_null($peopleId)) {
                throw new Exception("Quando não houver o parâmetro 'document' com um CNPJ, deve existir o parâmetro 'people_id' com o ID da empresa");
            } else { // Quando o parâmetro 'people_id' é iformado, tenta localizar uma empresa válida e ativa
                $people = $this->manager->getRepository(Filesb::class)->getValidPeopleNameAndDocumentByID($peopleId);
                if (!empty($people)) { // Quando o 'id' encontra com sucesso uma empresa
                    $peopleName = $people[0]['name'];
                    $document = $people[0]['document'];
                } else {
                    throw new Exception("O parâmetro 'people_id' não encontrou uma empresa válida");
                }
            }
        }

        // ---------------- Valida o parâmetro obrigatório 'company_id'
        $companyId = $dataPost['company_id'] ?? null;
        if (is_null($companyId)) {
            throw new Exception("O parâmetro 'company_id' deve ser informado");
        }
        $findOk = $this->findMyIdInMyCompanies($companyId);
        if (is_null($findOk)) { // Quando não encontrar uma compania válida
            throw new Exception("O parâmetro 'company_id' não encontrou uma empresa válida");
        } else {
            $companyName = $findOk['companyName'];
        }
        // ----------------------------------------------------------------

        $type = $dataPost['type'];
        $name = $dataPost['name'];


        $date_pediod = $dataPost['date_period'] ?? '';
        $date_pediod = $this->valFullStringDateOrOnlyMonthAndYear($date_pediod);

        // ------------------------------- Adiciona na base de dados tabela "docs" um novo registro
        $this->manager->getConnection()->beginTransaction();
        $audF = new Filesb();
        $audF->setType($type);
        $audF->setName($name);
        $audF->setDatePeriod(DateTime::createFromFormat('Y-m-d', $date_pediod));
        $audF->setStatus(
            $status
        );
        $audF->setPeopleId($peopleId);
        $audF->setCompanyId($companyId);
        $this->manager->persist($audF);
        $this->manager->flush();
        $lastId = $audF->getId(); // Pega o último ID incrementado antes mesmo do commit
        $fileNameGuideBD = $this->moveFileToServerPath($lastId, $file, 0); // Move o PDF da Guia para a pasta no servidor

        if (!is_null($fileb)) { // Se existir um PDF do Recibo, o que não é obrigatório
            $fileNameReceiptBD = $this->moveFileToServerPath($lastId, $fileb, 1); // Move o PDF da Guia para a pasta no servidor
            $receiptExists = 'sim';
        } else {
            $fileNameReceiptBD = null;
            $receiptExists = 'nao';
        }

        // ------------------ Atualiza o campo 'file_name_guide' após capturar o ID incrementado
        $updateFileName = $this->manager->getRepository(Filesb::class)->find($lastId);
        if (!empty($updateFileName)) {
            $updateFileName->setFileNameGuide($fileNameGuideBD);
            if ($fileNameReceiptBD !== null) {
                $updateFileName->setFileNameReceipt($fileNameReceiptBD);
            }
            $this->manager->persist($updateFileName);
            $this->manager->flush();
        }
        // --------------------------------------------------------

        preg_match("/(.*?)\/([^\/]*?)$/", $fileNameGuideBD, $piece); // Pega o conteúdo após a última barra
        $fileNameGuideBD = $piece[2];
        if ($fileNameReceiptBD !== null) {
            preg_match("/(.*?)\/([^\/]*?)$/", $fileNameReceiptBD, $piece2); // Pega o conteúdo após a última barra
            $fileNameReceiptBD = $piece2[2];
        }

        $ret['response']['data']['id'] = $lastId;
        $ret['response']['data']['type'] = $type;
        $ret['response']['data']['name'] = $name;
        $ret['response']['data']['date_pediod'] = substr($date_pediod, 0, 7);
        $ret['response']['data']['file_guide_created'] = 'sim';
        $ret['response']['data']['file_receipt_created'] = $receiptExists;
        $ret['response']['data']['file_name_guide'] = $fileNameGuideBD;
        $ret['response']['data']['file_name_receipt'] = $fileNameReceiptBD;
        $ret['response']['data']['status'] = [
            'status' => $status->getStatus(),
            'id' => $status->getId(),
            'real_status' => $status->getRealStatus(),
            'color' => $status->getColor(),
        ];
        $ret['response']['data']['company_id'] = $companyId;
        $ret['response']['data']['company_name'] = $companyName;
        $ret['response']['data']['people_id'] = $peopleId;
        $ret['response']['data']['people_name'] = $peopleName;
        $ret['response']['data']['people_document'] = $document;
        $ret['response']['count'] = 1;
        $ret['response']['success'] = true;
        $ret['response']['message'] = 'Registro <strong>Criado</strong> com Sucesso.';

        $this->manager->getConnection()->commit(); // Comita caso não ocorra nenhum erro

        return $ret;
    }

    /**
     * @Route("/docs_files/search-people", name="docs_files_search_people", methods={"GET"})
     */
    public function searchPeopleByCnpjOrName(Request $request): JsonResponse
    {

        $search = $request->query->get('search', null);
        $search = str_replace(array('.', '/', '\\', '-'), '', $search); // Remove .,/,- da busca por CNPJ

        $sql = "select distinct p.id, p.name,
        IF(CHAR_LENGTH(doc.document) = 14, doc.document, CONCAT('0', doc.document)) AS document
        from people as p LEFT JOIN document doc
        ON doc.id = (SELECT id FROM document WHERE document_type_id = 3 AND people_id = p.id LIMIT 1)
        where p.enable='1' and length(doc.document) > 4 and p.people_type='J' and (p.name like '%$search%' or doc.document like '%$search%')
        limit 8;
        ";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('name', 'name', 'string');
        $rsm->addScalarResult('document', 'document', 'string');

        $nqu = $this->manager->createNativeQuery($sql, $rsm);
        $result = $nqu->getArrayResult();
        $resulta = array(); // Resultado do $result formatado

        foreach ($result as $chave => $valor) { // Formata o CNPJ
            $name = trim($valor['name']);
            $document = trim($valor['document']);
            $resulta[$chave]['id'] = $valor['id'];
            if (!is_null($document)) { // Se houver um documento válido
                $document = Formatter::document($document);
                $resulta[$chave]['name'] = "$name - $document";
            } else { // Existem condições em que documento retorna NULL e então "Formatter::" não pode ser chamado
                $resulta[$chave]['name'] = $name;
            }
        }

        $ret['response']['data']['members'] = $resulta;
        $ret['response']['data']['total'] = count($result);
        $ret['response']['success'] = true;

        return new JsonResponse($ret, 200);
    }
}
