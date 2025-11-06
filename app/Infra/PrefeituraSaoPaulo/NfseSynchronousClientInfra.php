<?php

namespace App\Infra\PrefeituraSaoPaulo;

use DOMDocument;
use DOMElement;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class NfseSynchronousClientInfra
{
    protected string $endpointNF;
    protected string $endpointNFAsync;
    protected string $endpointNFTS;
    protected string $certPath;
    protected string $keyPath;
    protected string $certPass;
    protected string $cacertPath;
    protected string $cnpj;
    protected string $municipalRegistration;

    public function __construct()
    {
        $this->certPath = config("nfse.certificate.certPath");
        $this->keyPath = config("nfse.certificate.keyPath");
        $this->certPass = config("nfse.certificate.pemPassword");
        $this->cacertPath = config("nfse.certificate.cacertPath");
        $this->cnpj = config("nfse.company.cnpj");
        $this->municipalRegistration = config("nfse.company.municipalRegistration");
        $this->endpointNF = config("nfse.endpoint.NF");
        $this->endpointNFAsync = config("nfse.endpoint.NFAsync");
        $this->endpointNFTS = config("nfse.endpoint.NFTS");
    }

    /**
     * =====================================================
     * MÉTODOS PÚBLICOS DE CONSUMO DOS SERVIÇOS
     * =====================================================
     */
    public function RPSSubmission(array $rpsData): array
    {
        $xml = $this->buildRpsXml($rpsData);
        $signedXml = $this->signXml($xml, 'PedidoEnvioLoteRPS');

        return $this->sendRequest($this->endpointNFAsync, 'EnvioLoteRPS', $signedXml);
    }

    public function RPSBatchSubmission(array $rpsList): array
    {
        $xml = $this->buildRpsBatchXml($rpsList);
        $signedXml = $this->signXml($xml, 'PedidoEnvioLoteRPS');
        return $this->sendRequest($this->endpointNFAsync, 'EnvioLoteRPS', $signedXml);
    }

    public function RPSBatchSubmissionTest(array $rpsList): array
    {
        $xml = $this->buildRpsBatchXml($rpsList);
        $signedXml = $this->signXml($xml, 'TesteEnvioLoteRPS');
        return $this->sendRequest($this->endpointNFAsync, 'TesteEnvioLoteRPS', $signedXml);
    }

    public function NFeInquiry(string $invoiceNumber, string $verificationCode)
    {
        $xml = $this->buildNfeInquiryXml($invoiceNumber, $verificationCode);
        $signedXml = $this->signXml($xml, 'PedidoConsultaNFe');
        return $this->sendRequest($this->endpointNF, 'ConsultaNFe', $signedXml);
    }

    public function NFeCancellation(string $invoiceNumber, string $verificationCode): array
    {
        $xml = $this->buildCancelXml($invoiceNumber, $verificationCode);
        $signedXml = $this->signXml($xml, 'PedidoCancelamentoNFe');
        return $this->sendRequest($this->endpointNF, 'CancelamentoNFe', $signedXml);
    }

    /**
     * =====================================================
     * ASSINATURA DIGITAL - padrão XMLDSig Enveloped RSA-SHA1
     * =====================================================
     */
    protected function signXml(string $xmlContent, string $tagToSign): string
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xmlContent, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

        $privateKey = openssl_pkey_get_private(file_get_contents($this->keyPath), $this->certPass);
        $publicCert = file_get_contents($this->certPath);

        if (!$privateKey) {
            throw new Exception("Erro ao carregar chave privada para assinatura XML");
        }

        $objDSig = new \RobRichards\XMLSecLibs\XMLSecurityDSig();
        $objDSig->setCanonicalMethod(\RobRichards\XMLSecLibs\XMLSecurityDSig::C14N);
        $objDSig->addReference(
            $doc,
            \RobRichards\XMLSecLibs\XMLSecurityDSig::SHA1,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['force_uri' => true]
        );

        $objKey = new \RobRichards\XMLSecLibs\XMLSecurityKey(
            \RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA1,
            ['type' => 'private']
        );
        $objKey->loadKey($this->keyPath, true);

        $objDSig->sign($objKey);
        $objDSig->add509Cert($publicCert, true, false, ['subjectName' => false]);
        $objDSig->appendSignature($doc->documentElement);

        return $doc->saveXML();
    }

    /**
     * =====================================================
     * ENVIO DO XML AO ENDPOINT
     * =====================================================
     */
    protected function sendRequest(string $endpoint, string $method, string $xml)
    {
        $soapBody = <<<XML
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                             xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                <soap12:Body>
                    <{$method} xmlns="http://www.prefeitura.sp.gov.br/nfe">
                        <VersaoSchema>1</VersaoSchema>
                        <MensagemXML><![CDATA[{$xml}]]></MensagemXML>
                    </{$method}>
                </soap12:Body>
            </soap12:Envelope>
        XML;

        $client = new Client([
            'verify' => $this->cacertPath,
            'cert' => [$this->certPath, $this->certPass],
            'ssl_key' => [$this->keyPath, $this->certPass],
        ]);

        try {
            $response = $client->get($endpoint, [
                'headers' => ['Content-Type' => 'application/soap+xml; charset=utf-8'],
                'body' => $soapBody,
            ]);

//            return $this->parseResponse($response->getBody()->getContents());
            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new Exception("Erro na requisição SOAP: " . $e->getMessage());
        }
    }

    /**
     * =====================================================
     * PARSE DA RESPOSTA XML
     * =====================================================
     */
    protected function parseResponse(string $xmlResponse): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlResponse);

        $result = [];
        $body = $dom->getElementsByTagName('Body')->item(0);
        if ($body) {
            $result['raw'] = $dom->saveXML($body);
        }

        $success = $dom->getElementsByTagName('Sucesso')->item(0);
        $result['success'] = $success ? strtolower($success->nodeValue) === 'true' : false;

        return $result;
    }

    /**
     * =====================================================
     * CONSTRUÇÃO DE XMLS DE PEDIDO (EXEMPLOS)
     * =====================================================
     */
    protected function buildRpsXml(array $rpsData): string
    {
        // Aqui você gera o XML conforme schema PedidoEnvioLoteRPS.xsd
        return "<PedidoEnvioLoteRPS>...</PedidoEnvioLoteRPS>";
    }

    protected function buildRpsBatchXml(array $rpsList): string
    {
        return "<PedidoEnvioLoteRPS>...</PedidoEnvioLoteRPS>";
    }

    protected function buildNfeInquiryXml(string $invoiceNumber, string $verificationCode): string
    {
        return <<<XML
            <PedidoConsultaNFe xmlns="http://www.prefeitura.sp.gov.br/nfe">
                <Cabecalho>
                    <Versao>1</Versao>
                    <CNPJRemetente>{$this->cnpj}</CNPJRemetente>
                </Cabecalho>
                <Detalhe>
                    <ChaveNFe>{$invoiceNumber}</ChaveNFe>
                    <CodigoVerificacao>{$verificationCode}</CodigoVerificacao>
                </Detalhe>
            </PedidoConsultaNFe>
        XML;
    }

    protected function buildCancelXml(string $invoiceNumber, string $verificationCode): string
    {
        return <<<XML
            <PedidoCancelamentoNFe xmlns="http://www.prefeitura.sp.gov.br/nfe">
                <Cabecalho>
                    <Versao>1</Versao>
                    <CNPJRemetente>{$this->cnpj}</CNPJRemetente>
                </Cabecalho>
                <Detalhe>
                    <ChaveNFe>{$invoiceNumber}</ChaveNFe>
                    <CodigoVerificacao>{$verificationCode}</CodigoVerificacao>
                </Detalhe>
            </PedidoCancelamentoNFe>
        XML;
    }
}
