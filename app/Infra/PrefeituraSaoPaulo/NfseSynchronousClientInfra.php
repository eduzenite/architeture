<?php

namespace App\Infra\PrefeituraSaoPaulo;

class NfseSynchronousClientInfra
{
    protected string $endpointNF;
    protected string $endpointNFAsync;
    protected string $endpointNFTS;
    protected string $certPath;
    protected string $certPass;
    protected string $cacertPath;
    protected string $cnpj;
    protected string $municipalRegistration;

    public function __construct()
    {
        $this->certPath = config("nfse.certificate.pemPath");
        $this->certPass = config("nfse.certificate.pemPassword");
        $this->cacertPath = config("nfse.certificate.cacertPath");
        $this->cnpj = config("nfse.company.cnpj");
        $this->municipalRegistration = config("nfse.company.municipalRegistration");
        $this->endpointNF = config("nfse.endpoint.NF");
        $this->endpointNFAsync = config("nfse.endpoint.NFAsync");
        $this->endpointNFTS = config("nfse.endpoint.NFTS");
    }

    /**
     * Envia um RPS.
     */
    public function sendRPS()
    {
        // Operação SOAP: EnviarLoteRPS (Geralmente)
        return [];
    }

    /**
     * Envia um Lote de RPS.
     */
    public function sendBatchRPS()
    {
        // Operação SOAP: EnviarLoteRPS
    }

    /**
     * Consulta uma NF-e específica por número e código de verificação.
     *
     * @param string $invoiceNumber
     * @param string $verificationCode
     * @return array
     */
    public function consultNFe(string $invoiceNumber, string $verificationCode)
    {
        //
    }

    public function consultNFeReceived()
    {
        // Operação SOAP: ConsultaNFeRecebidas
    }

    public function consultNFeIssued()
    {
        // Operação SOAP: ConsultaNFeEmitidas
    }

    public function consultBatch()
    {
        // Operação SOAP: ConsultaLote (consulta o status de um lote)
    }

    public function consultBatchInfo()
    {
        // Operação SOAP: ConsultaInformacoesLote
    }

    public function consultCNPJ()
    {
        // Operação SOAP: ConsultaCPFCNPJ
    }

    public function cancelNFe()
    {
        // Operação SOAP: CancelamentoNFe (XML de Cancelamento Assinado)
    }
}
