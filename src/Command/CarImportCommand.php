<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;

use ControleOnline\Entity\CarManufacturer;
use ControleOnline\Entity\CarModel;
use ControleOnline\Entity\CarYearPrice;

class CarImportCommand extends Command
{
  protected static $defaultName = 'app:car-import';

  /**
   * Entity manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  public function __construct(EntityManagerInterface $entityManager)
  {
      $this->manager = $entityManager;

      parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Generate order and invoice for contract payers.')
      ->setHelp       ('This command generate order and invoices.')
    ;

    $this->addArgument('limit', InputArgument::OPTIONAL, 'Limit of contracts to process');
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

    $vehicleType = $this->getVehiclesType();

    if(empty($vehicleType)){
      $output->writeln([
        '',
        '   No vehicles type.',
        '',
      ]);
    }else{

      foreach($vehicleType as $vehicle_type){

        $car_manufacturers = $this->getCarManufacturer($vehicle_type, $this->getIdTableRef());
    
        if (empty($car_manufacturers)) {
          $output->writeln([
            '',
            '   No manufacturer to import.',
            '',
          ]);
        }
        else {
    
          foreach($car_manufacturers as $car_manufacturer){
            
              $this->manager->getConnection()->beginTransaction();
    
              $carmanufacturer = $this->manager->getRepository(CarManufacturer::class)
              ->findOneBy(['value' => $car_manufacturer['Value']]);

              if (!$carmanufacturer){
                $carmanufacturer = new CarManufacturer();
              }
                
              $carmanufacturer->setCarTypeId($vehicle_type);
              $carmanufacturer->setCarTypeRef($this->getIdTableRef());
              $carmanufacturer->setLabel($car_manufacturer['Label']);
              $carmanufacturer->setValue($car_manufacturer['Value']);
        
              $this->manager->persist($carmanufacturer);
              $this->manager->flush();
              
              $this->manager->getConnection()->commit();
    
    
              $output->writeln([
                '',
                '   =========================================',
                sprintf('   Manufacturer: %s', $car_manufacturer['Label']),
                sprintf('   Value: %s', $car_manufacturer['Value']),
                '   =========================================',
                '',
              ]);
    
              $models = $this->getCarModel($vehicle_type, $car_manufacturer['Value']);
    
            if ($models['Modelos'] && $models){
              foreach($models['Modelos'] as $model){
    
                $this->manager->getConnection()->beginTransaction();
    

                $carmodel = $this->manager->getRepository(CarModel::class)
                ->findOneBy(['value' => $model['Value']]);
  
                if (!$carmodel){
                  $carmodel = new CarModel();
                }
          
                $carmodel->setCarManufacturerId($carmanufacturer->getId());
                $carmodel->setLabel($model['Label']);
                $carmodel->setValue($model['Value']);
          
                $this->manager->persist($carmodel);
                $this->manager->flush();
          
                $this->manager->getConnection()->commit();
    
            
                $output->writeln([
                  '',
                  '   =========================================',
                  sprintf('   Model: %s', $model['Label']),
                  sprintf('   Value: %s', $model['Value']),
                  '   =========================================',
                  '',
                ]);


                  $caryearmodel = [
                    'codigoTabelaReferencia'  => $this->getIdTableRef(),
                    'codigoTipoVeiculo'       => $vehicle_type,
                    'codigoMarca'             => $car_manufacturer['Value'],
                    'codigoModelo'            => $model['Value'],
                  ];
  
                  $modelsyear = $this->getModelYear($caryearmodel);

                  
                  if ($modelsyear){
                  foreach($modelsyear as $modelyear){
                                      
                      $this->manager->getConnection()->beginTransaction();
    

                      $caryearprice = $this->manager->getRepository(CarYearPrice::class)
                      ->findOneBy([
                        'value' => $modelyear['Value'],
                        'carModelId' => $carmodel,
                        'fuelTypeCode' => $this->getFuelType($modelyear['Value'])
                      ]);

                      if (!$caryearprice){
                        $caryearprice = new CarYearPrice();
                      }

                      $caryearprice->setCarTypeId         ($vehicle_type);
                      $caryearprice->setCarTypeRef        ($this->getIdTableRef());
                      $caryearprice->setCarManufacturerId ($carmanufacturer->getId());
                      $caryearprice->setCarModel          ($carmodel);
                      $caryearprice->setLabel             (isset($modelyear['Label'])?$modelyear['Label']:0);
                      $caryearprice->setValue             (isset($modelyear['Value'])?$modelyear['Value']:1);

                      try{

                        $payloadCarPrice = [
                          'codigoTabelaReferencia'  => $this->getIdTableRef(),
                          'codigoTipoVeiculo'       => $vehicle_type,
                          'codigoMarca'             => $car_manufacturer['Value'],
                          'ano'                     => $modelyear['Value'],
                          'codigoTipoCombustivel'   => $this->getFuelType($modelyear['Value']),
                          'anoModelo'               => $this->getYearModel($modelyear['Value']),
                          'codigoModelo'            => $model['Value'],
                          'tipoConsulta'            => 'tradicional'
                        ];
      
                        $modelsprice = $this->getPrice($payloadCarPrice);
      
                        $caryearprice->setFuelTypeCode($this->getFuelType($modelyear['Value']));
                        $caryearprice->setPrice((int) preg_replace("/[^0-9]/", "", $modelsprice['Valor']));                        

                      }catch(\Exception $e){
                        echo $e->getMessage();
                      }
                                            
                      $this->manager->persist($caryearprice);
                      $this->manager->flush();
                
                      $this->manager->getConnection()->commit();

                      $output->writeln([
                        '',
                        '   =========================================',
                        sprintf('   Ano Modelo: %s', $this->getYearModel(isset($modelyear['Value'])?$modelyear['Value']:0)),
                        sprintf('   Fuel Type: %s', $this->getFuelType(isset($modelyear['Value'])?$modelyear['Value']:0)),
                        '   =========================================',
                        '',
                      ]);
  
                  }                               
                }
              }
            }
          }
        }

        
      }

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

  private function setCarYearPrice()
  {

  }


  /**
   * Get Fuel Type Id
   */
  private function getFuelType($yearModel):int
  {
    $fuelTypeId = explode("-", $yearModel);

    return isset($fuelTypeId[1])?$fuelTypeId[1]:0;
  }

  /**
   * Get Year Model
   */
  private function getYearModel($yearModel):int
  {
    $yearModelId = explode("-", $yearModel);

    return $yearModelId[0];
  }


  /**
   * Get Car Manufacturer
   */
  private function getCarManufacturer(int $vehicle_type, int $vehicle_type_ref)
  {
      $api = "http://veiculos.fipe.org.br/api/veiculos/ConsultarMarcas";

      $data = array(
        'codigoTabelaReferencia' => $vehicle_type_ref,
        'codigoTipoVeiculo' => $vehicle_type,
      );

      $payload = json_encode($data);

      $headers = array(
        'Content-Type:application/json'
      );

      $ch = curl_init();

      curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_URL, $api);

      if(curl_errno($ch)){
        echo curl_error($ch);
        return null;
      }

      $carmanufacturer = json_decode(curl_exec($ch), true);

      curl_close($ch);
      
      return $carmanufacturer && !isset($carmanufacturer['erro'])?$carmanufacturer:null;

  }

  /**
   * Get Car Model
   */
  private function getCarModel(int $vehicle_type, int $value)
  { 
      $api = "http://veiculos.fipe.org.br/api/veiculos/ConsultarModelos";

      $data = array(
        'codigoTabelaReferencia' => $this->getIdTableRef(),
        'codigoTipoVeiculo' => $vehicle_type,
        'codigoMarca' => $value,
      );

      $payload = json_encode($data);

      $headers = array(
        'Content-Type:application/json'
      );

      $ch = curl_init();

      curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_URL, $api);

      if(curl_errno($ch)){
        echo curl_error($ch);
        return null;
      }

      $carmodel = json_decode(curl_exec($ch), true);

      curl_close($ch);

      return $carmodel && !isset($carmodel['erro'])?$carmodel:null;
  }

  private function getVehiclesType(): array
  { 
     $type = [
        'carro'     => 1,
        'moto'      => 2,
        'caminhao'  => 3,
     ];

     return $type;
  }

  /**
   * Get id table ref
   */
  private function getIdTableRef()
  {
    $api = "http://veiculos.fipe.org.br/api/veiculos/ConsultarTabelaDeReferencia";
  
    $headers = array(
      'Host:veiculos.fipe.org.br',
      'Referer: http://veiculos.fipe.org.br',
      'Content-Length:0',
      'Content-Type:application/json',
      'Cookie: ROUTEID=.5'
    );
  
    $ch = curl_init();
  
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt( $ch, CURLOPT_URL, $api);
  
    if(curl_errno($ch)){
      echo curl_error($ch);
      return null;
    }
  
    $tableref = json_decode(curl_exec($ch), true);
  
    curl_close($ch);
  
    
    return $tableref?$tableref[0]['Codigo']:null;
  }

  private function getModelYear($data = [])
  {
      $api = "http://veiculos.fipe.org.br/api/veiculos/ConsultarAnoModelo";
    
      $payload = json_encode($data);
  
      $headers = array(
        'Content-Type:application/json'
      );
  
      $ch = curl_init();
  
      curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_URL, $api);
  
      if(curl_errno($ch)){
        echo curl_error($ch);
        return null;
      }
  
      $modelyear = json_decode(curl_exec($ch), true);
  
      curl_close($ch);
  
      return $modelyear && !isset($modelyear['erro'])?$modelyear:null;  
  }
  
  
  private function getPrice($data = [])
  {
    $api = "http://veiculos.fipe.org.br/api/veiculos/ConsultarValorComTodosParametros";
      
    $payload = json_encode($data);
    
  
    $headers = array(
      'Content-Type:application/json'
    );
  
    $ch = curl_init();
  
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_URL, $api);
  
    if(curl_errno($ch)){
      echo curl_error($ch);
      return null;
    }
  
    $carprice = json_decode(curl_exec($ch), true);        
  
    curl_close($ch);
  
    return $carprice && !isset($carprice['erro'])?$carprice:null;
  }
  

}
