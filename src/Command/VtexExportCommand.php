<?php
namespace App\Command;

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

class VtexExportCommand extends Command
{
  protected static $defaultName = 'app:vtex-export';

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
      $this->regions = $this->manager->getRepository(DeliveryRegion::class);

      parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Export sheet to VTEX.')
      ->setHelp       ('This command export xsl to import VTEX.')
    ;

    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of export to process');
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

    $limit  = $input->getArgument('limit') ?: 100;

    try {

      $this->saveAsXls($output);

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

    /**
     * Save file in format xls!
     */
    protected function saveAsXls(OutputInterface $output){

      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();

      $xlsxHeader = [
        "A1" => "ZipCodeStart",
        "B1" => "ZipCodeEnd",
        "C1" => "PolygonName",
        "D1" => "WeightStart",
        "E1" => "WeightEnd",
        "F1" => "AbsoluteMoneyCost",
        "G1" => "PricePercent",
        "H1" => "PriceByExtraWeight",
        "I1" => "MaxVolume",
        "J1" => "TimeCost",
        "K1" => "Country",
        "L1" => "MinimumValueInsurance",
      ];
      
      /**
       * Create Header For xls!
       */
      foreach($xlsxHeader as $key => $header){

        $output->writeln(sprintf('-- Header Added: %s in col: %s', $header, $key));

        $sheet->setCellValue($key, $header);

      }

      $output->writeln(['','-- Header xls created!', '']);

      $deadlines = $this->getDeadline();

      foreach($deadlines as $deadline){
        print_r($deadline);
      }


      $writer = new Xls($spreadsheet);
      $writer->save('public/arquivos/ExportVtex.xls');

      $output->writeln(['-- File xls save in public/arquivos/ExportVtex.xls', '']);

    }

    /**
     * Get Deadline
     */
    protected function getDeadline(){
      
      $region = $this->regions->findAll();

      foreach($region as $regionss){
          $deadline[] = [
            'deadline' => $regionss->getDeadline()
          ];
      }

      return $deadline;

    }

  }
