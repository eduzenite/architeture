<?php

namespace App\Infra\PrefeituraSaoPaulo;

use Exception;
use Illuminate\Support\Facades\Log;

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

    protected XmlRequestBuilder $requestBuilder;
    protected XmlSigner $xmlSigner;
    protected SoapClient $soapClient;

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

        // Inicializa helpers
        $this->requestBuilder = new XmlRequestBuilder($this->cnpj, $this->municipalRegistration);
        $this->xmlSigner = new XmlSigner($this->certPath, $this->certPass);
        $this->soapClient = new SoapClient($this->certPath, $this->certPass, $this->cacertPath);
    }

    /**
     * Envia um RPS.
     *
     * @param array $rpsData Dados do RPS
     * @return array Resposta da API
     * @throws Exception
     */
    public function sendRPS(array $rpsData): array
    {
        try {
            // 1. Constrói o XML
            $xml = $this->requestBuilder->buildSendRPSRequest($rpsData);

            Log::info('NFSe SP - XML gerado', ['xml' => $xml]);

            // 2. Assina o XML
            $signedXml = $this->xmlSigner->signXml($xml, 'PedidoEnvioLoteRPS');

            Log::info('NFSe SP - XML assinado', ['signedXml' => $signedXml]);

            // 3. Envia via SOAP
            $response = $this->soapClient->sendRequest(
                $this->endpointNF,
                'http://www.prefeitura.sp.gov.br/nfe/EnviarLoteRPS',
                $signedXml
            );

            Log::info('NFSe SP - Resposta recebida', ['response' => $response]);

            // 4. Processa a resposta
            return $this->parseResponse($response);

        } catch (Exception $e) {
            Log::error('NFSe SP - Erro ao enviar RPS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Envia um Lote de RPS.
     *
     * @param array $rpsList Lista de RPS
     * @return array
     * @throws Exception
     */
    public function sendBatchRPS(array $rpsList): array
    {
        // Similar ao sendRPS, mas monta XML com múltiplos RPS
        throw new Exception('Método não implementado ainda');
    }

    /**
     * Consulta uma NF-e específica por número e código de verificação.
     *
     * @param string $invoiceNumber
     * @param string $verificationCode
     * @return array
     * @throws Exception
     */
    public function consultNFe(string $invoiceNumber, string $verificationCode): array
    {
        try {
            // 1. Constrói o XML
            $xml = $this->requestBuilder->buildConsultNFeRequest($invoiceNumber, $verificationCode);

            // 2. Assina o XML
            $signedXml = $this->xmlSigner->signXml($xml, 'PedidoConsultaNFe');

            // 3. Envia via SOAP
            $response = $this->soapClient->sendRequest(
                $this->endpointNF,
                'http://www.prefeitura.sp.gov.br/nfe/ConsultaNFe',
                $signedXml
            );

            // 4. Processa a resposta
            return $this->parseResponse($response);

        } catch (Exception $e) {
            Log::error('NFSe SP - Erro ao consultar NFe', [
                'invoiceNumber' => $invoiceNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Consulta NFSe recebidas
     *
     * @param array $filters Filtros de consulta (datas, etc)
     * @return array
     */
    public function consultNFeReceived(array $filters = []): array
    {
        // Implementar conforme documentação da API
        throw new Exception('Método não implementado ainda');
    }

    /**
     * Consulta NFSe emitidas
     *
     * @param array $filters Filtros de consulta (datas, etc)
     * @return array
     */
    public function consultNFeIssued(array $filters = []): array
    {
        // Implementar conforme documentação da API
        throw new Exception('Método não implementado ainda');
    }

    /**
     * Consulta o status de um lote
     *
     * @param string $batchNumber Número do lote
     * @return array
     * @throws Exception
     */
    public function consultBatch(string $batchNumber): array
    {
        try {
            // 1. Constrói o XML
            $xml = $this->requestBuilder->buildConsultBatchRequest($batchNumber);

            // 2. Assina o XML
            $signedXml = $this->xmlSigner->signXml($xml, 'PedidoConsultaLote');

            // 3. Envia via SOAP
            $response = $this->soapClient->sendRequest(
                $this->endpointNF,
                'http://www.prefeitura.sp.gov.br/nfe/ConsultaLote',
                $signedXml
            );

            // 4. Processa a resposta
            return $this->parseResponse($response);

        } catch (Exception $e) {
            Log::error('NFSe SP - Erro ao consultar lote', [
                'batchNumber' => $batchNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Consulta informações detalhadas de um lote
     *
     * @param string $batchNumber
     * @return array
     */
    public function consultBatchInfo(string $batchNumber): array
    {
        // Similar ao consultBatch, porém retorna mais detalhes
        throw new Exception('Método não implementado ainda');
    }

    /**
     * Consulta dados de um CNPJ
     *
     * @param string $cnpj
     * @return array
     * @throws Exception
     */
    public function consultCNPJ(string $cnpj): array
    {
        try {
            // 1. Constrói o XML
            $xml = $this->requestBuilder->buildConsultCNPJRequest($cnpj);

            // 2. Assina o XML
            $signedXml = $this->xmlSigner->signXml($xml, 'PedidoConsultaCNPJ');

            // 3. Envia via SOAP
            $response = $this->soapClient->sendRequest(
                $this->endpointNF,
                'http://www.prefeitura.sp.gov.br/nfe/ConsultaCNPJ',
                $signedXml
            );

            // 4. Processa a resposta
            return $this->parseResponse($response);

        } catch (Exception $e) {
            Log::error('NFSe SP - Erro ao consultar CNPJ', [
                'cnpj' => $cnpj,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancela uma NFSe
     *
     * @param string $invoiceNumber
     * @param string $verificationCode
     * @return array
     * @throws Exception
     */
    public function cancelNFe(string $invoiceNumber, string $verificationCode): array
    {
        try {
            // 1. Constrói o XML de cancelamento
            $xml = $this->requestBuilder->buildCancelNFeRequest($invoiceNumber, $verificationCode);

            // 2. Assina o XML
            $signedXml = $this->xmlSigner->signXml($xml, 'PedidoCancelamentoNFe');

            // 3. Envia via SOAP
            $response = $this->soapClient->sendRequest(
                $this->endpointNF,
                'http://www.prefeitura.sp.gov.br/nfe/CancelamentoNFe',
                $signedXml
            );

            // 4. Processa a resposta
            return $this->parseResponse($response);

        } catch (Exception $e) {
            Log::error('NFSe SP - Erro ao cancelar NFe', [
                'invoiceNumber' => $invoiceNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Processa a resposta XML da API
     *
     * @param string $xmlResponse
     * @return array
     */
    protected function parseResponse(string $xmlResponse): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xmlResponse);

        $xpath = new \DOMXPath($dom);

        // Verifica se há erros
        $errors = $xpath->query('//Erro');
        if ($errors->length > 0) {
            $errorCode = $xpath->query('//Erro/Codigo')->item(0)?->nodeValue ?? 'UNKNOWN';
            $errorMsg = $xpath->query('//Erro/Descricao')->item(0)?->nodeValue ?? 'Erro desconhecido';

            throw new Exception("Erro NFSe SP [{$errorCode}]: {$errorMsg}");
        }

        // Extrai informações básicas da resposta
        $result = [
            'success' => true,
            'xml' => $xmlResponse,
            'data' => []
        ];

        // Extrai número de NFSe se disponível
        $nfeNumber = $xpath->query('//NumeroNFe')->item(0)?->nodeValue;
        if ($nfeNumber) {
            $result['data']['numeroNFe'] = $nfeNumber;
        }

        // Extrai código de verificação se disponível
        $verificationCode = $xpath->query('//CodigoVerificacao')->item(0)?->nodeValue;
        if ($verificationCode) {
            $result['data']['codigoVerificacao'] = $verificationCode;
        }

        // Extrai número de lote se disponível
        $batchNumber = $xpath->query('//NumeroLote')->item(0)?->nodeValue;
        if ($batchNumber) {
            $result['data']['numeroLote'] = $batchNumber;
        }

        return $result;
    }
}
