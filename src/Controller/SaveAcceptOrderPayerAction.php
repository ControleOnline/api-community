<?php

namespace App\Controller;

use ControleOnline\Entity\Cep;
use ControleOnline\Entity\City;
use ControleOnline\Entity\State;
use ControleOnline\Entity\Phone;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Street;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Document;
use ControleOnline\Entity\District;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\DocumentType;
use ControleOnline\Entity\SalesOrder;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\Quotation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use ControleOnline\Entity\Contract;
use ControleOnline\Entity\ContractModel;
use ControleOnline\Entity\ContractPeople;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Repository\ContractRepository;

class SaveAcceptOrderPayerAction extends AbstractController
{
        /**
         * Entity Manager
         *
         * @var EntityManagerInterface
         */
        private $manager = null;

        public function __construct(EntityManagerInterface $manager)
        {
            $this->manager = $manager;
        }

    /**
   *
   * @Route("/accept-order-payer/save/{id}","POST")
   */
    public function saveAcceptOrder(Request $request): JsonResponse
    {

        try {

            $people_domain = $request->headers->get('app-domain');

            if (empty($people_domain)){
                throw new BadRequestHttpException('domain is not found');

            }
            /**
             * @var string $id
             */
            $id      = $request->get('id', null);
            $payload = json_decode($request->getContent(), true);

            /**
             * @var Quotation $order
             */
            $quote = $this->manager->getRepository(Quotation::class)->find($id);

            if (empty($quote)) {
                throw new BadRequestHttpException('Quote is not found');
            }
            /**
             * @var SalesOrder $order
             */
            $order = $this->manager->getRepository(SalesOrder::class)
                ->find($quote->getOrder()->getId());

            if (empty($order)) {
                throw new BadRequestHttpException('order is not found');
            }

            $payer = $order->getPayer();
            if (!empty($payer))
                return new JsonResponse([
                    'response' => [
                        'data'    => [],
                        'count'   => 0,
                        'error'   => 'Payer has already been provided',
                        'success' => false,
                    ],
                ]);



            $document = $this->getDocument($payload);
            $email = $this->getEmail($payload);
            $phone = $this->getPhone($payload);

            $this->manager->getConnection()->beginTransaction();
            /**
             * Tentando achar o pagador pelos dados chave
             */

            if ($document) {
                $payer = $document->getPeople();
            }
            if (empty($payer) && !empty($email)) {
                $payer = $email->getPeople();
            }
            if (empty($payer) && !empty($phone)) {
                $payer = $phone->getPeople();
            }

            /**
             * Se nenhum dos dados tiver cadastrado, crie uma nova people
             */
            if (empty($payer)) {
                $lang = $this->manager->getRepository(Language::class)
                    ->findOneBy([
                        'language' => 'pt-BR'
                    ]);

                $payer = new People();
                $payer->setEnabled(1);
                $payer->setLanguage($lang);
                $payer->setPeopleType($payload["personType"] == "PF" ? "F" : "J");
            }

            $payer->setName($payload["name"]);
            $payer->setAlias($payload["alias"]);

            $payer->setFoundationDate(\DateTime::createFromFormat(
                'Y-m-d',
                date(implode('-', array_reverse(explode('/', $payload['birthDate']))))
            ));

            $this->manager->persist($payer);
            $this->manager->flush();

            /**
             * Caso algum dado chave exista, mude para que todos eles tenham o mesmo ID
             */
            if (!empty($document) && $payer->getId() != $document->getPeople()->getId()) {
                $document->setPeople($payer);
                $this->manager->persist($document);
                $this->manager->flush();
            }

            if (!empty($email) && $payer->getId() != $email->getPeople()->getId()) {
                $email->setPeople($payer);
                $this->manager->persist($email);
                $this->manager->flush();
            }

            if (!empty($phone) && $payer->getId() != $phone->getPeople()->getId()) {
                $phone->setPeople($payer);
                $this->manager->persist($phone);
                $this->manager->flush();
            }

            /**
             * Se algum dos dados chave não existirem, crio
             */

            if (!$document && $payer) {
                $this->addDocument($payload, $payer);
            }
            if (!$email && $payer) {
                $this->addEmail($payload, $payer);
            }
            if (!$phone && $payer) {
                $this->addPhone($payload, $payer);
            }


            $address = $payload["address"];

            $cep = $this->manager->getRepository(Cep::class)
                ->findOneBy([
                    "cep" => $address["postal_code"]
                ]);

            if (empty($cep)) {
                $cep = new Cep();

                $cep->setCep($address["postal_code"]);

                $this->manager->persist($cep);
                $this->manager->flush();
            }

            $state = $this->manager->getRepository(State::class)
                ->findOneBy([
                    "uf" => $address["state"]
                ]);

            if (empty($state)) {
                throw new BadRequestHttpException('state is not valid');
            }

            $city = $this->manager->getRepository(City::class)
                ->findOneBy([
                    "city" => $address["city"],
                    "state" => $state
                ]);

            if (empty($city)) {
                $city = new City();

                $city->setState($state);
                $city->setCity($address["city"]);

                $this->manager->persist($city);
                $this->manager->flush();
            }

            $district = $this->manager->getRepository(District::class)
                ->findOneBy([
                    "district" => $address["district"],
                    "city" => $city
                ]);

            if (empty($district)) {
                $district = new District();

                $district->setCity($city);
                $district->setDistrict($address["district"]);

                $this->manager->persist($district);
                $this->manager->flush();
            }

            $street = $this->manager->getRepository(Street::class)
                ->findOneBy([
                    "street" => $address["street"]
                ]);

            if (empty($street)) {
                $street = new Street();

                $street->setCep($cep);
                $street->setStreet($address["street"]);
                $street->setDistrict($district);
                $street->setCep($cep);

                $this->manager->persist($street);
                $this->manager->flush();
            }

            /**
             * @var Address $newAddress
             */
            $newAddress = $payer->getAddress();


            if (empty($newAddress) || count($newAddress) === 0) {

                $newAddress = new Address();
                $newAddress->setNickname('Endereço Principal');
                $newAddress->setNumber($address["number"]);
                $newAddress->setComplement($address["complement"]);
                $newAddress->setPeople($payer);
                $newAddress->setStreet($street);

                $this->manager->persist($newAddress);
                $this->manager->flush();
            } else if (!empty($newAddress) && count($newAddress) > 0) {

                /**
                 * @var Address $newAddress
                 */
                $newAddress = $newAddress[0];
            }

            $order->setPayer($payer);

            $order->addOtherInformations('carColor', $payload['other_informations']['carColor']);
            $order->addOtherInformations('carNumber', $payload['other_informations']['carNumber']);
            $order->addOtherInformations('renavan', $payload['other_informations']['renavan']);
            // $order->addOtherInformations('paymentType', $payload['other_informations']['paymentType']);
            $order->setStatus($this->getStatus($order));


            $contractModel = $this->manager->getRepository(ContractModel::class)->findOneBy([
                'peopleId' => $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $people_domain])->getPeople(),            
            ]);

            if (empty($contractModel))
                throw new BadRequestHttpException('contractModel not found');

            $newContract = new Contract();
            $newContract->setContractStatus('Waiting approval');
            $newContract->setContractModel($contractModel);

            $this->manager->persist($newContract);
            $this->manager->flush();



            $newContractPeople = new ContractPeople();

            $newContractPeople->setContract($newContract);
            $newContractPeople->setPeople($order->getProvider());
            $newContractPeople->setPeopleType('Provider');

            $this->manager->persist($newContractPeople);
            $this->manager->flush();

            $newContractPayer = new ContractPeople();

            $newContractPayer->setContract($newContract);
            $newContractPayer->setPeople($order->getPayer());
            $newContractPayer->setPeopleType('Payer');
            $newContractPayer->setContractPercentage(100);

            $this->manager->persist($newContractPayer);
            $this->manager->flush();


            $order->setContract($newContract);
            $this->manager->persist($order);
            $this->manager->flush();


            $this->manager->getConnection()->commit();

            return new JsonResponse([
                'response' => [
                    'data'    => [
                        "payerId" => $payer->getId(),
                        "orderId" => $order->getId(),
                        "contractId" => $newContract->getId()
                    ],
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }

    private function getDocument($payload): ?Document
    {
        return $this->manager->getRepository(Document::class)
            ->findOneBy([
                "document" => $payload["document"]
            ]);
    }


    private function addDocument($payload, People $payer): Document
    {

        $document = new Document();
        /**
         * @var DocumentType $docType
         */
        $docType = $this->manager->getRepository(DocumentType::class)
            ->findOneBy([
                "documentType" => $payload["personType"] == "PF" ? "CPF" : "CNPJ"
            ]);
        $document->setDocumentType($docType);
        $document->setDocument($payload["document"]);
        $document->setPeople($payer);

        $this->manager->persist($document);
        $this->manager->flush();

        return $document;
    }

    private function getEmail($payload): ?Email
    {
        return $this->manager->getRepository(Email::class)
            ->findOneBy([
                "email" => $payload["email"]
            ]);
    }

    private function addEmail($payload, People $payer): Email
    {
        $email = new Email();
        $email->setEmail($payload["email"]);
        $email->setPeople($payer);
        $this->manager->persist($email);
        $this->manager->flush();
        return $email;
    }


    private function getPhone($payload): ?Phone
    {
        $ddd = substr($payload["phone"], 0, 2);
        $number = str_replace($ddd, "", $payload["phone"]);

        return $this->manager->getRepository(Phone::class)
            ->findOneBy([
                "ddd" => $ddd,
                "phone" => $number
            ]);
    }

    private function addPhone($payload, People $payer): Phone
    {
        $ddd = substr($payload["phone"], 0, 2);
        $number = str_replace($ddd, "", $payload["phone"]);


        $phone = new Phone();


        $phone->setPeople($payer);
        $phone->setDdd($ddd);
        $phone->setPhone($number);

        $this->manager->persist($phone);
        $this->manager->flush();
        return $phone;
    }


    protected function getStatus(SalesOrder $order)
    {
        if ($order->getStatus()->getStatus() == 'quote' || $order->getStatus()->getStatus() == 'proposal sent' || !$order->getDeliveryPeople()) {
            return $this->manager->getRepository(Status::class)
                ->findOneBy(array(
                    'status' => 'waiting client invoice tax'
                ));
        } else {
            return $this->manager->getRepository(Status::class)
                ->findOneBy(array(
                    'status' => 'automatic analysis'
                ));
        }
    }
}
