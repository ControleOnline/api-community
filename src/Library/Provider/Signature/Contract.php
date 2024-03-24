<?php

namespace App\Library\Provider\Signature;

use ControleOnline\Entity\People;
use App\Library\Provider\Signature\Document;
use App\Library\Provider\Signature\Signer;
use App\Service\SignatureService;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\MyContract;
use App\Library\Provider\Signature\Exception\InvalidParameterException;
use App\Library\Provider\Signature\Exception\ProviderRequestException;
use App\Library\Exception\MissingDataException;
use ControleOnline\Entity\PeopleDomain;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Library\Provider\Signature\ContractDocument;


class Contract
{
    protected $manager;

    protected $request;

    protected $signature;

    protected $signatureProvider;


    public function __construct(
        EntityManagerInterface $entityManager,
        SignatureService       $signature
    ) {
        $this->manager   = $entityManager;
        $this->signature = $signature;
    }


    public function sign(MyContract $data)
    {

        $this->signatureProvider = $this->signature->getFactory();

        if ($this->signatureProvider === null) {
            $data->setContractStatus('Waiting approval');
        } else {

            try {

                // config document
                $contractDocument = new ContractDocument();
                $contractDocument->setEntityManager($this->manager);
                $contractDocument->setCompanyId($data->getContractPeople()
                    ->filter(function ($contractPeople) {
                        return $contractPeople->getPeopleType() == 'Provider';
                    })[0]->getPeople()->getId());

                $contractDocument->getContractContent($data);

                $document = ($this->signatureProvider->createDocument())
                    ->setFileName(
                        sprintf('Contrato-%s', $this->getContractContractorSignerName($data))
                    )
                    ->setContent($this->getContractPDFContent($data))
                    ->setDeadlineAt(
                        (new \DateTime('now'))
                            ->add(new \DateInterval('P7D'))
                            ->format('c')
                    );

                // config signers

                $this->addDocumentSignersFromContract($document, $data);

                // create document in cloud service

                $this->signatureProvider->saveDocument($document);

                // update contract status

                $data->setContractStatus('Waiting signatures');
                $data->setDocKey($document->getKey());

                $this->manager->persist($data);
                $this->manager->flush($data);
            } catch (InvalidParameterException $e) {
                throw new \Exception($e->getMessage());
            } catch (ProviderRequestException $e) {
                throw new \Exception($e->getMessage());
            } catch (MissingDataException $e) {
                throw new \Exception($e->getMessage());
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return $data;
    }
    protected function addDocumentSignersFromContract(Document $document, MyContract $contract)
    {
        if ($contract->getContractPeople()->isEmpty()) {
            throw new MissingDataException('Este contrato não tem assinantes');
        }

        // add providers


        $contractProviders = $contract->getContractPeople()
            ->filter(function ($contractPeople) {
                return $contractPeople->getPeopleType() == 'Provider';
            });
        if ($contractProviders->isEmpty()) {
            throw new MissingDataException('O prestador de serviços não foi definido');
        }

        foreach ($contractProviders as $provider) {
            $document->addSigner(
                $this->getSignerFromPeople($provider->getPeople(), 'prestador de serviços')
            );
        }

        // add the rest

        $contractParticipants = $contract->getContractPeople()
            ->filter(function ($contractPeople) {
                return $contractPeople->getPeopleType() != 'Provider';
            });
        if ($contractParticipants->isEmpty()) {
            throw new MissingDataException(
                'Devem existir pelo menos 1 assinante no contrato'
            );
        }

        foreach ($contractParticipants as $participant) {
            $document->addSigner(
                $this->getSignerFromPeople($participant->getPeople(), 'assinante')
            );
        }
    }

    protected function getContractContractorSignerName(MyContract $contract): string
    {
        $contractPayers = $contract->getContractPeople()
            ->filter(function ($contractPeople) {
                return $contractPeople->getPeopleType() == 'Payer';
            });
        if ($contractPayers->isEmpty()) {
            throw new MissingDataException(
                'Devem existir pelo menos 1 assinante como contratante'
            );
        }

        return $contractPayers->first()->getPeople()->getFullName();
    }

    protected function getContractPDFContent(MyContract $contract): string
    {
        $content = $contract->getHtmlContent();

        if (empty($content)) {
            throw new \Exception(
                sprintf('Houve um erro ao gerar o PDF')
            );
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $html = $dompdf->output();

        return $html;
    }

    protected function getSignerFromPeople(People $people, string $role): Signer
    {
        $signer = $this->signatureProvider->createSigner();

        $signer->setKey($people->getId());
        $signer->setName($people->getFullName());

        if (($email = $people->getOneEmail()) === null) {
            throw new MissingDataException(
                sprintf('O %s "%s" não possui um email', $role, $people->getFullName())
            );
        }

        $signer->setEmail($email->getEmail());

        if ($people->isPeople()) {
            $signer->setHasCPF(true);

            if (($document = $people->getOneDocument()) === null) {
                throw new MissingDataException(
                    sprintf('O %s "%s" não possui um CPF/CNPJ', $role, $people->getFullName())
                );
            }

            $signer->setCPF($document->getDocument());
            if (($birthday = $people->getBirthdayAsString()) === null) {
                throw new MissingDataException(
                    sprintf(
                        'O %s "%s" não tem data de nascimento definida',
                        $role,
                        $people->getFullName()
                    )
                );
            }

            $signer->setBirthday($birthday);
        }

        return $signer;
    }
}
