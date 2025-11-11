<?php

namespace App\Infra\PrefeituraSaoPaulo;

use App\Infra\PrefeituraSaoPaulo\XmlProcessing\BuildCancel;
use App\Infra\PrefeituraSaoPaulo\XmlProcessing\BuildNfeInquiry;
use App\Infra\PrefeituraSaoPaulo\XmlProcessing\BuildRps;
use App\Infra\PrefeituraSaoPaulo\XmlProcessing\BuildRpsBatch;
use App\Infra\PrefeituraSaoPaulo\XmlProcessing\SignXml;

class NfseSynchronousClientInfra
{
    protected string $endpointNF;
    protected string $endpointNFTS;
    protected string $cnpj;
    protected string $municipalRegistration;
    protected array $certificates;
    protected RequestApi $requestApi;

    public function __construct()
    {
        $this->cnpj = config("nfse.company.cnpj");
        $this->municipalRegistration = config("nfse.company.municipalRegistration");
        $this->endpointNF = config("nfse.endpoint.NF");
        $this->endpointNFTS = config("nfse.endpoint.NFTS");
        $this->requestApi = new RequestApi(config("nfse.certificate.certPath"), config("nfse.certificate.keyPath"));
        $this->certificates = [
            'certPass' => config("nfse.certificate.pemPassword"),
            'certPath' => config("nfse.certificate.certPath"),
            'keyPath' => config("nfse.certificate.keyPath")
        ];
    }

    public function RPSSubmission(array $rpsData): array
    {
        $buildRpsXml = new BuildRps($this->cnpj, $this->municipalRegistration, $this->certificates);
        $xml = $buildRpsXml->build($rpsData);

        return $this->requestApi->request('EnvioRPS', $xml, $this->endpointNF, "http://www.prefeitura.sp.gov.br/nfe/wsdl/EnvioRPS");
    }

    public function RPSBatchSubmission(array $rpsList): array
    {
        $buildRpsBatchXml = new BuildRpsBatch($this->cnpj, $this->municipalRegistration, $this->certificates);
        $xml = $buildRpsBatchXml->build($rpsList);
        return $this->requestApi->request('EnvioLoteRPS', $xml, $this->endpointNF, "http://www.prefeitura.sp.gov.br/nfe/wsdl/EnvioLoteRPS");
    }

    public function RPSBatchSubmissionTest(array $rpsList): array
    {
        $buildRpsBatchXml = new BuildRpsBatch($this->cnpj, $this->municipalRegistration, $this->certificates);
        $xml = $buildRpsBatchXml->build($rpsList);
        return $this->requestApi->request('TesteEnvioLoteRPS', $xml, $this->endpointNF, "http://www.prefeitura.sp.gov.br/nfe/wsdl/TesteEnvioLoteRPS");
    }

    public function NFeInquiry(string $invoiceNumber, string $verificationCode)
    {
        $buildNfeInquiryXml = new BuildNfeInquiry($this->cnpj, $this->municipalRegistration, $this->certificates);
        $xml = $buildNfeInquiryXml->build($invoiceNumber, $verificationCode);
        return $this->requestApi->request('ConsultaNFeRequest', $xml, $this->endpointNF, "http://www.prefeitura.sp.gov.br/nfe/wsdl/ConsultaNFe");
    }

    public function NFeCancellation(string $invoiceNumber, string $verificationCode): array
    {
        $buildCancelXml = new BuildCancel($this->cnpj, $this->municipalRegistration, $this->certificates);
        $xml = $buildCancelXml->build($invoiceNumber, $verificationCode);
        return $this->requestApi->request('CancelamentoNFe', $xml, $this->endpointNF, "http://www.prefeitura.sp.gov.br/nfe/wsdl/CancelamentoNFe");
    }

}
