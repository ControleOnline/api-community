<?php

namespace App\Library\Provider\Signature;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use ControleOnline\Entity\City;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\MyContract;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\SchoolTeamSchedule;
use ControleOnline\Entity\Team;
use ControleOnline\Entity\Particulars;
use App\Library\Utils\Formatter;
use ControleOnline\Entity\MyContractProduct;


class ContractDocument
{
    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    protected $manager  = null;

    protected $request;

    protected $company_id;

    protected $daysWeek = [
        'monday'    => 'segunda-feira',
        'tuesday'   => 'terça-feira',
        'wednesday' => 'quarta-feira',
        'thursday'  => 'quinta-feira',
        'friday'    => 'sexta-feira',
        'saturday'  => 'sábado',
        'sunday'    => 'domingo'
    ];

    protected $vars = [
        'contract_id' => [
            'id',
            'contract_id'
        ],
        'order_id' => [
            'order_id',
            'quote_id'
        ],
        'order_price' => [
            'order_price'
        ],
        'order_price_text' => [
            'order_price_text'
        ],
        'product_cubage' => [
            'product_cubage'
        ],
        'product_type' => [
            'product_type',
            'car_name'
        ],
        'product_total_price' => [
            'product_total_price',
            'car_price'
        ],
        'student_name' => [
            'dados_aluno',
            'student_name',
            'contratante'
        ],
        'student_address' => [
            'endereco_aluno',
            'student_address',
            'contratante_endereco_completo'
        ],
        'student_small_address' => [
            'student_small_address',
            'contratante_endereco'
        ],
        'student_cep' => [
            'student_cep',
            'contratante_cep'
        ],
        'student_rg' => [
            'rg_aluno',
            'student_rg',
            'contratante_rg'
        ],
        'payer_document_type' => [
            'doc_aluno',
            'payer_document_type',
            'contratante_doc_type'
        ],
        'payer_document' => [
            'cpf_aluno',
            'payer_document',
            'contratante_cpf'
        ],
        'company_cnpj' => [
            'company_cnpj'
        ],
        'company_address' => [
            'company_address'
        ],
        'company_owners' => [
            'company_owners'
        ],
        'contract_place' => [
            'company_place',
            'contract_place'
        ],
        'contract_hours' => [
            'total_horas_contratadas',
            'contract_hours'
        ],
        'contract_schedule' => [
            'horarios_agendados',
            'contract_schedule'
        ],
        'contract_startdate' => [
            'data_inicio_contrato',
            'contract_startdate'
        ],
        'contract_amount' => [
            'valor_total_servicos',
            'contract_amount'
        ],
        'contract_enddate' => [
            'dia_vencimento',
            'contract_enddate'
        ],
        'contract_modality' => [
            'contract_modality'
        ],
        'contract_detail_services' => [
            'detalhe_total_servicos',
            'contract_detail_services'
        ],
        'origin_address' => [
            'origin_address'
        ],
        'origin_city' => [
            'origin_city'
        ],
        'origin_state' => [
            'origin_state'
        ],
        'destination_city' => [
            'destination_city'
        ],
        'destination_state' => [
            'destination_state'
        ],
        'origin_small_address' => [
            'origin_small_address'
        ],
        'origin_cep' => [
            'origin_cep'
        ],
        'destination_address' => [
            'destination_address'
        ],
        'destination_small_address' => [
            'destination_small_address'
        ],
        'destination_cep' => [
            'destination_cep'
        ],
        'company_address' => [
            'company_address'
        ],
        'company_cep' => [
            'company_cep'
        ],
        'company_small_address' => [
            'company_small_address'
        ],
        'today' => [
            'data_hoje',
            'today'
        ],
        'company_name' => [
            'company_name'
        ],
        'company_alias' => [
            'company_alias'
        ],
        'car_color' => [
            'car_color'
        ],
        'car_number' => [
            'car_number'
        ],
        'renavan' => [
            'renavan'
        ],
        'route_time' => [
            'route_time'
        ],
        'retrieve_address_type' => [
            'retrieve_address_type'
        ],
        'delivery_address_type' => [
            'delivery_address_type'
        ],
        'retrieve_address' => [
            'retrieve_address'
        ],
        'delivery_address' => [
            'delivery_address'
        ],
        'payment_type' => [
            'payment_type'
        ],
        'date' => [
            'date'
        ],
    ];


    public function setCompanyId($company_id)
    {
        $this->company_id = $company_id;
    }

    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
    }


    /**
     * dados_aluno
     * endereco_aluno
     * rg_aluno
     * cpf_aluno
     * total_horas_contratadas
     * horarios_agendados
     * data_inicio_contrato
     * valor_total_servicos
     * dia_vencimento
     * data_hoje
     * company_place
     * company_cnpj
     * company_address
     * company_owners
     * contract_modality
     * detalhe_total_servicos
     */
    public function getContractContent(MyContract $contract): string
    {
        $values = $this->getTemplateVarsWithValues($contract);
        $content = preg_replace(
            $this->getRegex($values),
            array_values($values),
            ($contract->getHtmlContent() !== null && $contract->getHtmlContent() !== '') ? $contract->getHtmlContent() : $contract->getContractModel()->getContent()
        );               

        if (!empty($content)) {
            $this->manager->persist($contract->setHtmlContent($content));
            $this->manager->flush();
        }
        return $content;
    }

    protected function getRegex(array $values): array
    {

        $reges = array();

        foreach (array_keys($values) as $key) {
            $variables = $this->vars[$key];

            $reges[] = "#\{\{\\s*\\n*\\t*(" . implode("|", $variables) . ")\\t*\\n*\\s*\}\}#";
        }

        return $reges;
    }

    protected function getTemplateVarsWithValues(MyContract $myContract): array
    {
        $student   = $this->getStudentsData($myContract);
        $contract  = $this->getContractData($myContract);
        $company   = $this->getCompanyData($myContract);
        $orderData = $this->getOrderData($myContract);

        $orderPriceText = new \NumberFormatter("pt-BR", \NumberFormatter::SPELLOUT);


        date_default_timezone_set('America/Sao_Paulo');

        $data = new \DateTime('now');
        $formatter = new \IntlDateFormatter(
            'pt_BR',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            \IntlDateFormatter::GREGORIAN
        );

        return [
            'contract_id'               => $myContract->getId(),
            'student_name'              => $student['student_name'],
            'student_address'           => $student['student_address'],
            'student_small_address'     => $student['student_small_address'],
            'student_cep'               => $student['student_cep'],
            'student_rg'                => $student['student_rg'],
            'payer_document'            => Formatter::document($student['payer_document']),
            'payer_document_type'       => $student['payer_document_type'],
            'today'                     => (new \DateTime('now'))->format('d/m/Y'),
            'date'                      => $formatter->format($data),
            'company_cnpj'              => Formatter::document($company['company_cnpj']),
            'company_address'           => $company['company_address'],
            'company_cep'               => $company['company_cep'],
            'company_small_address'     => $company['company_small_address'],
            'company_owners'            => $company['company_owners'],
            'company_name'              => $company['company_name'],
            'company_alias'             => $company['company_alias'],
            'contract_place'            => $contract['contract_place'],
            'contract_hours'            => $contract['contract_hours'],
            'contract_schedule'         => $contract['contract_schedule'],
            'contract_startdate'        => $contract['contract_startdate'],
            'contract_amount'           => Formatter::money($contract['contract_amount']),
            'contract_enddate'          => $contract['contract_enddate'],
            'contract_modality'         => $contract['contract_modality'],
            'contract_detail_services'  => $contract['contract_detail_services'],
            'order_id'                  => $orderData['order_id'],
            'order_price'               => $orderData['order_price'],
            'order_price_text'          => $orderPriceText->format(floatval($orderData['order_price'])),
            'product_cubage'            => $orderData['product_cubage'],
            'product_type'              => $orderData['product_type'],
            'product_total_price'       => $orderData['product_total_price'],
            'origin_address'            => $orderData['origin_address'],
            'origin_city'               => $orderData['origin_city'],
            'origin_state'              => $orderData['origin_state'],
            'origin_cep'                => $orderData['origin_cep'],
            'origin_small_address'      => $orderData['origin_small_address'],
            'destination_address'       => $orderData['destination_address'],
            'destination_city'          => $orderData['destination_city'],
            'destination_state'         => $orderData['destination_state'],
            'destination_cep'           => $orderData['destination_cep'],
            'destination_small_address' => $orderData['destination_small_address'],
            'retrieve_address_type'     => isset($orderData['other_informations']->retrieve_address_type) ? $orderData['other_informations']->retrieve_address_type : 'Base',
            'delivery_address_type'     => isset($orderData['other_informations']->delivery_address_type) ? $orderData['other_informations']->delivery_address_type : 'Base',
            'renavan'                   => isset($orderData['other_informations']->renavan) ? $orderData['other_informations']->renavan : 'Renavan não informado',
            'car_color'                 => isset($orderData['other_informations']->carColor) ? $orderData['other_informations']->carColor : null,
            'car_number'                => isset($orderData['other_informations']->carNumber) ? $orderData['other_informations']->carNumber : null,
            'route_time'                => $this->getRouteTime($orderData),
            'payment_type'              => $this->getPaymentType($orderData),
            'delivery_address'          => $this->getDeliveryAddress($orderData),
            'retrieve_address'          => $this->getRetrieveAddress($orderData),

        ];
    }
    protected function getRouteTime($orderData)
    {
        $route_time = (isset($orderData['other_informations']->route_time) && $orderData['other_informations']->route_time > 0 ? $orderData['other_informations']->route_time : 10);

        if (in_array($orderData['origin_state'], ['RR', 'AM', 'RO', 'AC']) || in_array($orderData['destination_state'], ['RR', 'AM', 'RO', 'AC'])) {
            $route_time += 10;
        }

        return  $route_time . ' a ' . ($route_time + 5) . ' dias';
    }



    protected function getDeliveryAddress($orderData)
    {
        if (!isset($orderData['other_informations']->delivery_address_type)) {
            return $orderData['destination_city'] . ' / ' . $orderData['destination_state'];
        } else if ($orderData['other_informations']->delivery_address_type == 'winch') {
            return $orderData['destination_address'] . ' - ' . $orderData['destination_city'] . ' / ' . $orderData['destination_state'];
        } else {
            return $orderData['destination_city'] . ' / ' . $orderData['destination_state'] . ' (Ponto de Encontro) ';
        }
    }

    protected function getRetrieveAddress($orderData)
    {
        if (!isset($orderData['other_informations']->retrieve_address_type)) {
            return $orderData['origin_city'] . ' / ' . $orderData['origin_state'];
        } else if ($orderData['other_informations']->retrieve_address_type == 'winch') {
            return $orderData['origin_address'] . ' - ' . $orderData['origin_city'] . ' / ' . $orderData['origin_state'];
        } else {
            return $orderData['origin_city'] . ' / ' . $orderData['origin_state'] . ' (Ponto de Encontro) ';
        }
    }

    protected function getPaymentType($orderData)
    {
        if ($orderData['other_informations'] && isset($orderData['other_informations']->paymentType)) {
            switch ($orderData['other_informations']->paymentType) {
                case '5':
                    return '
                    24 horas úteis (não considerados sábados, domingos e feriados) antes da retirada
                    do veículo, para que sejam realizados os procedimentos administrativos de liberação.
                    ';
                    break;
                case '4':
                    return '

                    50% do valor na assinatura do contrato e os outros 50%, 24 horas úteis
                    (não considerados sábados, domingos e feriados) antes da retirada do veículo,
                    para que sejam realizados os procedimentos administrativos de liberação.

                        ';
                    break;
                case '3':
                    return '

                    na assinatura do contrato, via Cartão de Crédito, COM OS ACRÉSCIMOS DAS TAXAS
                    RELATIVAS AO CARTÃO. Para esta modalidade de pagamento, o veículo será liberado
                    após 24 horas úteis (não considerados sábados, domingos e feriados) da
                    confirmação da chegada, para que sejam realizados os procedimentos
                    administrativos de liberação

                    ';
                    break;
                case '2':
                    return '

                    60% do valor na assinatura do contrato e os outros 40%, 24 horas úteis
                    (não considerados sábados, domingos e feriados) antes da retirada do veículo,
                    para que sejam realizados os procedimentos administrativos de liberação.


                    ';
                    break;
                case '1':
                    return '

                    à vista. Para esta modalidade de pagamento, o veículo será liberado
                    após 24 horas úteis (não considerados sábados, domingos e feriados)
                    da confirmação da chegada, para que sejam realizados os procedimentos
                    administrativos de liberação.

                    ';
                    break;
                default:
                    return '
                    na
                    assinatura do contrato, via Transferência Bancaria/Boleto ou
                    Cartão de Crédito
                    ';
                    break;
            }
        } else {
            return ' na
            assinatura do contrato, via Transferência Bancaria/Boleto ou
            Cartão de Crédito
           ';
        }
    }

    protected function getOrderData(MyContract $myContract): array
    {
        $data = [
            'order_id'                  => '[order_id]',
            'order_price'               => '[order_price]',
            'product_cubage'            => '[product_cubage]',
            'product_type'              => '[product_type]',
            'product_total_price'       => '[product_total_price]',
            'origin_address'            => '[origin_address]',
            'origin_cep'                => '[origin_cep]',
            'origin_small_address'      => '[origin_small_address]',
            'destination_address'       => '[destination_address]',
            'destination_cep'           => '[destination_cep]',
            'destination_small_address' => '[destination_small_address]',
            'other_informations'        => '[other_informations]',
            'origin_city'               => '[origin_city]',
            'origin_state'              => '[origin_state]',
            'destination_city'          => '[destination_city]',
            'destination_state'         => '[destination_state]',
        ];

        $orderRepo = $this->manager->getRepository(SalesOrder::class);

        /**
         * @var SalesOrder $order
         */
        $order = $orderRepo->findOneBy([
            'contract' => $myContract->getId(),
            'orderType'  => 'sale'
        ]);



        if (!empty($order)) {
            $data['order_id']            = $order->getId();
            $data['order_price']         = number_format($order->getPrice(), 2, ',', '.');
            $data['product_cubage']      = $order->getCubage();
            $data['product_type']        = $order->getProductType();
            $data['product_total_price'] = number_format($order->getInvoiceTotal(), 2, ',', '.');
            $data['other_informations'] = $order->getOtherInformations(true);
            $origin = $order->getAddressOrigin();
            if (empty($origin)) {
                $origin = $order->getQuote()->getCityOrigin();
            }

            $adresO =             $this->getAddress($origin);
            if ($adresO) {
                $resOrigin = $this->getAddressVars('origin', $adresO);
                $data["origin_address"]       = $resOrigin["origin_address"];
                $data["origin_cep"]           = $resOrigin["origin_cep"];
                $data["origin_small_address"] = $resOrigin["origin_small_address"];
                $data["origin_city"] = $resOrigin["origin_city"];
                $data["origin_state"] = $resOrigin["origin_state"];
            } else {
                $data["origin_city"] = $order->getQuotes()->first()->getCityOrigin()->getCity();
                $data["origin_state"] = $order->getQuotes()->first()->getCityOrigin()->getState()->getUf();
            }
            $destination = $order->getAddressDestination();
            if (empty($destination)) {
                $destination = $order->getQuote()->getCityDestination();
            }

            $adresD =             $this->getAddress($destination);
            if ($adresD) {
                $resDestination = $this->getAddressVars('destination', $adresD);
                $data["destination_address"]       = $resDestination["destination_address"];
                $data["destination_cep"]           = $resDestination["destination_cep"];
                $data["destination_small_address"] = $resDestination["destination_small_address"];
                $data["destination_city"] = $resDestination["destination_city"];
                $data["destination_state"] = $resDestination["destination_state"];
            } else {
                $data["destination_city"] = $order->getQuotes()->first()->getCityDestination()->getCity();
                $data["destination_state"] = $order->getQuotes()->first()->getCityDestination()->getState()->getUf();
            }
        }

        return $data;
    }

    protected function getAddressVars(string $prefix, array $address): array
    {
        $data = [
            $prefix . '_address'       => '[_address]',
            $prefix . '_cep'           => '[_cep]',
            $prefix . '_small_address' => '[_small_address]',
        ];

        if ($address !== null) {
            $resAddress = [];

            $resAddress[] = sprintf('%s %s', $address['street'], $address['number']);
            $resAddress[] = $address['complement'];
            $resAddress[] = $address['district'];
            $resAddress[] = sprintf('%s - %s', $address['city'], $address['state']);

            $data[$prefix . '_small_address'] = array_filter($resAddress, 'strlen');
            $data[$prefix . '_small_address'] = implode(', ', $data[$prefix . '_small_address']);

            $resAddress[] = sprintf('CEP %s', Formatter::mask('#####-###', $address['postalCode']));

            $data[$prefix . '_address'] = array_filter($resAddress, 'strlen');
            $data[$prefix . '_address'] = implode(', ', $data[$prefix . '_address']);
            $data[$prefix . '_cep']     = Formatter::mask('#####-###', $address['postalCode']);
            $data[$prefix . '_city'] = $address['city'];
            $data[$prefix . '_state'] = $address['state'];
        }

        return $data;
    }

    protected function getStudentsData(MyContract $myContract): array
    {
        $data = [
            'student_name'          => '[student_name]',
            'student_rg'            => '[student_rg]',
            'payer_document'        => '[payer_document]',
            'payer_document_type'   => '[payer_document_type]',
            'student_address'       => '[student_address]',
            'student_cep'           => '[student_cep]',
            'student_small_address' => '[student_small_address]',
        ];

        $peopleContract = $myContract->getContractPeople()
            ->filter(function ($contractPeople) {
                return $contractPeople->getPeopleType() == 'Payer';
            });
        if (!$peopleContract->isEmpty()) {
            $contact = $this->getContactByPeople($peopleContract->first()->getPeople());

            // name

            $data['student_name'] = sprintf('%s %s', $contact['name'], $contact['alias']);

            // documents

            if (!empty($contact['documents'])) {

                if (isset($contact['documents']['CNPJ']) && !empty($contact['documents']['CNPJ'])) {
                    $data['payer_document'] = $contact['documents']['CNPJ'];
                    $data['payer_document_type'] = 'CNPJ';
                }

                if (isset($contact['documents']['CPF']) && !empty($contact['documents']['CPF'])) {
                    $data['payer_document'] = $contact['documents']['CPF'];
                    $data['payer_document_type'] = 'CPF';
                }

                if (isset($contact['documents']['R.G'])) {
                    $data['student_rg']  = $contact['documents']['R.G'];
                }
            }

            // address

            if ($contact['address'] !== null) {
                $result = $this->getAddressVars('student', $contact['address']);

                $data["student_address"]       = $result["student_address"];
                $data["student_cep"]           = $result["student_cep"];
                $data["student_small_address"] = $result["student_small_address"];
            }
        }

        return $data;
    }

    protected function getContractData(MyContract $myContract): array
    {
        $data = [
            'contract_hours'           => '[contract_hours]',
            'contract_schedule'        => '[contract_schedule]',
            'contract_startdate'       => $myContract->getStartDate()->format('d/m/Y'),
            'contract_enddate'         => $myContract->getEndDate() !== null ? $myContract->getEndDate()->format('d/m/Y') : '-',
            'contract_amount'          => $this->getContractTotalPrice($myContract),
            'contract_place'           => '[contract_place]',
            'contract_modality'        => '[contract_modality]',
            'contract_detail_services' => '[contract_detail_services]',
        ];

        $teams = $this->manager->getRepository(Team::class)
            ->findBy([
                'contract' => $myContract
            ]);

        if (!empty($teams)) {
            foreach ($teams as $team) {
                $schedule = $this->manager->getRepository(SchoolTeamSchedule::class)
                    ->findBy([
                        'team' => $team
                    ]);

                if (!empty($schedule)) {

                    // hours

                    $hours = 0;

                    foreach ($schedule as $dayTime) {
                        $interval = $dayTime->getStartTime()->diff($dayTime->getEndTime());
                        if ($interval) {
                            $hours += (int) $interval->format('%H');
                        }
                    }

                    if ($hours > 0) {
                        $data['contract_hours'] = sprintf(
                            '%d %s',
                            $hours,
                            ($hours > 1 ? 'horas semanais' : 'hora semanal')
                        );
                    }

                    // schedule

                    $schedules = [];

                    foreach ($schedule as $dayTime) {
                        $schedules[] = sprintf(
                            '%s das %s às %s',
                            $this->daysWeek[$dayTime->getWeekDay()],
                            $dayTime->getStartTime()->format('H:i'),
                            $dayTime->getEndTime()->format('H:i')
                        );
                    }

                    if (!empty($schedules)) {
                        $data['contract_schedule'] = implode(', ', $schedules);
                    }
                }
            }

            // modality

            if ($modality = $teams[0]->getType()) {
                switch ($modality) {
                    case 'ead':
                        $data['contract_modality'] = 'na modalidade ONLINE';
                        break;
                    case 'school':
                        $data['contract_modality'] = 'nas dependencias da CONTRATADA';
                        break;
                    case 'company':
                        $data['contract_modality'] = 'nas dependencias do CONTRATANTE';
                        break;
                }
            }
        }

        // place

        $company = $this->getCompany();
        if ($company !== null) {
            $address = $this->getPeopleAddress($company);
            if (is_array($address) && isset($address['city'])) {
                $data['contract_place'] = $address['city'];
            }
        }

        // detail services

        $items  = [];
        $payers = $this->getContractPeoplePayers($myContract);

        $contractProducts = $this->manager->getRepository(MyContractProduct::class)
            ->getContractProducts($myContract->getId());

        //// total per month
        $products = array_filter(
            $contractProducts,
            function ($contractProduct) {
                return $contractProduct['product_subtype'] == 'Package'
                    && $contractProduct['billing_unit'] == 'Monthly';
            }
        );
        $total = 0;
        foreach ($products as $product) {
            $total += (float) $product['product_price'] * (int) $product['quantity'];
        }
        $items[] = sprintf(
            'Mensalidade de %s aplicada para os meses que seguem o contrato '
                . 'tendo seu vencimento para todo dia %s.',
            Formatter::money($total),
            !empty($payers) ? $payers[0]['paymentDay'] : '00'
        );
        //// contract tax
        $products = array_filter(
            $contractProducts,
            function ($contractProduct) {
                return $contractProduct['product_type'] == 'Registration'
                    && $contractProduct['billing_unit'] == 'Single';
            }
        );
        $product = current($products);
        $total   = /*(float) $product['product_price'] * (int) $product['quantity']*/ 10;
        $parcels = /*(int) $product['parcels']*/ 0;
        if ($parcels > 1) {
            $items[] = sprintf(
                'Taxa administrativa: %s será '
                    . 'parcelada em %sx nas primeiras cobranças.',
                Formatter::money($total),
                $parcels
            );
        } else {
            $items[] = sprintf(
                'Taxa administrativa: %s será paga à vista '
                    . '(ou parcelada em 2x nas primeiras cobranças)',
                Formatter::money($total)
            );
        }

        if (!empty($items)) {
            $data['contract_detail_services'] = '';
            foreach ($items as $description) {
                $data['contract_detail_services'] .= '<li>' . $description . '</li>';
            }
        }

        return $data;
    }

    protected function getCompanyData(MyContract $myContract): array
    {
        $data = [
            'company_name'          => '[company_name]',
            'company_alias'         => '[company_alias]',
            'company_cnpj'          => '[company_cnpj]',
            'company_address'       => '[company_address]',
            'company_cep'           => '[company_cep]',
            'company_small_address' => '[company_small_address]',
            'company_owners'        => '[company_owners]',
        ];

        $company = $this->getCompany();
        $contact = $this->getContactByPeople($company);


        $data['company_name']  = $company->getName();
        $data['company_alias'] = $company->getAlias();

        // get CNPJ

        if (!empty($contact['documents'])) {
            if (isset($contact['documents']['CNPJ'])) {
                $data['company_cnpj'] = $contact['documents']['CNPJ'];
            }
        }

        // get address

        if (isset($contact['address'])) {
            $result = $this->getAddressVars('company', $contact['address']);

            $data["company_address"]       = $result["company_address"];
            $data["company_cep"]           = $result["company_cep"];
            $data["company_small_address"] = $result["company_small_address"];
        }


        /*
        // get owners

        $owners = $company->getCompany()->filter(function ($peopleLink) {
            return $peopleLink->getPeopleRole() == 'owner';
        });;

        if (!$owners->isEmpty()) {
            $ownersContact = [];

            foreach ($owners as $owner) {
                $ownerContact = $this->getContactByPeople($owner->getPeople());

                // name

                $ownerData = sprintf('%s %s', trim($ownerContact['name']), trim($ownerContact['alias']));

                // particulars

                $particulars = $this->manager->getRepository(Particulars::class)
                    ->getParticularsByPeopleAndContext($owner->getPeople(), 'contract_document');
                if (!empty($particulars)) {
                    $particularsValues = [];

                    foreach ($particulars as $particular) {
                        if ($particular['type_value'] == 'Naturalidade') {
                            $particularsValues[] = 'natural de ' . $particular['value'];
                        } else {
                            $particularsValues[] = $particular['value'];
                        }
                    }

                    if (!empty($particularsValues)) {
                        $ownerData .= ', ' . implode(', ', $particularsValues);
                    }
                }

                // documents

                if (!empty($ownerContact['documents'])) {
                    if (isset($ownerContact['documents']['R.G'])) {
                        $ownerData .= sprintf(
                            ', portador da cédula de identidade nº %s',
                            $ownerContact['documents']['R.G']
                        );
                    }

                    if (isset($ownerContact['documents']['CPF']) && !empty($ownerContact['documents']['CPF'])) {
                        $ownerData .= sprintf(
                            ', inscrito no CPF/MF %s',
                            Formatter::document($ownerContact['documents']['CPF'])
                        );
                    }
                }

                $ownersContact[] = $ownerData;
            }

            $lastOwner              = array_pop($ownersContact);
            $data['company_owners'] = implode(', ', $ownersContact) . ' e ' . $lastOwner;
        }
        */

        return $data;
    }

    protected function getCompany(): ?People
    {
        $id = $this->company_id ?: $this->request->query->get('company', null);
        if (!empty($id)) {
            $company = $this->manager->getRepository(People::class)->find($id);
            if ($company === null) {
                throw new \InvalidArgumentException('Company not found');
            }

            return $company;
        }

        return null;
    }

    protected function getContractTotalPrice(MyContract $myContract): float
    {
        $total = 0;

        // monthly products

        $contractProduct = $myContract->getContractProduct()
            ->filter(function ($contractProduct) {
                return $contractProduct->getProduct()->getProductSubtype() == 'Package'
                    && $contractProduct->getProduct()->getBillingUnit() == 'Monthly'
                    && $contractProduct->getPrice() > 0;
            });

        foreach ($contractProduct as $cproduct) {
            $total += $cproduct->getPrice() * $cproduct->getQuantity();
        }

        // contract tax

        $contractProduct = $myContract->getContractProduct()
            ->filter(function ($contractProduct) {
                return $contractProduct->getProduct()->getProductType() == 'Registration'
                    && $contractProduct->getProduct()->getBillingUnit() == 'Single'
                    && $contractProduct->getPrice() > 0;
            });

        foreach ($contractProduct as $cproduct) {
            $total += $cproduct->getPrice() * $cproduct->getQuantity();
        }

        return (float) $total;
    }

    protected function getContactByPeople(People $people): array
    {
        $documents = [];
        foreach ($people->getDocument() as $document) {
            $documents[$document->getDocumentType()->getDocumentType()] = $document->getDocument();
        }

        return [
            'id'        => $people->getId(),
            'name'      => $people->getName(),
            'alias'     => $people->getAlias(),
            'documents' => $documents,
            'address'   => $this->getPeopleAddress($people),
        ];
    }

    protected function getPeopleAddress(People $people): ?array
    {
        if (($address = $people->getAddress()->first()) === false)
            return null;

        return $this->getAddress($address);
    }

    /**
     * @param Address|City $address
     */
    protected function getAddress($address): ?array
    {

        $isAddress = true;

        $street   = null;
        $district = null;
        $city     = null;
        $state    = null;

        if (!empty($address)) {
            if ($address instanceof City) {
                $isAddress = false;
                $city      = $address;
                $state     = $address->getState();
            } else {
                $street   = $address->getStreet();
                $district = $street->getDistrict();
                $city     = $district->getCity();
                $state    = $city->getState();
            }
        } else {
            $isAddress = false;
            return null;
        }


        return [
            'id'         => $isAddress        ? $address->getId() : '',
            'country'    => !empty($state)    ? $this->fixCountryName($state->getCountry()->getCountryName()) : '',
            'state'      => !empty($state)    ? $state->getUF() : '',
            'city'       => !empty($city)     ? $city->getCity() : '',
            'district'   => !empty($district) ? $district->getDistrict() : '',
            'postalCode' => !empty($street)   ? $this->fixPostalCode($street->getCep()->getCep()) : '',
            'street'     => !empty($street)   ? $street->getStreet() : '',
            'number'     => $isAddress        ? $address->getNumber() : '',
            'complement' => $isAddress        ? $address->getComplement() : '',
        ];
    }

    protected function fixCountryName(string $originalName): string
    {
        return strtolower($originalName) == 'brazil' ? 'Brasil' : $originalName;
    }

    protected function fixPostalCode(int $postalCode): string
    {
        $code = (string)$postalCode;
        return strlen($code) == 7 ? '0' . $code : $code;
    }

    protected function getContractPeoplePayers(MyContract $contract): array
    {
        $contractPeople = $contract->getContractPeople()
            ->filter(function ($contractPeople) {
                return $contractPeople->getPeopleType() == 'Payer' && $contractPeople->getContractPercentage() > 0;
            });

        $payers = [];

        if (!$contractPeople->isEmpty()) {
            foreach ($contractPeople as $cpeople) {
                $payers[] = [
                    'people'     => $cpeople->getPeople(),
                    'percent'    => $cpeople->getContractPercentage(),
                    'paymentDay' => $cpeople->getPeople()->getPaymentTerm()
                ];
            }
        }

        return $payers;
    }
}
