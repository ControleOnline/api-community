<?php

namespace App\Command;

use App\Entity\Category;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

use App\Repository\DeliveryRegionRepository;
use App\Repository\DeliveryTaxGroupRepository;

use App\Entity\DeliveryRegion;
use App\Entity\DeliveryRegionCity;
use App\Entity\DeliveryTax;
use App\Entity\DeliveryTaxGroup;
use App\Entity\Status;
use App\Entity\PurchasingInvoiceTax;
use App\Entity\SalesInvoiceTax;

class FixCommand extends Command
{
  protected static $defaultName = 'app:fix';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  /**
   * DeliveryRegion repository
   *
   * @var DeliveryRegionRepository
   */
  private $regions = null;
  /**
   * 
   */


  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager = $entityManager;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Fix Database.')
      ->setHelp('This command fix database with many sql commands.');

    $this->addArgument('target', InputArgument::REQUIRED, 'Notifications target');
    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of fix to process');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln([
      '',
      '=========================================',
      'Starting...',
      '=========================================',
      '',
    ]);
    $targetName = $input->getArgument('target');
    $limit  = $input->getArgument('limit') ?: 10;
    try {


      $fix  = 'fix' . str_replace('-', '', ucwords(strtolower($targetName), '-'));
      if (method_exists($this, $fix) === false)
        throw new \Exception(sprintf('Notification target "%s" is not defined', $targetName));

      $output->writeln([
        '',
        '=========================================',
        sprintf('Notification target: %s', $targetName),
        '=========================================',
        sprintf('Rows to process: %d', $limit),
        '',
      ]);
      $this->$fix($output, $limit);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

      $output->writeln([
        '',
        'Error: ' . $e->getMessage(),
        '',
      ]);
    }


    $output->writeln([
      '',
      '=========================================',
      'End',
      '=========================================',
      '',
    ]);

    return 0;
  }





  protected function fixClientInvoiceKey(OutputInterface $output, $limit)
  {

    $salesInvoiceTax = $this->manager->getRepository(SalesInvoiceTax::class)
      ->createQueryBuilder('IT')
      ->select()
      ->innerJoin('\App\Entity\SalesOrderInvoiceTax', 'SIT', 'WITH', 'SIT.invoiceTax = IT.id AND SIT.invoiceType=:invoiceType')
      ->where('IT.invoiceNumber =:invoice_number')
      ->orWhere('IT.invoiceNumber IS NULL')
      ->orWhere('IT.invoiceKey =:invoice_key')
      ->orWhere('IT.invoiceKey IS NULL')
      ->orderBy('RAND()')
      ->setMaxResults($limit)
      ->setParameters(array(
        'invoice_number' => 0,
        'invoice_key'    => 0,
        'invoiceType'    => 55
      ))
      ->getQuery()
      ->getResult();

    $a = 0;

    foreach ($salesInvoiceTax as $invoiceTax) {
      $nf = $invoiceTax->getInvoice();

      if ($nf) {
        $xml = @simplexml_load_string($nf);

        if ($xml && $xml->protNFe && $xml->protNFe->infProt && $xml->protNFe->infProt->chNFe) {

          $chave = (string) preg_replace("/[^0-9]/", "", $xml->protNFe->infProt->chNFe);
          $numero = (int) substr((string) $chave, 25, 9);


          $output->writeln([
            '',
            '=========================================',
            sprintf('Key: %s', $chave),
            '=========================================',
            sprintf('Number: %d', $numero),
            '',
            '=========================================',
            sprintf('Order: %d', $invoiceTax->getOrder()->first()->getOrder()->getId()),
            '',
          ]);

          $exists = null; //$this->manager->getRepository(SalesInvoiceTax::class)->findOneBy(['invoiceKey' => $chave]);
          if (!$exists) {
            if ($chave)
              $invoiceTax->setInvoiceKey($chave);
            if ($numero)
              $invoiceTax->setInvoiceNumber($numero);

            $this->manager->persist($invoiceTax);
            $this->manager->flush();
          }
        }
      }
    }
  }

  protected function fixInvoiceKey(OutputInterface $output, $limit)
  {
    $salesInvoiceTax = $this->manager->getRepository(SalesInvoiceTax::class)
      ->createQueryBuilder('IT')
      ->select()
      ->innerJoin('\App\Entity\SalesOrderInvoiceTax', 'SIT', 'WITH', 'SIT.invoiceTax = IT.id AND SIT.invoiceType=:invoiceType')
      ->where('IT.invoiceNumber =:invoice_number')
      ->orWhere('IT.invoiceNumber IS NULL')
      ->orWhere('IT.invoiceKey =:invoice_key')
      ->orWhere('IT.invoiceKey IS NULL')
      ->orderBy('RAND()')
      ->setMaxResults($limit)
      ->setParameters(array(
        'invoice_number' => 0,
        'invoice_key'    => 0,
        'invoiceType'    => 57
      ))
      ->getQuery()->getResult();


    foreach ($salesInvoiceTax as $invoiceTax) {
      $nf = $invoiceTax->getInvoice();

      if ($nf) {
        $xml = @simplexml_load_string($nf);

        if ($xml && $xml->protCTe && $xml->protCTe->infProt && $xml->protCTe->infProt->chCTe) {
          $chave = (string) $xml->protCTe->infProt->chCTe;
          $numero = (int) substr((string) $chave, 25, 9);

          $output->writeln([
            '',
            '=========================================',
            sprintf('Key: %s', $chave),
            '=========================================',
            sprintf('Number: %d', $numero),
            '',
            '=========================================',
            sprintf('Order: %d', $invoiceTax->getOrder()->first()->getOrder()->getId()),
            '',
          ]);

          $exists = null; //$this->manager->getRepository(SalesInvoiceTax::class)->findOneBy(['invoiceKey' => $chave]);
          if (!$exists) {
            if ($chave)
              $invoiceTax->setInvoiceKey($chave);
            if ($numero)
              $invoiceTax->setInvoiceNumber($numero);

            $this->manager->persist($invoiceTax);
            $this->manager->flush();
          }
        }
      }
    }
  }

  protected function fixTasksStatus(OutputInterface $output, $limit)
  {
    $rawSQL = 'UPDATE `tasks` 
    SET task_status_id = :pending_task_status_id
    WHERE `task_type` = :task_type 
    AND task_status_id = :open_task_status_id
    AND `due_date` >= :due_date
    ';

    $params = [
      'open_task_status_id' => $this->manager->getRepository(Status::class)->findOneBy(['context' => 'relationship', 'status' => 'open'])->getId(),
      'pending_task_status_id' => $this->manager->getRepository(Status::class)->findOneBy(['context' => 'relationship', 'status' => 'pending'])->getId(),
      'task_type' => 'relationship',
      'due_date' => date('Y-m-d 23:59:59')
    ];

    $output->writeln(['', 'Fix Tasks Status', '']);
    $this->manager->getConnection()->executeQuery($rawSQL, $params);

    $rawSQL = 'UPDATE `tasks` T
    INNER JOIN category C ON C.company_id = T.provider_id
    SET T.reason_id = (SELECT id FROM category CC WHERE CC.name = :category_name AND  CC.context = :context AND  CC.company_id = C.company_id)
    WHERE T.`reason_id` IS NULL
    AND T.`name` = :name';

    $params = [
      'context' => 'relationship-reason',
      'category_name' => 'Novo',
      'name' => '[Automático] - Nova oportunidade',
      'reason_id' => $this->manager->getRepository(Category::class)->findOneBy(['name' => 'Novo', 'context' => 'relationship-reason'])->getId(),
    ];

    $output->writeln(['', 'Fix Tasks Reason', '']);
    $this->manager->getConnection()->executeQuery($rawSQL, $params);
  }


  protected function fixPendingLogistic(OutputInterface $output, $limit)
  {
    $rawSQL = 'UPDATE order_logistic AS OL 
    INNER JOIN 
    (SELECT id FROM order_logistic  WHERE status_id IN (SELECT id FROM status WHERE real_status = \'pending\' AND context = \'logistic\')
       group by order_id having count(*) > 1
       ORDER BY id) OLI
    ON OLI.id = OL.id 
    SET OL.status_id = (SELECT id FROM status WHERE real_status = \'closed\' AND context = \'logistic\' LIMIT 1)';

    $output->writeln(['', 'Fix Pending Logistic', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);


    /**
     * Cria a logística Inicial
     */
    $rawSQL = 'INSERT INTO order_logistic (order_id,shipping_date,status_id)
    SELECT O.id,O.parking_date,(SELECT id FROM status WHERE status.status = \'open\' AND context = \'logistic\'  LIMIT 1) AS status_id FROM orders O
    LEFT JOIN order_logistic OL ON (OL.order_id = O.id)
    WHERE O.status_id IN (SELECT id FROM status WHERE status.status = \'waiting payment\' AND context = \'order\' )
    AND O.order_type = \'sale\'
    GROUP BY O.id
    HAVING COUNT(OL.id) = 0';

    $output->writeln(['', 'Add Open Logistic', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);

    /**
     * Quando houver uma vistoria à fazer
     */

    $rawSQL = 'UPDATE order_logistic AS OL 
     INNER JOIN order_logistic_surveys OLS ON OLS.order_logistic_id = OL.id AND OLS.status = \'complete\'
     SET OL.status_id = (SELECT id FROM status WHERE status = \'waiting survey\' AND context = \'logistic\' LIMIT 1)
     WHERE OL.status_id = (SELECT id FROM status WHERE status.status = \'open\' AND context = \'logistic\' LIMIT 1)
     AND shipping_date >= date(\'Y-m-d\')
     ';

    $output->writeln(['', 'Change Logistic Status', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);


    /**
     * Quando houver uma vistoria finalizada
     */

    $rawSQL = 'UPDATE order_logistic AS OL 
    INNER JOIN order_logistic_surveys OLS ON OLS.order_logistic_id = OL.id AND OLS.status = \'complete\'
    SET OL.status_id = (SELECT id FROM status WHERE status = \'waiting\' AND context = \'logistic\' LIMIT 1)
    WHERE OL.status_id = (SELECT id FROM status WHERE status.status = \'open\' AND context = \'logistic\' LIMIT 1)
    ';

    $output->writeln(['', 'Change Logistic Status', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);


    /**
     * Quando houver uma vistoria finalizada
     */

     $rawSQL = 'UPDATE order_logistic AS OL 
     INNER JOIN orders O ON O.id = OL.order_id AND  O.parking_date IS NOT NULL
     SET OL.shipping_date = O.parking_date
     WHERE OL.shipping_date IS NULL 
     ';
 
     $output->writeln(['', 'Change Logistic Status', '']);
     $this->manager->getConnection()->executeQuery($rawSQL);




         /**
     * Finaliza a logística quando houver uma logística pro mesmo pedido já aberta
     */

    $rawSQL = 'UPDATE order_logistic AS OL 
    INNER JOIN order_logistic OLN ON (
      OLN.order_id = OL.order_id AND 
      OLN.id > OL.id AND
      OLN.status_id != (SELECT id FROM status WHERE status.status = \'open\' AND context = \'logistic\' LIMIT 1)
    )
    INNER JOIN order_logistic_surveys OLS ON OLS.order_logistic_id = OL.id AND OLS.status = \'complete\'
    SET OL.status_id = (SELECT id FROM status WHERE status = \'finished\' AND context = \'logistic\' LIMIT 1)
    WHERE OL.status_id IN (SELECT id FROM status WHERE status.real_status != \'closed\' AND context = \'logistic\')
    AND OLS.status = \'complete\'
    ';

   $output->writeln(['', 'Change Logistic Status', '']);
   $this->manager->getConnection()->executeQuery($rawSQL);

  }

  protected function fixOrderStatusByContract(OutputInterface $output, $limit)
  {
    $rawSQL = 'UPDATE orders 
    INNER JOIN contract ON (contract.id = orders.contract_id AND contract.contract_status=\'Active\')    
    SET orders.status_id = :set_status
    WHERE orders.status_id = :status
    
    ';

    $params = [
      'set_status' => $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting payment', 'context' => 'order'])->getId(),
      'status' => $this->manager->getRepository(Status::class)->findOneBy(['status' => 'waiting client invoice tax', 'context' => 'order'])->getId(),
    ];    

    $output->writeln(['', 'Fix Orders Status', '']);
    $this->manager->getConnection()->executeQuery($rawSQL, $params);
  }


  protected function fixOrderOwner(OutputInterface $output, $limit)
  {
    $rawSQL = 'UPDATE orders 
    INNER JOIN address ON (address_origin_id = address.id)
    INNER JOIN street ON (street.id = street_id)
    INNER JOIN district ON (district_id = district.id)
    INNER JOIN city ON (city.id = district.city_id)
    INNER JOIN state ON (state.id = city.state_id)
    INNER JOIN people_states ON (state.id = people_states.state_id)
    SET orders.provider_id = people_states.people_id
    WHERE orders.provider_id IN (SELECT people_id FROM people_states)
    AND orders.order_type = :order_type
    AND orders.main_order_id IS NULL';

    $params = [
      'order_type' => 'sale'
    ];

    $output->writeln(['', 'Fix Orders Owners', '']);
    $this->manager->getConnection()->executeQuery($rawSQL, $params);
  }

  protected function fixClients(OutputInterface $output, $limit)
  {
    $rawSQL = '
    INSERT INTO people_client (client_id,company_id,`enable`)
    SELECT orders.client_id,orders.provider_id,1 FROM orders 
    LEFT JOIN people_client ON people_client.client_id = orders.client_id AND people_client.company_id = orders.provider_id
    WHERE orders.client_id IS NOT NULL AND orders.provider_id IS NOT NULL
    GROUP BY orders.client_id,orders.provider_id
    HAVING COUNT(people_client.id) = 0
    ';

    $output->writeln(['', 'Fix Clients', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);


    $rawSQL = '
    INSERT INTO people_client (client_id,company_id,`enable`)
    SELECT orders.payer_people_id,orders.provider_id,1 FROM orders 
    LEFT JOIN people_client ON people_client.client_id = orders.payer_people_id AND people_client.company_id = orders.provider_id
    WHERE orders.payer_people_id IS NOT NULL AND orders.provider_id IS NOT NULL
    GROUP BY orders.payer_people_id,orders.provider_id
    HAVING COUNT(people_client.id) = 0
    ';

    $output->writeln(['', 'Fix Payers', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);


    $rawSQL = 'INSERT INTO people_client (company_id,client_id,enable)
    SELECT PC.company_id,TA.company_id AS client,"1" FROM people_client PC
    INNER JOIN  (
    SELECT `people_employee`.company_id,`people_employee`.`employee_id` FROM `people_employee` WHERE people_employee.employee_id IN (
        SELECT client_id FROM people_client WHERE people_client.client_id = people_employee.employee_id
    ) AND people_employee.company_id NOT IN (
        SELECT client_id FROM people_client WHERE people_client.client_id = people_employee.employee_id
    )) TA ON TA.employee_id = PC.client_id AND PC.company_id IN (SELECT people_states.people_id FROM people_states)  
    
    WHERE PC.company_id NOT IN (SELECT CC.company_id FROM people_client CC WHERE CC.company_id = PC.company_id AND CC.client_id = TA.company_id)
    GROUP BY PC.company_id,TA.company_id';

    $output->writeln(['', 'Fix Clients Companies', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);

    $rawSQL = 'INSERT INTO people_client (company_id,client_id,enable)
      SELECT PC.company_id,TA.employee_id AS client,"1" FROM people_client PC
      INNER JOIN  (
      SELECT `people_employee`.company_id,`people_employee`.`employee_id` FROM `people_employee` WHERE 
      people_employee.employee_id NOT IN (
          SELECT client_id FROM people_client WHERE people_client.client_id = people_employee.company_id
      ) AND people_employee.company_id IN (
          SELECT client_id FROM people_client WHERE people_client.client_id = people_employee.company_id
      )) TA ON TA.company_id = PC.client_id AND PC.company_id IN (SELECT people_states.people_id FROM people_states)  
    
    WHERE PC.company_id NOT IN (SELECT CC.company_id FROM people_client CC WHERE CC.company_id = PC.company_id AND CC.client_id = TA.employee_id)
    GROUP BY PC.company_id,TA.employee_id';

    $output->writeln(['', 'Fix Clients Companies', '']);
    $this->manager->getConnection()->executeQuery($rawSQL);
  }
}
