<?php

namespace App\Controller;

use ControleOnline\Entity\Email;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\OrderLogistic;
use ControleOnline\Entity\OrderLogisticSurveys;
use ControleOnline\Entity\OrderLogisticSurveysFiles;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\Task;
use ControleOnline\Entity\TasksSurveys;
use ControleOnline\Entity\TasksSurveysFiles;
use App\Library\Exception\MissingDataException;
use App\Library\Utils\Str;
use DateTime;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Imagick;
use ImagickException;
use PhpCsFixer\Console\Report\FixReport\JsonReporter;
use phpDocumentor\Reflection\Types\Object_;
use RuntimeException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use function PHPUnit\Framework\isNull;

class OrderLogisticSurveysController extends AbstractController
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
    private $appKernel;

    public function __construct(EntityManagerInterface $entityManager, Security $security, KernelInterface $appKernel)
    {
        $this->manager = $entityManager;
        $this->security = $security;
        $this->appKernel = $appKernel;
        // $this->checkApiToken(); // Usado somente quando não está utilizando '@ApiResource'
    }

    /**
   * 
   * @Route("/order_logistic_surveys/{id_survey}/{token_url}/allfilesimages","GET")
   */
    public function getAllFilesFromGalleryBySurveyIdAndToken(Request $request): JsonResponse
    {
        try {
            $ret = array();

            $surveyId =$request->get('id_survey');
            $tokenUrl =$request->get('token_url');

            $this->checkOrderLogisticSurveysIfExists($surveyId, $tokenUrl);

            $surveysFilesEtt = $this->manager->getRepository(OrderLogisticSurveysFiles::class)->getAllPhotosFromSurveyId($surveyId);
            if (empty($surveysFilesEtt)) {
                throw new Exception("Nenhum registro de Files para a Vistoria de ID: $surveyId", 344);
            }
            $qtdFiles = count($surveysFilesEtt);

            $ret['response']['data'] = $surveysFilesEtt;
            $ret['response']['count'] = $qtdFiles;
            $ret['response']['success'] = true;
            $ret['response']['message'] = 'Galeria de filens da vistoria retornada com êxito.';

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

    /**
   * 
   * @Route("/order_logistic_surveys/{surveyId}/{fileId}/viewphoto/{type}","GET")
   */
    public function viewPhotoRealSizeOrThumb(Request $request): Response
    {
        try {
            $surveyId = $request->get('surveyId');
            $fileId = $request->get('fileId');
            $type = $request->get('type');

            $surveyFilesEtt = $this->manager->getRepository(OrderLogisticSurveysFiles::class)->findOneBy(['id' => $fileId, 'order_logistic_surveys_id' => $surveyId]);
            if (empty($surveyFilesEtt)) {
                throw new RuntimeException("O Registro 'order_logistic_surveys_files' de ID: $fileId e com 'order_logistic_surveys' de ID: $surveyId, não foi localizado.");
            }
   
            $response = new Response();
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->addCacheControlDirective('max-age', 900);
            $response->headers->addCacheControlDirective('s-maxage', 900);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('public', true);
            $response->headers->removeCacheControlDirective('private');
            $response->headers->set('Content-Type', 'file/jpeg');
            $response->setContent($surveyFilesEtt->getContent());
            return $response;
        } catch (\Exception $e) {
            $response = new Response($e->getMessage(), $e->getCode());
            $response->headers->set('Content-Type', 'text/plain');
            return $response;;
        }
    }

    /**
   * 
   * @Route("/order_logistic_surveys/{id_local}/{token_url}/filesimages","POST")
   */
    public function uploadFileVehicleSurvey(Request $request): JsonResponse
    {
        try {
            $surveyId = $request->get('id_local');
            $tokenUrl = $request->get('token_url');
            $file = $request->files->get('file');
            // dd($file);
            
            // return new JsonResponse(['id' => $surveyId, 'token' => $tokenUrl]);
            // dd($surveyId);
            $surveyEtt = $this->manager->getRepository(OrderLogisticSurveys::class)->findOneBy(['id' => $surveyId, 'token_url' => $tokenUrl]);
            if (empty($surveyEtt)) {
                throw new RuntimeException("O Registro 'order_logistic_surveys' de 'id': $surveyId e 'token_url': $tokenUrl não foi localizado.", 311);
            }

            // --------------------- Antes de Salvar os Dados, verifica se a vistoria está com status de 'Cancelado' ou 'Completo'
            $this->checkStatusSurveyIsCompleteOrCanceled($surveyEtt, true);
            // ------------------------------------------------------------------------------------

            // --------------- Valida MimeType real do arquivo independete da extensão
            $realMimeType = $file->getMimeType();

            // return new JsonResponse(['id' => $realMimeType]);

            $mimetypeAccepted = array('image/png', 'image/jpeg'); // MimeTypes Aceitos
            if (!in_array($realMimeType, $mimetypeAccepted, true)) {
                throw new RuntimeException("O mimetype real do arquivo não é um 'JPG' ou um 'PNG'");
            }
            // --------------- Insere o Registro da Imagem na tabela 'tasks_surveys_files'
            $surveyFilesAdd = new OrderLogisticSurveysFiles();
            $surveyFilesAdd->setOrderLogisticSurveysId($surveyId);
            $this->manager->persist($surveyFilesAdd);
            $this->manager->flush();
            $surveyFilesId = $surveyFilesAdd->getId();
            // --------------- Move o Arquivo da imagem para pasta /data e cria uma miniatura
            // --------------- Atualiza o Nome do Arquivo + Path no novo registo inserido no BD
            $surveyFilesAdd->setContent($file->getContent());

            $this->manager->persist($surveyFilesAdd);
            $this->manager->flush();
            // -----------------------------------------------------------------------

            $ret = array();

            $ret['response']['data']['survey_id'] = $surveyId;
            $ret['response']['data']['survey_files_id'] = $surveyFilesId;
            $ret['response']['count'] = 1;
            $ret['response']['success'] = true;
            $ret['response']['message'] = 'Upload de Photo concluído com Êxito';

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

   

    /**
   *
   * @Route("/order_logistic_surveys/surveys","GET")
   */
    public function getOneSurveyByIdAndToken(Request $request): JsonResponse
    {
        try {
            $id = $request->get('id');
            $tokenUrl = $request->get('token');
            
            $ret = array();
            /**
             * @var OrderLogisticSurveys $surveyEtt
             */
            $surveyEtt = $this->checkOrderLogisticSurveysIfExists($id, $tokenUrl);

            $data['type_survey'] = $surveyEtt->getTypeSurvey();
            $data['created_at'] = $surveyEtt->getCreatedAt()->format('d/m/Y - H:i');
            if ($surveyEtt->getUpdatedAt() !== null) {
                $data['updated_at'] = $surveyEtt->getUpdatedAt()->format('d/m/Y - H:i');
            } else {
                $data['updated_at'] = null;
            }
            $surveyorId = $surveyEtt->getSurveyorId();
            $orderLogisticId = $surveyEtt->getOrderLogistcId();
            // ------------------ Capturando dados da Order Logistic
            /**
             * @var OrderLogistic $OrderLogisticEtt
             */
            $orderLogisticEtt = $this->manager->getRepository(OrderLogistic::class)->find($orderLogisticId);
            if (empty($orderLogisticEtt)) {
                throw new RuntimeException("Registro Order Logistic com o ID: $orderLogisticId, não foi encontrado.");
            }

            // ------------------ Capturando dados da Order
            $orderId = $orderLogisticEtt->getOrder()->getId();
            $orderEtt = $this->manager->getRepository(Order::class)->find($orderId);
            // dd($orderEtt->getAddressDestination());
            if (empty($orderEtt)) {
                throw new RuntimeException("Registro 'Orders' com o ID: $orderId, não foi encontrado.");
            }
            // dd($orderEtt->getOtherInformations());
            $data['car_inf'] = $orderEtt->getOtherInformations(true);
            $data['car_type'] = $orderEtt->getProductType();
            $data['clientName'] = $orderEtt->getClient()->getName();
            if ($orderEtt->getClient()->getEmail()) {
                $data['clientEmail'] = $orderEtt->getClient()->getEmail()[0]->getEmail();
            }
            // ------------------ Capturando dados People do Vistoriador
            if ($surveyorId !== null) {
                $peopleEtt = $this->manager->getRepository(People::class)->find($surveyorId);
                if (empty($peopleEtt)) {
                    throw new RuntimeException("Registro Vistoriador 'people' com o ID: $surveyorId, não foi encontrado.");
                }
                $data['surveyor_people_id'] = $surveyorId;
                $data['surveyor_name'] = $peopleEtt->getName();
                $data['surveyor_email'] = $peopleEtt->getOneEmail()->getEmail();
            } else {
                $data['surveyor_people_id'] = null;
                $data['surveyor_name'] = null;
                $data['surveyor_email'] = null;
            }
            // ------------------------------------------------------------------------------------------

            $data['status'] = $surveyEtt->getStatus();
            $data['vehicle_km'] = $surveyEtt->getVehicleKm();
            $data['belongings_removed'] = $surveyEtt->getBelongingsRemoved();
            $data['selectedTrainedId'] = $surveyEtt->getProfessionalId();
            $data['selectedAddressId'] = $surveyEtt->getAddressId();
            $data['group'] = json_decode($surveyEtt->getOtherInformations(true), false);
            $data['comments'] = $surveyEtt->getComments();

            $ret['response']['data'] = $data;
            $ret['response']['count'] = 1;
            $ret['response']['success'] = true;
            $ret['response']['message'] = 'Vistoria de ID: ' . $id . ' teve os dados recuperados com êxito.';
            // dd($data);
            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

    /**
     * @param $surveyId
     * @param $tokenUrl
     * @return void
     */
    private function checkOrderLogisticSurveysIfExists($surveyId, $tokenUrl): OrderLogisticSurveys
    {
        $surveyEtt = $this->manager->getRepository(OrderLogisticSurveys::class)->findOneBy(['id' => $surveyId, 'token_url' => $tokenUrl]);
        if (empty($surveyEtt)) {
            throw new RuntimeException("Registro 'order_logistics_surveys' com o 'id': $surveyId e 'token_url': $tokenUrl , não foi encontrado.");
        }
        return $surveyEtt;
    }

    /**
     * @param OrderLogisticSurveys $surveyEtt
     * @param bool $photos
     * @return void
     */
    private function checkStatusSurveyIsCompleteOrCanceled(OrderLogisticSurveys $surveyEtt, bool $photos = false): void
    {
        $msgError = $photos ? "Enviar Fotos" : "Salvar";
        $arrTmp = array();
        $arrTmp['canceled'] = 'Cancelado';
        $arrTmp['complete'] = 'Completo';
        $statusSurvey = $surveyEtt->getStatus();
        if ($statusSurvey === 'canceled' || $statusSurvey === 'complete') {
            throw new RuntimeException("Erro - Vistoria não pode $msgError, Status está como: {$arrTmp[$statusSurvey]}", 253);
        }
    }

    /**
   *
   * @Route("/order_logistic_surveys/{order_logistic_id}/{token_url}/surveys/update","PUT")
   */
    public function updateSurveyByIdAndToken(Request $request): JsonResponse
    {
        try {
            $ret = array();
            $id = $request->get('order_logistic_id');
            $tokenUrl = $request->get('token_url');
            $data = json_decode($request->getContent(), false);

            /**
             * @var OrderLogisticSurveys $surveyEtt
             */
            $surveyEtt = $this->checkOrderLogisticSurveysIfExists($id, $tokenUrl);

            // --------------------- Antes de Salvar os Dados, verifica se a vistoria está com status de 'Cancelado' ou 'Completo'
            $this->checkStatusSurveyIsCompleteOrCanceled($surveyEtt, false);
            // ------------------------------------------------------------------------------------

            $this->manager->getConnection()->beginTransaction();

            // ------------- Localiza o E-Mail do Vistoriador no BD e verifica se ele existe
            /**
             * @var Email $emailEtt
             */
            $emailEtt = $this->manager->getRepository(Email::class)->findOneBy(['email' => $data->surveyor_email]);
            if (empty($emailEtt)) { // Se o E-Mail não for localizado, insere um novo registro People + E-Mail para o Vistoriador

                // ------------------------- Adiciona o Registro People para um vistoriador novo
                $peopleAdd = new People();
                $peopleAdd->setName($data->surveyor_name);
                $peopleAdd->setAlias($data->surveyor_name);
                $peopleAdd->setEnabled(0);
                // ----------------------------------- Atrela Idioma pt-BR ao novo registro People
                $lang = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']);
                $peopleAdd->setLanguage($lang);
                // --------------------------------------------------------
                $peopleAdd->setPeopleType('F');
                $peopleAdd->setBilling(0);
                $peopleAdd->setBillingDays('daily');
                $peopleAdd->setPaymentTerm(1);
                $peopleAdd->setIcms(1);
                $this->manager->persist($peopleAdd);
                $this->manager->flush();
                $surveyorId = $peopleAdd->getId();
                // ------------------------- Adiciona o Registro Email atrelado ao People adicionado para um vistoriador novo
                $mailAdd = new Email();
                $mailAdd->setEmail($data->surveyor_email);
                $mailAdd->setConfirmed(0);
                $mailAdd->setPeople($peopleAdd);
                $this->manager->persist($mailAdd);
                $this->manager->flush();

                $vistoriadorInserido = 'Sim';

            } else { // Se o E-Mail do Vistoriador existir no BD

                $surveyorId = $emailEtt->getPeople()->getId();
                $vistoriadorInserido = 'Não';

            }

            // -------------------- Atualiza dados da Vistoria
            $surveyEtt->setSurveyorId($surveyorId);
            $surveyEtt->setProfessionalId($data->selectedTrainedId);
            $surveyEtt->setAddressId($data->selectedAddressId);
            $surveyEtt->setTypeSurvey($data->type_survey);
            $otherJsonString = json_encode($data->group);
            $surveyEtt->setOtherInformations($otherJsonString);
            $surveyEtt->setBelongingsRemoved($data->belongings_removed);
            if ($data->vehicle_km !== null) {
                $surveyEtt->setVehicleKm($data->vehicle_km);
            } else {
                $surveyEtt->setVehicleKm('0');
            }
            $surveyEtt->setStatus('complete'); // Comentar para ambiente de DEV e testes
            $surveyEtt->setUpdatedAt(DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s')));
            if ($data->comments !== null) {
                $surveyEtt->setComments($data->comments);
            }

            // -------------------- Atualiza os dados da Galeria de Fotos - tabela 'tasks_surveys_files'

            if (!empty($data->galleryModels)) { // Atualiza somente se existir demanda de atualização de avaria ou região de foto

                foreach ($data->galleryModels->region as $chave => $valor) {
                    $objBreak = $data->galleryModels->breakdown; // Aproveita o mesmo loop da Região para capturar as Avarias
                    $idPhoto = str_replace('photoId', '', $chave);
                    $valueRegion = $valor->value;
                    $breakdownRegion = $objBreak->$chave->value;
                    $photoRegionEtt = $this->manager->getRepository(OrderLogisticSurveysFiles::class)->find($idPhoto);
                    if (empty($photoRegionEtt)) { // Se o registro de photo não for encontrado
                        throw new RuntimeException("Registro da galeria de fotos 'order_logistic_surveys_files' com o ID: $idPhoto, não foi localizado.");
                    }
                    $photoRegionEtt->setRegion($valueRegion);
                    $photoRegionEtt->setBreakdown($breakdownRegion);
                    $this->manager->persist($photoRegionEtt);
                }

            }

            // --------------------------------------------------------

            $this->manager->persist($surveyEtt);
            $this->manager->flush();
            $statusBd = $surveyEtt->getStatus();
            $data->status = $statusBd;

            $ret['response']['data'] = $data;
            $ret['response']['vistoriador_inserido'] = $vistoriadorInserido;
            $ret['response']['count'] = 1;
            $ret['response']['success'] = true;
            $ret['response']['message'] = 'Vistoria de ID: ' . $id . ' foi atualizada com êxito.';

            $this->manager->getConnection()->commit(); // Comita caso não ocorra nenhum erro

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }

            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

    /**
   *
   * @Route("/order_logistic_surveys/{surveyId}/surveys/status_update","PUT")
   */
    public function changeStatusSurveyById(Request $request, $surveyId): JsonResponse
    {
        try {
            $statusUser = $request->get('status');
            $date = new DateTime();
            $updatedTime = $date->setTimestamp($request->get('timestamp') / 1000)->format('Y-m-d H:i:s');
            $updatedTime = \DateTime::createFromFormat('Y-m-d H:i:s', $updatedTime);

            if (is_null($statusUser)) {
                throw new RuntimeException("O Parâmetro 'status' está como 'null'.");
            }

            $ret = array();

            $surveysEtt = $this->manager->getRepository(OrderLogisticSurveys::class)->find($surveyId);
            if (empty($surveysEtt)) {
                throw new RuntimeException("Registro OrderLogisticSurveys para o ID: $surveyId não existe.");
            }
            $statusBd = $surveysEtt->getStatus();
            $surveysEtt->setStatus($statusUser);
            $surveysEtt->setUpdatedAt($updatedTime);
            $this->manager->persist($surveysEtt);
            $this->manager->flush();

            $ret['response']['data']['status_bd_before'] = $statusBd;
            $ret['response']['data']['status_bd_after'] = $statusUser;
            $ret['response']['count'] = 1;
            $ret['response']['success'] = true;
            $ret['response']['message'] = "Vistoria de ID: $surveyId teve o status alterado de $statusBd para $statusUser";

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

    private function cryptoRandSecure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) {
            return $min;
        }
        $log = ceil(log($range, 2));
        $bytes = (int)($log / 8) + 1; // length in bytes
        $bits = (int)$log + 1; // length in bits
        $filter = (int)(1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd &= $filter; // discard irrelevant bits
        } while ($rnd > $range);
        return $min + $rnd;
    }

    private function generateToken($length): string
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $max = strlen($codeAlphabet); // edited
        // Chegou poligonal à cavalo na cidade

        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->cryptoRandSecure(0, $max - 1)];
        }

        return $token;
    }

    /**
   *
   * @Route("/order_logistic_surveys/surveys_create","POST")
   */
    public function addNewSurveyByOrderLogisticId(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent());
            $orderLogisticId = $payload->orderLogisticId;
            $date = new DateTime();
            $createdAt = $date->setTimestamp($payload->timestamp / 1000)->format('Y-m-d H-i-s');
            $createdAt = \DateTime::createFromFormat('Y-m-d H-i-s', $createdAt);

            // --------------- Gera hash para URL
            $tokenUrl = $this->generateToken(7);
            // throw new RuntimeException("Token Gerado: $tokenUrl");
            $ret = array();

            /**
             * @var OrderLogistic $orderLogisticEtt
             */
            $orderLogisticEtt = $this->manager->getRepository(OrderLogistic::class)->find($orderLogisticId);
            if (empty($orderLogisticEtt)) {
                throw new RuntimeException("Não encontrou registro 'Order Logistic' para o orderLogisticId: $orderLogisticId");
            }
            if ($orderLogisticEtt->getOrder() === null) {
                throw new RuntimeException("'order_id' em 'Order' de ID: $orderLogisticId está como 'null'");
            }

            $this->manager->getConnection()->beginTransaction();
            $olsAdd = new OrderLogisticSurveys();
            $olsAdd->setOrderLogistcId($orderLogisticEtt);
            $olsAdd->setTokenUrl($tokenUrl);
            $olsAdd->setStatus('pending');
            $olsAdd->setCreatedAt($createdAt);
            $this->manager->persist($olsAdd);
            $this->manager->flush();
            $surveyAddId = $olsAdd->getId();
            $this->manager->getConnection()->commit(); // Comita caso não ocorra nenhum erro

            $data['order_logistic_id'] = $orderLogisticEtt;
            $data['order_logistic_surveys_id'] = $surveyAddId;
            $data['order_logistic_surveys_token_url'] = $tokenUrl;

            $ret['response']['data'] = $data;
            $ret['response']['count'] = 1;
            $ret['response']['success'] = true;
            $ret['response']['message'] = "Adicionada uma nova vistoria em 'order_logistic_surveys' com o id: '$surveyAddId' e taskId: " . $orderLogisticId;

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }

    }

    /**
   *
   * @Route("/order_logistics_surveys/surveys/{orderId}","GET")
   */
    public function getSurveyCollection($orderId): JsonResponse
    {
        try {
            $ret = array();

            $surveysEtt = $this->manager->getRepository(OrderLogisticSurveys::class)->getCollectionSurveys($orderId);

            if (empty($surveysEtt)) {
                throw new MissingDataException("Nenhum registro OrderLogisticSurveys para o orderId: $orderId", $orderId);
            }
            $qtdSurveys = count($surveysEtt);
            $data = array();

            foreach ($surveysEtt as $key => $val) {
                $data[$key]['id'] = $val['id'];
                $data[$key]['token_url'] = $val['token_url'];
                $data[$key]['client_name'] = $val['client_name'];
                $data[$key]['vehicle'] = $val['vehicle'];
                $data[$key]['date'] = $val['date'];
                $data[$key]['type_survey'] = $val['type_survey'];
                $data[$key]['status'] = $val['status'];
            }

            $allowSurvey = false;
            if ($orderId > 0) {
                $allowSurvey = true;
            }

            $ret['response']['data'] = $data;
            $ret['response']['count'] = $qtdSurveys;
            $ret['response']['allow_survey'] = $allowSurvey;
            $ret['response']['success'] = true;
            $ret['response']['message'] = "Listagem de Vistorias retornada pelo 'orderId': " . $orderId;

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

    /**
   *
   * @Route("/order_logistic_surveys/findpeopleprofessional","GET")
   */
    public function findPeopleProfessionalByDefaultCompanyId(Request $request): JsonResponse
    {
        try {
            $ret = array();
            $defaultCompanyId = $request->get('companyId');
            // dd($defaultCompanyId);

            $surveysEtt = $this->manager->getRepository(OrderLogisticSurveys::class)->getPeopleProfessionalByDefaultCompanyId($defaultCompanyId);
            // dd($surveysEtt);

            if (empty($surveysEtt)) {
                throw new RuntimeException("Nenhum registro People Professional para a default company id: $defaultCompanyId");
            }
            $qtdPeople = count($surveysEtt);
            $data = array();

            foreach ($surveysEtt as $key => $val) {
                $data[$key]['professional_id'] = $val['professional_id'];
                $data[$key]['address_id'] = $val['address_id'];
                $data[$key]['alias'] = $val['alias'];
                $data[$key]['district'] = $val['district'];
                $data[$key]['city'] = $val['city'];
                $data[$key]['UF'] = $val['UF'];
            }

            $ret['response']['data'] = $data;
            $ret['response']['count'] = $qtdPeople;
            $ret['response']['success'] = true;
            $ret['response']['message'] = 'Listagem de Pontos de Encontro (People Professional) Recuperada com Sucesso!';

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

    /**
   *
   * @Route("/order_logistic_surveys/findpsurveyorbyemail","GET")
   */
    public function findSurveyorByEmail(Request $request): JsonResponse
    {
        try {
            $ret = array();
            $email = $request->get('email');

            $surveysEtt = $this->manager->getRepository(OrderLogisticSurveys::class)->getPeopleSurveyorByExactMail($email);
            if (empty($surveysEtt)) {
                throw new RuntimeException("Nenhum registro People Surveyor para o E-MAIL: $email");
            }
            $qtdPeople = count($surveysEtt);
            $data = array();

            foreach ($surveysEtt as $key => $val) {
                $data[$key]['id'] = $val['id'];
                $data[$key]['name'] = $val['name'];
            }

            $ret['response']['data'] = $data;
            $ret['response']['count'] = $qtdPeople;
            $ret['response']['success'] = true;
            $ret['response']['message'] = 'Registro Recuperado com Sucesso';

            return new JsonResponse($ret);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            ]);
        }
    }

}
