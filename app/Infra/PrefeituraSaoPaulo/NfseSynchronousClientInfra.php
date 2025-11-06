<?php

namespace App\Infra\PrefeituraSaoPaulo;

use DOMDocument;
use Exception;
use SoapClient;
use SoapFault;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

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
    protected SoapClient $client;

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

        $this->client = $this->createSoapClient($this->endpointNF);
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
        return $this->callSoapMethod('EnvioRPS', $signedXml);
    }

    public function RPSBatchSubmission(array $rpsList): array
    {
        $xml = $this->buildRpsBatchXml($rpsList);
        $signedXml = $this->signXml($xml, 'PedidoEnvioLoteRPS');
        return $this->callSoapMethod('EnvioLoteRPS', $signedXml);
    }

    public function RPSBatchSubmissionTest(array $rpsList): array
    {
        $xml = $this->buildRpsBatchXml($rpsList);
        $signedXml = $this->signXml($xml, 'PedidoEnvioLoteRPS');
        return $this->callSoapMethod('TesteEnvioLoteRPS', $signedXml);
    }

    public function NFeInquiry(string $invoiceNumber, string $verificationCode)
    {
        $xml = $this->buildNfeInquiryXml($invoiceNumber, $verificationCode);
        $signedXml = $this->signXml($xml, 'PedidoConsultaNFe');
        return $this->callSoapMethod('ConsultaNFe', $signedXml);
    }

    public function NFeCancellation(string $invoiceNumber, string $verificationCode): array
    {
        $xml = $this->buildCancelXml($invoiceNumber, $verificationCode);
        $signedXml = $this->signXml($xml, 'PedidoCancelamentoNFe');
        return $this->callSoapMethod('CancelamentoNFe', $signedXml);
    }

    /**
     * =====================================================
     * CONFIGURAÇÃO DO SOAP CLIENT
     * =====================================================
     */
    protected function createSoapClient(string $wsdl): SoapClient
    {
        // contexto SSL com certificado e chave separados
        $context = stream_context_create([
            'ssl' => [
                'local_cert' => $this->certPath,
                'passphrase' => $this->certPass,
                'cafile' => $this->cacertPath,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'local_pk' => $this->keyPath, // chave privada separada
            ],
        ]);

        $options = [
            'stream_context' => $context,
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];

        try {
            return new SoapClient($wsdl, $options);
        } catch (SoapFault $e) {
            throw new Exception("Erro ao inicializar SoapClient: " . $e->getMessage());
        }
    }

    /**
     * =====================================================
     * EXECUTA O MÉTODO SOAP
     * =====================================================
     */
    protected function callSoapMethod(string $method, string $xml): array
    {
        $params = [
            'VersaoSchema' => '1',
            'MensagemXML' => $xml,
        ];

        try {
            $response = $this->client->__soapCall($method, [$params]);
            $rawXml = $this->client->__getLastResponse();
            return $this->parseResponse($rawXml);
        } catch (SoapFault $e) {
            throw new Exception("Erro ao consumir método SOAP {$method}: " . $e->getMessage());
        }
    }

    /**
     * =====================================================
     * ASSINATURA DIGITAL - XMLDSig Enveloped RSA-SHA1
     * =====================================================
     */
    protected function signXml(string $xmlContent, string $tagToSign): string
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xmlContent, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);

        $root = $doc->documentElement;

        // Para PedidoConsultaNFe, usa referência vazia (documento inteiro)
        if ($tagToSign === 'PedidoConsultaNFe') {
            // URI vazia significa assinar o documento inteiro
            $objDSig->addReference(
                $root,
                XMLSecurityDSig::SHA1,
                ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::C14N],
                ['uri' => '']  // URI vazia para assinar o documento inteiro
            );
        } else {
            // Para outros elementos, mantém a lógica com Id
            if (!$root->hasAttribute('Id')) {
                $root->setAttribute('Id', $tagToSign . '_' . uniqid());
            }

            $objDSig->addReference(
                $root,
                XMLSecurityDSig::SHA1,
                ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::C14N],
                ['uri' => '#' . $root->getAttribute('Id')]
            );
        }

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
        $objKey->loadKey($this->keyPath, true);

        $objDSig->sign($objKey);
        $objDSig->add509Cert(file_get_contents($this->certPath));
        $objDSig->appendSignature($root);

        return $doc->saveXML();
    }


    /**
     * =====================================================
     * PARSE DA RESPOSTA XML SOAP
     * =====================================================
     */
    protected function parseResponse(string $xmlResponse): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlResponse);

        $body = $dom->getElementsByTagName('Body')->item(0);
        $success = $dom->getElementsByTagName('Sucesso')->item(0);

        return [
            'success' => $success ? strtolower($success->nodeValue) === 'true' : false,
            'raw' => $dom->saveXML($body),
        ];
    }

    /**
     * =====================================================
     * CONSTRUÇÃO DE XMLS DE PEDIDO
     * =====================================================
     */
    protected function buildRpsXml(array $rpsData): string
    {
        return "<PedidoEnvioLoteRPS xmlns='http://www.prefeitura.sp.gov.br/nfe'>...</PedidoEnvioLoteRPS>";
    }

    protected function buildRpsBatchXml(array $rpsList): string
    {
        return "<PedidoEnvioLoteRPS xmlns='http://www.prefeitura.sp.gov.br/nfe'>...</PedidoEnvioLoteRPS>";
    }

    protected function buildNfeInquiryXml(string $invoiceNumber, string $verificationCode): string
    {
        // Remove caracteres inválidos e limita a 8 caracteres
        $verificationCode = substr(preg_replace('/[^A-Za-z0-9]/', '', $verificationCode), 0, 8);

        return <<<XML
                <PedidoConsultaNFe xmlns="http://www.prefeitura.sp.gov.br/nfe">
                    <Cabecalho Versao="1" xmlns="">
                        <CPFCNPJRemetente>
                            <CNPJ>{$this->cnpj}</CNPJ>
                        </CPFCNPJRemetente>
                    </Cabecalho>
                    <Detalhe xmlns="">
                        <ChaveNFe>
                            <InscricaoPrestador>{$this->municipalRegistration}</InscricaoPrestador>
                            <NumeroNFe>{$invoiceNumber}</NumeroNFe>
                            <CodigoVerificacao>{$verificationCode}</CodigoVerificacao>
                        </ChaveNFe>
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
                    <ChaveNFe>
                        <InscricaoPrestador>{$this->municipalRegistration}</InscricaoPrestador>
                        <Numero>{$invoiceNumber}</Numero>
                        <CodigoVerificacao>{$verificationCode}</CodigoVerificacao>
                    </ChaveNFe>
                </Detalhe>
            </PedidoCancelamentoNFe>
        XML;
    }
}
