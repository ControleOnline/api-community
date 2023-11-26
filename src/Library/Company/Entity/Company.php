<?php

namespace App\Library\Company\Entity;

class Company
{
    private $atividade_principal = null;
    private $data_situacao = null;
    private $fantasia = null;
    private $complemento = null;
    private $tipo = null;
    private $nome = null;
    private $telefone = null;
    private $email = null;
    private $atividades_secundarias = null;
    private $qsa = null;
    private $situacao = null;
    private $bairro = null;
    private $logradouro = null;
    private $numero = null;
    private $cep = null;
    private $municipio = null;
    private $porte = null;
    private $abertura = null;
    private $natureza_juridica = null;
    private $uf = null;
    private $cnpj = null;
    private $ultima_atualizacao = null;
    private $status = null;
    private $efr = null;
    private $motivo_situacao = null;
    private $situacao_especial = null;
    private $data_situacao_especial = null;
    private $capital_social = null;
    private $extra = null;

    /**
     * Get the value of atividade_principal
     */
    public function getAtividadePrincipal()
    {
        return $this->atividade_principal;
    }

    /**
     * Set the value of atividade_principal
     */
    public function setAtividadePrincipal($atividade_principal): self
    {
        $this->atividade_principal = $atividade_principal;

        return $this;
    }

    /**
     * Get the value of data_situacao
     */
    public function getDataSituacao()
    {
        return $this->data_situacao;
    }

    /**
     * Set the value of data_situacao
     */
    public function setDataSituacao($data_situacao): self
    {
        $this->data_situacao = $data_situacao;

        return $this;
    }

    /**
     * Get the value of fantasia
     */
    public function getFantasia()
    {
        return $this->fantasia;
    }

    /**
     * Set the value of fantasia
     */
    public function setFantasia($fantasia): self
    {
        $this->fantasia = $fantasia;

        return $this;
    }

    /**
     * Get the value of complemento
     */
    public function getComplemento()
    {
        return $this->complemento;
    }

    /**
     * Set the value of complemento
     */
    public function setComplemento($complemento): self
    {
        $this->complemento = $complemento;

        return $this;
    }

    /**
     * Get the value of tipo
     */
    public function getTipo()
    {
        return $this->tipo;
    }

    /**
     * Set the value of tipo
     */
    public function setTipo($tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    /**
     * Get the value of nome
     */
    public function getNome()
    {
        return $this->nome;
    }

    /**
     * Set the value of nome
     */
    public function setNome($nome): self
    {
        $this->nome = $nome;

        return $this;
    }

    /**
     * Get the value of telefone
     */
    public function getTelefone()
    {
        return preg_replace('/[^0-9]/', '', $this->telefone);
    }

    /**
     * Set the value of telefone
     */
    public function setTelefone($telefone): self
    {
        $this->telefone = preg_replace('/[^0-9]/', '', $telefone);

        return $this;
    }

    /**
     * Get the value of email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     */
    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of atividades_secundarias
     */
    public function getAtividadesSecundarias()
    {
        return $this->atividades_secundarias;
    }

    /**
     * Set the value of atividades_secundarias
     */
    public function setAtividadesSecundarias($atividades_secundarias): self
    {
        $this->atividades_secundarias = $atividades_secundarias;

        return $this;
    }

    /**
     * Get the value of qsa
     */
    public function getQsa()
    {
        return $this->qsa;
    }

    /**
     * Set the value of qsa
     */
    public function setQsa($qsa): self
    {
        $this->qsa = $qsa;

        return $this;
    }

    /**
     * Get the value of situacao
     */
    public function getSituacao()
    {
        return $this->situacao;
    }

    /**
     * Set the value of situacao
     */
    public function setSituacao($situacao): self
    {
        $this->situacao = $situacao;

        return $this;
    }

    /**
     * Get the value of bairro
     */
    public function getBairro()
    {
        return $this->bairro;
    }

    /**
     * Set the value of bairro
     */
    public function setBairro($bairro): self
    {
        $this->bairro = $bairro;

        return $this;
    }

    /**
     * Get the value of logradouro
     */
    public function getLogradouro()
    {
        return $this->logradouro;
    }

    /**
     * Set the value of logradouro
     */
    public function setLogradouro($logradouro): self
    {
        $this->logradouro = $logradouro;

        return $this;
    }

    /**
     * Get the value of numero
     */
    public function getNumero()
    {
        return preg_replace('/[^0-9]/', '',$this->numero);
    }

    /**
     * Set the value of numero
     */
    public function setNumero($numero): self
    {
        $this->numero = preg_replace('/[^0-9]/', '',$numero);

        return $this;
    }

    /**
     * Get the value of cep
     */
    public function getCep()
    {
        return preg_replace('/[^0-9]/', '', $this->cep);
    }

    /**
     * Set the value of cep
     */
    public function setCep($cep): self
    {
        $this->cep = preg_replace('/[^0-9]/', '', $cep);

        return $this;
    }

    /**
     * Get the value of municipio
     */
    public function getMunicipio()
    {
        return $this->municipio;
    }

    /**
     * Set the value of municipio
     */
    public function setMunicipio($municipio): self
    {
        $this->municipio = $municipio;

        return $this;
    }

    /**
     * Get the value of porte
     */
    public function getPorte()
    {
        return $this->porte;
    }

    /**
     * Set the value of porte
     */
    public function setPorte($porte): self
    {
        $this->porte = $porte;

        return $this;
    }

    /**
     * Get the value of abertura
     */
    public function getAbertura()
    {
        return $this->abertura;
    }

    /**
     * Set the value of abertura
     */
    public function setAbertura($abertura): self
    {
        $this->abertura = $abertura;

        return $this;
    }

    /**
     * Get the value of natureza_juridica
     */
    public function getNaturezaJuridica()
    {
        return $this->natureza_juridica;
    }

    /**
     * Set the value of natureza_juridica
     */
    public function setNaturezaJuridica($natureza_juridica): self
    {
        $this->natureza_juridica = $natureza_juridica;

        return $this;
    }

    /**
     * Get the value of uf
     */
    public function getUf()
    {
        return $this->uf;
    }

    /**
     * Set the value of uf
     */
    public function setUf($uf): self
    {
        $this->uf = $uf;

        return $this;
    }

    /**
     * Get the value of cnpj
     */
    public function getCnpj()
    {
        return $this->cnpj;
    }

    /**
     * Set the value of cnpj
     */
    public function setCnpj($cnpj): self
    {
        $this->cnpj = $cnpj;

        return $this;
    }

    /**
     * Get the value of ultima_atualizacao
     */
    public function getUltimaAtualizacao()
    {
        return $this->ultima_atualizacao;
    }

    /**
     * Set the value of ultima_atualizacao
     */
    public function setUltimaAtualizacao($ultima_atualizacao): self
    {
        $this->ultima_atualizacao = $ultima_atualizacao;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of efr
     */
    public function getEfr()
    {
        return $this->efr;
    }

    /**
     * Set the value of efr
     */
    public function setEfr($efr): self
    {
        $this->efr = $efr;

        return $this;
    }

    /**
     * Get the value of motivo_situacao
     */
    public function getMotivoSituacao()
    {
        return $this->motivo_situacao;
    }

    /**
     * Set the value of motivo_situacao
     */
    public function setMotivoSituacao($motivo_situacao): self
    {
        $this->motivo_situacao = $motivo_situacao;

        return $this;
    }

    /**
     * Get the value of situacao_especial
     */
    public function getSituacaoEspecial()
    {
        return $this->situacao_especial;
    }

    /**
     * Set the value of situacao_especial
     */
    public function setSituacaoEspecial($situacao_especial): self
    {
        $this->situacao_especial = $situacao_especial;

        return $this;
    }

    /**
     * Get the value of data_situacao_especial
     */
    public function getDataSituacaoEspecial()
    {
        return $this->data_situacao_especial;
    }

    /**
     * Set the value of data_situacao_especial
     */
    public function setDataSituacaoEspecial($data_situacao_especial): self
    {
        $this->data_situacao_especial = $data_situacao_especial;

        return $this;
    }

    /**
     * Get the value of capital_social
     */
    public function getCapitalSocial()
    {
        return $this->capital_social;
    }

    /**
     * Set the value of capital_social
     */
    public function setCapitalSocial($capital_social): self
    {
        $this->capital_social = $capital_social;

        return $this;
    }

    /**
     * Get the value of extra
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Set the value of extra
     */
    public function setExtra($extra): self
    {
        $this->extra = $extra;

        return $this;
    }
}
