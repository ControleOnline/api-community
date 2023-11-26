<?php

namespace App\Controller;

use App\Library\Company\CompanyProviderBalancer;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


class CompanyCnpjAction
{

    public function __construct()
    {
    }

    public function __invoke(array $data, Request $request): JsonResponse
    {
        $items = [];
        $input = $request->get('input', false);

        try {
            $provider = new CompanyProviderBalancer();
            $cnpjData  = $provider->search(preg_replace('/[^0-9]/', '', $input));
            $items    = [
                'cnpj'  => $cnpjData->getCnpj(),
                'razao_social'  => $cnpjData->getNome(),
                'nome_fantasia'  => $cnpjData->getFantasia(),
                'tipo'  => $cnpjData->getTipo(),
                'capital_social'  => $cnpjData->getCapitalSocial(),
                'porte'  => $cnpjData->getPorte(),
                'data_abertura'  => DateTime::createFromFormat('d/m/Y', $cnpjData->getAbertura())->format('Y-m-d'),
                'natureza_juridica'  => $cnpjData->getNaturezaJuridica(),
                'ultima_atualizacao'  => $cnpjData->getUltimaAtualizacao(),
                'status'  => $cnpjData->getStatus(),
                'ente_federativo_responsavel'  => $cnpjData->getEfr(),
                'situacao' => [
                    'situacao'  => $cnpjData->getSituacao(),
                    'data_situacao'  =>  DateTime::createFromFormat('d/m/Y', $cnpjData->getDataSituacao())->format('Y-m-d'),
                    'motivo_situacao'  => $cnpjData->getMotivoSituacao(),
                    'situacao_especial'  => $cnpjData->getSituacaoEspecial(),
                    'data_situacao_especial'  => $cnpjData->getDataSituacaoEspecial(),
                ],
                'contatos' => [
                    'telefone'  => $cnpjData->getTelefone(),
                    'email'  => $cnpjData->getEmail(),
                    'quadro_societario'  => $cnpjData->getQsa(),
                    'endereco' => [
                        'cep'  => $cnpjData->getCep(),
                        'logradouro'  => $cnpjData->getlogradouro(),                        
                        'numero'  => $cnpjData->getNumero(),
                        'complemento'  => $cnpjData->getComplemento(),
                        'bairro'  => $cnpjData->getBairro(),
                        'municipio'  => $cnpjData->getMunicipio(),
                        'uf'  => $cnpjData->getUf(),
                    ],
                ],
                'atividades' => [
                    'atividade_principal'  => $cnpjData->getAtividadePrincipal(),
                    'atividades_secundarias'  => $cnpjData->getAtividadesSecundarias(),
                ],
                'outras_informacoes'  => $cnpjData->getExtra(),
            ];

            return new JsonResponse([
                'response' => [
                    'data'    => $items,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {

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
}
