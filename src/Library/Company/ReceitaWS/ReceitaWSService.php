<?php

namespace App\Library\Company\ReceitaWS;

use App\Library\Company\Entity\Company;
use App\Library\Company\CompanyService;
use GuzzleHttp\Client;
use App\Library\Company\Exception\InvalidParameterException;
use App\Library\Company\Exception\ProviderRequestException;

class ReceitaWSService implements CompanyService
{
    private $endpoint = 'https://www.receitaws.com.br/v1/cnpj/';

    public function __construct()
    {
    }

    public function query(string $cnpj): Company
    {

        $result = $this->search($cnpj);

        return (new Company)



            ->setAtividadePrincipal($result->atividade_principal)
            ->setDataSituacao($result->data_situacao)
            ->setFantasia($result->fantasia)
            ->setComplemento($result->complemento)
            ->setTipo($result->tipo)
            ->setNome($result->nome)
            ->setTelefone($result->telefone)
            ->setEmail($result->email)
            ->setAtividadesSecundarias($result->atividades_secundarias)
            ->setQsa($result->qsa)
            ->setSituacao($result->situacao)
            ->setBairro($result->bairro)
            ->setlogradouro($result->logradouro)
            ->setNumero($result->numero)
            ->setCep($result->cep)
            ->setMunicipio($result->municipio)
            ->setPorte($result->porte)
            ->setAbertura($result->abertura)
            ->setNaturezaJuridica($result->natureza_juridica)
            ->setUf($result->uf)
            ->setCnpj($result->cnpj)
            ->setUltimaAtualizacao($result->ultima_atualizacao)
            ->setStatus($result->status)
            ->setEfr($result->efr)
            ->setMotivoSituacao($result->motivo_situacao)
            ->setSituacaoEspecial($result->situacao_especial)
            ->setDataSituacaoEspecial($result->data_situacao_especial)
            ->setCapitalSocial($result->capital_social)
            ->setExtra($result->extra);
    }

    private function search(string $cnpj): object
    {
        try {
            $client   = new Client(['verify' => false]);
            $response = $client->request('GET',sprintf('%s%s', $this->endpoint, $cnpj));
            $result   = json_decode($response->getBody());
            if (isset($result->cnpj)) {
                return $result;
            }

            throw new ProviderRequestException('Receitaws response format error');
        } catch (\Exception $e) {
            throw new ProviderRequestException($e->getMessage());
        }
    }
}
