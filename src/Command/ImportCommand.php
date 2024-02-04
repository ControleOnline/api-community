<?php

namespace App\Command;

use ControleOnline\Entity\Category;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Repository\ImportRepository;
use ControleOnline\Repository\DeliveryRegionRepository;
use ControleOnline\Repository\DeliveryTaxGroupRepository;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\InputArgument;
use ControleOnline\Service\DatabaseSwitchService;

use ControleOnline\Entity\Import;
use ControleOnline\Entity\DeliveryRegion;
use ControleOnline\Entity\DeliveryTax;
use ControleOnline\Entity\DeliveryTaxGroup;
use ControleOnline\Entity\File;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PurchasingInvoiceTax;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\Task;
use ControleOnline\Entity\TaskInteration;
use App\Service\EmailService;
use Exception;

class ImportCommand extends Command
{
    protected static $defaultName = 'app:import';

    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    private $manager  = null;


    /**
     * Import repository
     *
     * @var ImportRepository
     */
    private $imports = null;

    /**
     * DeliveryRegion repository
     *
     * @var DeliveryRegionRepository
     */
    private $regions = null;

    /**
     * DeliveryRegion repository
     *
     * @var DeliveryTaxGroupRepository
     */
    private $groups = null;

    private $importObject = [];

    /**     
     * @var OutputInterface
     */
    private $output;

    /**
     * App Kernel
     *
     * @var KernelInterface
     */
    private $appKernel;

    private $importType;

    private $error = null;

    /**
     * Entity manager
     *
     * @var DatabaseSwitchService
     */
    private $databaseSwitchService;

    public function __construct(EntityManagerInterface $entityManager,        KernelInterface $appKernel, DatabaseSwitchService $databaseSwitchService)
    {
        $this->manager   = $entityManager;
        $this->appKernel = $appKernel;
        $this->imports   = $this->manager->getRepository(Import::class);
        $this->regions   = $this->manager->getRepository(DeliveryRegion::class);
        $this->groups    = $this->manager->getRepository(DeliveryTaxGroup::class);
        $this->databaseSwitchService = $databaseSwitchService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import items with "waiting" status in the imports table.')
            ->setHelp('This command import data to tables.');

        $this->addArgument('importType', InputArgument::REQUIRED, 'Número limite de importações');
        $this->addArgument('limit', InputArgument::OPTIONAL, 'Número limite de importações');
    }

    protected function throw($e)
    {
        $this->error = $e;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $domains = $this->databaseSwitchService->getAllDomains();
        foreach ($domains as $domain) {
            $this->databaseSwitchService->switchDatabaseByDomain($domain);

            $this->output = $output;
            $this->importType = $input->getArgument('importType');
            $this->limit = $input->getArgument('limit') ?: 100;




            $this->output->writeln([
                '',
                '=========================================',
                'Starting (limit ' . $this->limit . ')...',
                '=========================================',
                '',
            ]);

            try {
                $this->manager->getConnection()->beginTransaction();
                $this->startJobs();
                $this->manager->flush();
                $this->manager->getConnection()->commit();
            } catch (\Exception $e) {
                if ($this->manager->getConnection()->isTransactionActive())
                    $this->manager->getConnection()->rollBack();

                $this->error = $e->getMessage();

                $this->output->writeln([
                    '',
                    'Main Error: ' . $e->getMessage(),
                    '',
                ]);
            }

            $this->output->writeln([
                '',
                '=========================================',
                'End',
                '=========================================',
                '',
            ]);
        }
        return 0;
    }

    public function __destruct()
    {
        if ($this->error)
            $this->changeStatus('failed', $this->error);
    }

    private function changeStatus(string $newStatus, string $newFeedback = '')
    {


        if (!empty($this->importObject)) {

            $this->manager->getConnection()->beginTransaction();

            /**
             * @var Import $importEntity
             */
            $importEntity = $this->manager->getRepository(Import::class)->find($this->importObject['id']);


            $importEntity->setStatus($newStatus);
            $importEntity->setFeedback($newFeedback);

            $this->importObject['status']   = $newStatus;
            $this->importObject['feedback'] = $newFeedback;

            $this->manager->persist($importEntity);

            $this->manager->flush($importEntity);
            $this->manager->getConnection()->commit();


            $this->output->writeln([
                '',
                'import (#' . $importEntity->getId() . ') Updating status from ' . $this->importObject['status'] . ' to ' . $newStatus . '...',
                '',
            ]);
        }
    }

    private function findOrCreateRegion(People $carrier, string $regionName): DeliveryRegion
    {
        $fixedName = trim(preg_replace('/\\s\\s+/', ' ', $regionName));

        if (!$fixedName) {
            return null;
        }

        $region = $this->regions->findOneBy(array(
            'people' => $carrier,
            'region' => $fixedName
        ));


        if (empty($region)) {

            $region = new DeliveryRegion();

            $region->setPeople($carrier);
            $region->setRegion($fixedName);
            $region->setDeadline(0);
            $region->setRetrieveTax(0);

            $this->manager->persist($region);
            $this->manager->flush($region);
        }

        return $region;
    }

    /**
     * @return DeliveryTaxGroup
     */
    private function getGroupById(int $groupId)
    {
        return $this->groups->find($groupId);
    }

    private function getCSVFromFile(File $fileEntity): array
    {
        $pathRoot  = $this->appKernel->getProjectDir();


        $contents = $fileEntity->getContent();

        $contents = mb_convert_encoding(
            $contents,
            'UTF-8',
            mb_detect_encoding($contents)
        );

        $rows = explode("\n", str_replace("\r", "", $contents));

        $csvArray = [];

        foreach ($rows as $row) {
            $csvRow = str_getcsv($row, ",");

            foreach ($csvRow as $key => $col) {
                if (is_numeric($col)) {
                    $floatVal = floatval($col);

                    if ($floatVal && intval($floatVal) != $floatVal) {
                        $csvRow[$key] = $floatVal;
                    } else {
                        $csvRow[$key] = (int) $col;
                    }
                }
            }

            if (!empty($csvRow) && !empty($csvRow[0])) {
                $csvArray[] = $csvRow;
            }
        }

        $csvHeaders = [];
        $objectArray = [];

        // get csv headers
        foreach ($csvArray[0] as $header) {
            $csvHeaders[] = $header;
        }

        array_shift($csvArray);

        foreach ($csvArray as $line) {
            $lineObject = [];

            foreach ($csvHeaders as $key => $header) {
                $lineObject[$header] = $line[$key];
            }

            $objectArray[] = $lineObject;
        }

        return $objectArray;
    }

    /**
     * @param array $this->importObject
     * @return File
     */
    private function getFileEntity()
    {
        $fileRepo = $this->manager->getRepository(File::class);

        return $fileRepo->find($this->importObject['fileId']);
    }

    private function getCSVFromImport(): array
    {

        $fileEntity = $this->getFileEntity();

        $csvArray = $this->getCSVFromFile($fileEntity);

        return $csvArray;
    }







    private function getXMLFromImport()
    {

        $fileEntity = $this->getFileEntity();
        $pathRoot  = $this->appKernel->getProjectDir();


        return  $fileEntity->getContent();
    }


    protected function uploadCTEFromMail()
    {
        $result      = [];
        EmailService::setEm($this->manager);
        EmailService::setOutput($this->output);
        try {
            EmailService::getAttachments($this->limit, 'UNSEEN');
        } catch (\Exception $e) {
            $this->throw($e->getMessage());
        }
        EmailService::close();
        return array_filter($result);
    }

    protected function addCarrierInvoiceTax(SalesOrder $order, $nf)
    {
        $carrierInvoiceTax = null;

        if (!$nf) {
            return;
        }

        /*
      * @todo "Como achar o documento de quem emitiu a NF?"
      */
        //$document


        $clientInvoiceTax = $this->manager->getRepository(PurchasingInvoiceTax::class)
            ->createQueryBuilder('IT')
            ->select()
            ->innerJoin('\ControleOnline\Entity\PurchasingOrderInvoiceTax', 'OIT', 'WITH', 'OIT.invoiceTax = IT.id')
            ->innerJoin('\ControleOnline\Entity\SalesOrder', 'O', 'WITH', 'O.id = OIT.order')
            //->innerJoin('\ControleOnline\Entity\Document', 'D', 'WITH', 'D.people = O.client')
            ->where('O.id =:order')
            ->andWhere('OIT.invoiceType=:invoice_type')
            //->andWhere('O.document:document')
            ->setParameters(array(
                //'document' => $document,
                'order' => $order->getId(),
                'invoice_type' => 55
            ))->getQuery()->getResult();

        if (in_array($order->getStatus()->getStatus(), ['waiting retrieve', 'retrieved', 'analysis'])) {
            foreach ($nf->CTe->infCte->infCTeNorm->infDoc->infNFe as $nf_key) {
                $keys[] = (int) $nf_key->chave;
                $nf_number = (int) substr((string) $nf_key->chave, 25, 9);
                $nfs[] = $nf_number;
            }
            if ($clientInvoiceTax && in_array($clientInvoiceTax[0]->getInvoiceNumber(), $nfs)) {

                $key = $clientInvoiceTax[0]->getInvoiceKey();

                /**
                 * Verificar por chave da NF
                 */

                if ($order->getStatus()->getStatus() == ['analysis'] && in_array($key, $keys)) {

                    $task = new Task();
                    $task->setType('support');
                    $task->setClient($order->getClient());
                    $task->setDueDate(new \DateTime('now'));
                    $task->setOrder($order);
                    $task->setProvider($order->getProvider());

                    $category = $this->manager->getRepository(Category::class)->findOneBy(['name' => ['Material coletado sem aprovação']]);

                    /**
                     * @todo Ajustar para que este usuário seja pego automaticamente
                     * Por enquanto, adicionado manualmente o usuário da Luiza
                     */
                    $defaultPeople = $this->manager->getRepository(People::class)->find(24149);

                    if (!$category) {
                        $category = new Category();
                        $category->setName('Material coletado sem aprovação');
                        $category->setContext('support');
                        $this->manager->persist($category);
                        $this->manager->flush();
                    }
                    $task->setTaskFor($defaultPeople);
                    $task->setRegisteredBy($defaultPeople);
                    $task->setCategory($category);

                    $task->setName('[Automático] - Material coletado sem aprovação');
                    $task->setTaskStatus($this->manager->getRepository(Status::class)->findOneBy(['status' => ['closed'], 'context' => 'support']));

                    $taskInteration = new TaskInteration();
                    $taskInteration->setType('comment');
                    $taskInteration->setVisibility('private');
                    $taskInteration->setBody('DACTE recebido quando o pedido ainda estava em análise');
                    $taskInteration->setTask($task);
                    $taskInteration->setRegisteredBy($defaultPeople);

                    $this->manager->persist($taskInteration);
                    $this->manager->persist($task);
                    $this->manager->flush();
                }


                if (in_array($key, $keys)) {
                    $status = $this->manager->getRepository(Status::class)->findOneBy(['status' => 'on the way', 'context' => 'order']);
                    $order->setStatus($status);
                    $this->manager->persist($order);
                    //$this->manager->flush($order);
                    //$carrierInvoiceTax = $this->createPurchasingOrderFromSaleOrder($order, $order->getQuote()->getCarrier(), $invoice);
                    $this->manager->flush();
                }
            } else {
                $this->throw('This invoice tax is not part of this order');
            }
        } else {
            //$this->throw('Order is not in the correct status for sending invoice tax');
        }
        //return $carrierInvoiceTax;
    }

    protected function processXml($nf): array
    {
        $result = [];
        $nfs = [];
        $orders = false;
        if ($nf) {
            if (isset($nf->retEventoCTe) || $nf->CTe->infCte->ide->CFOP == 2206) {
                //EmailService::makeProcessed($filename);
            } elseif (isset($nf->CTe->infCte->infCTeNorm->infDoc->infOutros)) {
                //sEmailService::makeProcessed($filename, 'DECLARATION');
            } else {
                foreach ($nf->CTe->infCte->infCTeNorm->infDoc->infNFe as $nf_key) {
                    $nf_number = (int) substr((string) $nf_key->chave, 25, 9);
                    $nfs[]     = $nf_number;
                }
                foreach ($nf->CTe->infCte->infCTeNorm->infDoc->infNF as $nf_key) {
                    $nfs[]     = (int) $nf_key->nDoc;
                }

                if ($nfs) {
                    /**
                     * @var \ControleOnline\Repository\SalesOrderRepository $repo
                     */
                    $repo   = $this->manager->getRepository(SalesOrder::class);
                    $orders = $repo->createQueryBuilder('O')
                        ->select()
                        ->innerJoin('\ControleOnline\Entity\PurchasingOrderInvoiceTax', 'OIT', 'WITH', 'O.id = OIT.order')
                        ->innerJoin('\ControleOnline\Entity\SalesInvoiceTax', 'IT', 'WITH', 'IT.id = OIT.invoiceTax')
                        ->andWhere('OIT.invoiceType =:invoice_type')
                        ->andWhere('IT.invoiceNumber IN (:invoice)')
                        ->andWhere('O.status IN (:status)')
                        ->setParameters([
                            'invoice_type' => 55,
                            'invoice'      => $nfs,
                            'status'  => $this->manager->getRepository(Status::class)->findBy(['status' => ['analysis', 'waiting retrieve', 'retrieved', 'on the way', 'delivered'], 'context' => 'order']),

                        ])
                        ->groupBy('O.id')
                        ->getQuery()
                        ->getResult();
                }


                /*
                if (count($orders) == 0)
                    EmailService::makeProcessed($filename, 'ERROR');
                else
                    EmailService::makeProcessed($filename);
                */

                foreach ($orders as $order) {
                    //EmailService::removeProcessed($filename);
                    if (!in_array($order->getStatus()->getStatus(), ['on the way', 'delivered'])) {
                        $result[] = [
                            'order'       => $order,
                            'attachment'  => $nf
                        ];
                    }
                }
            }
        }
        return array_filter($result);
    }

    private function getImports($importType): array
    {
        $importing = $this->imports->getAllImports([
            'status' => 'importing',
            'import_type' => $importType
        ], [
            'from' => 0,
            'limit' => $this->limit
        ]);

        if (!empty($importing) && count($importing) > 0) {

            $this->output->writeln([
                '',
                'There is job in progress: #' . $importing[0]['id'],
                '',
            ]);

            return [];
        }

        $imports = $this->imports->getAllImports([
            'status' => 'waiting',
            'import_type' => $importType
        ], [
            'from' => 0,
            'limit' => $this->limit
        ]);

        return $imports;
    }

    private function getBoolFromString(string $text)
    {
        $text = trim($text);

        if (is_numeric($text)) {
            if ($text === 0 || $text === '0') {
                return false;
            } else if ($text === 1 || $text === '1') {
                return true;
            }
        } else {
            $text = mb_strtolower($text);

            $yesLabels = [
                'y', 'yes', 's', 'sim', 'si',
                'true', 't', 'verdadeiro', 'verdade', 'v',
                'certo', 'correto'
            ];

            if (in_array($text, $yesLabels)) {
                return true;
            } else {
                $noLabels = [
                    'n', 'no', 'não', 'nao',
                    'false', 'f', 'falso', 'mentira',
                    'errado', 'incorreto'
                ];

                if (in_array($text, $noLabels)) {
                    return false;
                }
            }
        }

        return null;
    }

    private function getFloatFromString(string $float, bool $isPrice = false)
    {

        if (str_contains($float, ',')) {
            if (strpos($float, ',') === strlen($float) - 2) {
                $float = str_replace('.', '', $float);
                $float = str_replace(',', '.', $float);
            } else {
                $float = str_replace(',', '', $float);
            }
        }

        return $float;
    }

    private function importLeads(array $csvObject)
    {
        print_r($csvObject);
    }

    private function importDeliveryTax(array $csvObject,        $groupId)
    {
        $importCount = 0;
        /**
         * @var People $carrier
         */
        $carrier = null;

        if (!empty($groupId) && is_numeric($groupId)) {
            $groupId = (int) $groupId;

            /**
             * @var People $carrier
             */
            $carrier = null;
            $group   = $this->getGroupById($groupId);


            if (!empty($group)) {
                $carrier = $group->getCarrier();

                // validate line
                $fixedValues = $this->validateDeliveryTax($csvObject);

                if (!empty($fixedValues) && count($fixedValues) > 0) {

                    // import tax
                    foreach ($fixedValues as $valueObject) {
                        /**
                         * @var DeliveryRegion $originRegion
                         */
                        $originRegion = null;
                        /**
                         * @var DeliveryRegion $destinationRegion
                         */
                        $destinationRegion = null;

                        if (!empty($valueObject['origin'])) {
                            $originRegion =      $this->findOrCreateRegion($carrier, $valueObject['origin']);
                        }
                        if (!empty($valueObject['destination'])) {
                            $destinationRegion = $this->findOrCreateRegion($carrier, $valueObject['destination']);
                        }

                        foreach ($valueObject['tax'] as $tax) {



                            $newTax = $this->manager->getRepository(DeliveryTax::class)->findOneBy(array(
                                'taxName' => $tax['name'],
                                'taxType' => $tax['type'],
                                'taxSubtype' => $tax['subType'] ?: null,
                                'finalWeight' => $tax['finalWeight'] ?: 0,
                                'regionOrigin' => $originRegion ?: null,
                                'regionDestination' => $destinationRegion ?: null,
                                'groupTax' => $group,

                            ));

                            if (!$newTax)
                                $newTax = new DeliveryTax();

                            if (!empty($originRegion) && !empty($destinationRegion)) {
                                $newTax->setRegionOrigin($originRegion);
                                $newTax->setRegionDestination($destinationRegion);
                            }

                            $newTax->setGroupTax($group);
                            $newTax->setPrice($tax['price']);
                            $newTax->setTaxType($tax['type']);
                            $newTax->setTaxSubtype($tax['subType'] ?: null);
                            $newTax->setTaxName($tax['name']);
                            $newTax->setFinalWeight($tax['finalWeight'] ?: 0);
                            $newTax->setOptional($tax['optional'] ?: 0);
                            $newTax->setMinimumPrice($tax['minimumPrice'] ?: 0);
                            $newTax->setTaxOrder(0);
                            $newTax->setDeadline($tax['deadline']);
                            $this->manager->persist($newTax);

                            $importCount++;
                        }
                    }
                } else if ($fixedValues === null) {
                    return null;
                }
            } else {

                $this->throw('group tax not found');


                return null;
            }
        } else {
            $this->throw('it was not possible to identify the tax group');
        }

        return $importCount;
    }

    private function validateDeliveryTax(array $csvObject)
    {
        $fixedValues = [];

        switch ($this->importObject['fileFormat']) {
            case 'csv': /* default format */
                foreach ($csvObject as $rowKey => $row) {
                    $otherCols = [];
                    $prices = [];

                    $rowValues = [
                        'tax' => []
                    ];

                    $taxName        = null;
                    $taxType        = null;
                    $taxSubType     = null;
                    $taxFinalWeight = null;
                    $taxOptional    = null;
                    $taxMinPrice    = null;
                    $taxPrice       = null;
                    $taxdeadline    = null;

                    foreach ($row as $colKey => $col) {

                        $ck = trim(preg_replace("/[\s_-]/", '', mb_strtolower($colKey)));

                        $originLabels = [
                            'origem', 'origin', 'from', 'de'
                        ];

                        if (in_array($ck, $originLabels)) {
                            $rowValues['origin'] = $col;
                            continue;
                        }

                        $destinationLabels = [
                            'destino', 'destination', 'to', 'para'
                        ];

                        if (in_array($ck, $destinationLabels)) {
                            $rowValues['destination'] = $col;
                            continue;
                        }

                        $taxNameLabels = [
                            'nome', 'name', 'label', 'taxa', 'tax'
                        ];

                        if ($taxName === null && in_array($ck, $taxNameLabels)) {
                            $taxName = mb_strtoupper($col);
                            continue;
                        }



                        $taxTypeLabels = [
                            'tipo', 'type', 'tipotaxa', 'taxatipo',
                            'taxtype', 'typetax', 'tipodetaxa'
                        ];

                        if ($taxType === null && in_array($ck, $taxTypeLabels)) {
                            $cl = strtolower(trim($col));

                            if (in_array($cl, ['fixed', 'percentage'])) {
                                $taxType = $cl;
                                continue;
                            } else {
                                $this->throw('the tax type found is not valid on line  ' . ($rowKey + 1));
                                return null;
                            }
                        }

                        $taxSubTypeLabels = [
                            'subtipo', 'subtype', 'subtipotaxa', 'taxasubtipo',
                            'taxsubtype', 'subtypetax', 'subtipodetaxa',
                            'segundotipo', 'segundotipotaxa', 'tipo2', 'type2',
                            'taxatipo2', 'taxtype2', 'tipo2taxa'
                        ];

                        if ($taxSubType === null && in_array($ck, $taxSubTypeLabels)) {
                            $cl = strtolower(trim($col));

                            if (in_array($cl, ['invoice', 'kg', 'order', 'km', '', null])) {
                                $taxSubType = $cl;
                                continue;
                            } else {
                                $this->throw('the tax sub type found is not valid on line  ' . ($rowKey + 1));
                                return null;
                            }
                        }

                        $taxFinalWeightLabels = [
                            'peso', 'pesomaximo', 'pesominimo',
                            'maiorpeso', 'menorpeso', 'finalweight',
                            'weight', 'minimunweight', 'maximumweight',
                            'kg', 'quilo', 'quilos'
                        ];

                        if ($taxFinalWeight === null && in_array($ck, $taxFinalWeightLabels)) {
                            try {
                                $value = $this->getFloatFromString($col);
                                $taxFinalWeight = $value;
                            } catch (\Exception $e) {
                                $this->throw('finalized but nothing was imported');
                                return null;
                            }
                        }

                        $taxOptionalLabels = [
                            'optional', 'opcional', 'eopcional',
                            'éopcional', 'isoptional', 'taxaopcional',
                            'optionaltax', 'taxoptional'
                        ];

                        if ($taxOptional === null && in_array($ck, $taxOptionalLabels)) {
                            $value = $this->getBoolFromString($col);

                            if ($value !== null) {
                                $taxOptional = $value;
                                continue;
                            } else {
                                $this->throw('the value for optional tax found is invalid on line ' . ($rowKey + 1));
                                return null;
                            }
                        }

                        $taxMinPriceLabels = [
                            'minimum', 'minimumprice', 'priceminimum',
                            'minimo', 'precominimo', 'minimopreco',
                            'valorminimo', 'minimumvalue', 'menorpreco',
                            'precomenor', 'menorvalor', 'valormenor',
                            'preçomenor', 'preçominimo', 'minimopreço',
                            'menorpreço'
                        ];

                        if ($taxMinPrice === null && in_array($ck, $taxMinPriceLabels)) {
                            try {
                                $value = $this->getFloatFromString($col, true);
                                $taxMinPrice = $value;
                            } catch (\Exception $e) {
                                $this->throw('finalized but nothing was imported');
                                return null;
                            }
                        }

                        $taxdeadlineLabels = [
                            'prazo'
                        ];



                        if ($taxdeadline === null && in_array($ck, $taxdeadlineLabels)) {
                            $taxdeadline = trim($col);
                            continue;
                        }

                        $tName     = $taxName;
                        $tType     = $taxType;
                        $tSType    = $taxSubType;
                        $tWeight   = $taxFinalWeight;
                        $tOptional = $taxOptional;
                        $tMinPrice = $taxMinPrice;

                        if ($tName === null) {
                            $tName = mb_strtoupper($colKey);
                        }

                        if ($tType === null) {
                            $tType = 'fixed';
                        }

                        if ($tWeight === null) {
                            $tWeight = 9999999;
                        }

                        if ($tOptional === null) {
                            $tOptional = false;
                        }

                        if ($tMinPrice === null) {
                            $tMinPrice = 0;
                        }



                        $taxPriceLabels = [
                            'price', 'preco', 'preço'
                        ];

                        if ($taxPrice === null && in_array($ck, $taxPriceLabels)) {
                            try {
                                $taxPrice = $this->getFloatFromString($col, true);
                            } catch (\Exception $e) {
                                $this->throw('finalized but nothing was imported');
                                return null;
                            }
                        }
                    }


                    if ($taxPrice > 0) {

                        $rowValues['tax'][] = [
                            'name'         => $tName,
                            'price'        => $taxPrice,
                            'type'         => $tType,
                            'subType'      => $tSType,
                            'finalWeight'  => $tWeight,
                            'optional'     => $tOptional,
                            'minimumPrice' => $tMinPrice,
                            'deadline'     => $taxdeadline
                        ];
                    }


                    if (count($rowValues['tax']) === 0) {
                        $this->throw('the tax price was not found on the line ' . ($rowKey + 1));
                        return null;
                    }
                    $fixedValues[] = $rowValues;
                }
                break;
            default:
                $this->throw('this file format does not exist');
        }

        return $fixedValues;
    }

    private function startJobs(): void
    {
        $importsArray = $this->getImports($this->importType);
        $importCount = 0;


        if ($this->importType == 'MAIL') {
            $this->uploadCTEFromMail();
        } else {
            if (!empty($importsArray) && count($importsArray) > 0) {
                $countImports = count($importsArray);

                $this->output->writeln([
                    '',
                    $countImports . ' files to import.',
                    '',
                ]);

                /**
                 * @var array $importObject
                 */
                foreach ($importsArray as $importObject) {

                    $this->importObject = $importObject;
                    $this->changeStatus('importing', 'import has started');

                    switch ($this->importType) {

                        case 'leads':
                            $tableToImport = $this->importObject['Name'];
                            $csvObject = $this->getCSVFromImport($this->importObject);
                            $this->output->writeln([
                                '',
                                'import (#' . $this->importObject['id'] . ') has ' . count($csvObject) . ' rows to import.',
                                '',
                            ]);
                            if (count($csvObject) < 1) {
                                $this->throw('The table dont have a valid rows to import');
                            }

                            $importCount = $this->importLeads($csvObject);
                            $this->output->writeln([
                                '',
                                'import (#' . $this->importObject['id'] . ') created ' . $importCount . ' rows in ' . $tableToImport . '.',
                                '',
                            ]);
                            $this->changeStatus('imported', 'import has been completed');
                            break;

                        case 'table':
                            $tableToImport = $this->importObject['Name'];
                            $csvObject = $this->getCSVFromImport($this->importObject);
                            $this->output->writeln([
                                '',
                                'import (#' . $this->importObject['id'] . ') has ' . count($csvObject) . ' rows to import.',
                                '',
                            ]);
                            if (count($csvObject) < 1) {
                                $this->throw('The table dont have a valid rows to import');
                            }

                            $importCount = $this->importDeliveryTax($csvObject, $tableToImport);
                            $this->output->writeln([
                                '',
                                'import (#' . $this->importObject['id'] . ') created ' . $importCount . ' rows in ' . $tableToImport . '.',
                                '',
                            ]);
                            $this->changeStatus('imported', 'import has been completed');
                            break;
                        case 'DACTE':
                            $content = $this->getXMLFromImport();
                            $orders = $this->processXml(simplexml_load_string($content));
                            foreach ($orders as $order) {
                                $this->addCarrierInvoiceTax($order['order'], $order['attachment']);
                            }
                            $this->changeStatus('imported', 'import has been completed');
                            break;
                        default:
                            $tableIsValid = false;
                    }
                }
            }

            if ($importCount === 0 || empty($importsArray) || count($importsArray) === 0) {
                $this->output->writeln([
                    '',
                    '   No data imported.',
                    '',
                ]);
            }
        }
    }
}
