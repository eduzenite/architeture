<?php

namespace App\Infra\PrefeituraSaoPaulo;

use DOMDocument;
use Exception;
use SoapClient;
use SoapFault;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use GuzzleHttp\Client;

class NfseSynchronousClientInfra
{
    protected string $endpointNF;
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
        $this->endpointNFTS = config("nfse.endpoint.NFTS");
    }

    public function RPSSubmission(array $rpsData): array
    {
        $xml = $this->buildRpsXml($rpsData);
        $signedXml = $this->signXml($xml, 'PedidoEnvioLoteRPS');
        return $this->callSoapMethod('EnvioRPS', $signedXml, $this->endpointNF);
    }

    public function RPSBatchSubmission(array $rpsList): array
    {
        $xml = $this->buildRpsBatchXml($rpsList);
        $signedXml = $this->signXml($xml, 'PedidoEnvioLoteRPS');
        return $this->callSoapMethod('EnvioLoteRPS', $signedXml, $this->endpointNF);
    }

    public function RPSBatchSubmissionTest(array $rpsList): array
    {
        $xml = $this->buildRpsBatchXml($rpsList);
        $signedXml = $this->signXml($xml, 'PedidoEnvioLoteRPS');
        return $this->callSoapMethod('TesteEnvioLoteRPS', $signedXml, $this->endpointNF);
    }

    public function NFeInquiry(string $invoiceNumber, string $verificationCode)
    {
        $xml = $this->buildNfeInquiryXml($invoiceNumber, $verificationCode);
        $signedXml = $this->signXml($xml, 'PedidoConsultaNFe');
        return $this->callSoapMethod('ConsultaNFeEmitidasRequest', $signedXml, $this->endpointNF);
    }

    public function NFeCancellation(string $invoiceNumber, string $verificationCode): array
    {
        $xml = $this->buildCancelXml($invoiceNumber, $verificationCode);
        $signedXml = $this->signXml($xml, 'PedidoCancelamentoNFe');
        return $this->callSoapMethod('CancelamentoNFe', $signedXml, $this->endpointNF);
    }

    /**
     * =====================================================
     * EXECUTA O MÉTODO SOAP
     * =====================================================
     */
    protected function callSoapMethod(string $method, string $xml, string $endpoint): array
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $envelope = $dom->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'soap:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $dom->appendChild($envelope);

        $body = $dom->createElement('soap:Body');
        $envelope->appendChild($body);

        $methodElement = $dom->createElementNS('http://www.prefeitura.sp.gov.br/nfe', $method);
        $body->appendChild($methodElement);

        // <VersaoSchema>1</VersaoSchema>
        $versao = $dom->createElement('VersaoSchema', '1');
        $methodElement->appendChild($versao);

        // <MensagemXML></MensagemXML> Sem serialize
//        $mensagemXml = $dom->createElement('MensagemXML');
//        $xmlFragment = new DOMDocument();
//        $xmlFragment->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
//        $imported = $dom->importNode($xmlFragment->documentElement, true);
//        $mensagemXml->appendChild($imported);
//        $methodElement->appendChild($mensagemXml);

        // <MensagemXML></MensagemXML> Com serialize
        $mensagemXml = $dom->createElement('MensagemXML');
        $cdata = $dom->createCDATASection($xml);
        $mensagemXml->appendChild($cdata);
        $methodElement->appendChild($mensagemXml);

        // Converte o DOM em string XML
        $xml = $dom->saveXML();
//        echo $xml;
//        die();

        $client = new Client([
            'verify' => false,
            'cert' => $this->certPath,
            'ssl_key' => $this->keyPath,
            'timeout' => 60,
        ]);

        try {
            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction'   => "http://www.prefeitura.sp.gov.br/nfe/$method",
                ],
                'body' => $xml,
            ]);

            $body = (string) $response->getBody();

            return [$body];
        } catch (SoapFault $e) {
            throw new Exception("Erro ao consumir método SOAP {$method}: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Erro inesperado ao processar {$method}: " . $e->getMessage());
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
                ['uri' => '']
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
     * ===============================================================================================================================================================
     * PARSE DA RESPOSTA XML SOAP
     * ===============================================================================================================================================================
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
     * ===============================================================================================================================================================
     * CONSTRUÇÃO DE XMLS DE PEDIDO
     * ===============================================================================================================================================================
     */
    protected function buildRpsXml(array $rpsData): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $root = $dom->createElementNS('http://www.prefeitura.sp.gov.br/nfe', 'PedidoEnvioLoteRPS');
        $dom->appendChild($root);

        // ===== Cabeçalho =====
        $cabecalho = $dom->createElement('Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $root->appendChild($cabecalho);

        $cpfCnpjRemetente = $dom->createElement('CPFCNPJRemetente');
        $cnpj = $dom->createElement('CNPJ', $this->cnpj);
        $cpfCnpjRemetente->appendChild($cnpj);
        $cabecalho->appendChild($cpfCnpjRemetente);

        $transacao = $dom->createElement('Transacao', 'false');
        $cabecalho->appendChild($transacao);

        $dtInicio = $dom->createElement('DtInicio', date('Y-m-d'));
        $cabecalho->appendChild($dtInicio);

        $dtFim = $dom->createElement('DtFim', date('Y-m-d'));
        $cabecalho->appendChild($dtFim);

        $qtdeRPS = $dom->createElement('QtdRPS', '1');
        $cabecalho->appendChild($qtdeRPS);

        // ===== RPS =====
        $lote = $dom->createElement('RPS');
        $root->appendChild($lote);

        $identificacao = $dom->createElement('IdentificacaoRPS');
        $numero = $dom->createElement('Numero', $rpsData['numero']);
        $serie = $dom->createElement('Serie', $rpsData['serie']);
        $tipo = $dom->createElement('Tipo', 'RPS');
        $identificacao->appendChild($numero);
        $identificacao->appendChild($serie);
        $identificacao->appendChild($tipo);
        $lote->appendChild($identificacao);

        $dataEmissao = $dom->createElement('DataEmissao', $rpsData['dataEmissao']);
        $lote->appendChild($dataEmissao);

        $valorServicos = $dom->createElement('ValorServicos', number_format($rpsData['valorServicos'], 2, '.', ''));
        $lote->appendChild($valorServicos);

        $codigoServico = $dom->createElement('CodigoServico', $rpsData['codigoServico']);
        $lote->appendChild($codigoServico);

        // Tomador
        $tomador = $dom->createElement('Tomador');
        $cpfCnpjTomador = $dom->createElement('CPFCNPJ');
        $cnpjTomador = $dom->createElement('CNPJ', $rpsData['cnpjTomador']);
        $cpfCnpjTomador->appendChild($cnpjTomador);
        $tomador->appendChild($cpfCnpjTomador);

        $razao = $dom->createElement('RazaoSocial', $rpsData['razaoSocialTomador']);
        $tomador->appendChild($razao);

        $email = $dom->createElement('Email', $rpsData['emailTomador']);
        $tomador->appendChild($email);

        $lote->appendChild($tomador);

        $discriminacao = $dom->createElement('Discriminacao', htmlspecialchars($rpsData['discriminacao']));
        $lote->appendChild($discriminacao);

        return $dom->saveXML();
    }

    protected function buildRpsBatchXml(array $rpsList): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $root = $dom->createElementNS('http://www.prefeitura.sp.gov.br/nfe', 'PedidoEnvioLoteRPS');
        $dom->appendChild($root);

        // ===== Cabeçalho =====
        $cabecalho = $dom->createElement('Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $root->appendChild($cabecalho);

        $cpfCnpjRemetente = $dom->createElement('CPFCNPJRemetente');
        $cnpj = $dom->createElement('CNPJ', $this->cnpj);
        $cpfCnpjRemetente->appendChild($cnpj);
        $cabecalho->appendChild($cpfCnpjRemetente);

        $transacao = $dom->createElement('Transacao', 'false');
        $cabecalho->appendChild($transacao);

        $dtInicio = $dom->createElement('DtInicio', date('Y-m-d'));
        $cabecalho->appendChild($dtInicio);

        $dtFim = $dom->createElement('DtFim', date('Y-m-d'));
        $cabecalho->appendChild($dtFim);

        $qtdeRPS = $dom->createElement('QtdRPS', count($rpsList));
        $cabecalho->appendChild($qtdeRPS);

        // ===== Lote de RPS =====
        foreach ($rpsList as $rpsData) {
            $rps = $dom->createElement('RPS');
            $root->appendChild($rps);

            $identificacao = $dom->createElement('IdentificacaoRPS');
            $numero = $dom->createElement('Numero', $rpsData['numero']);
            $serie = $dom->createElement('Serie', $rpsData['serie']);
            $tipo = $dom->createElement('Tipo', 'RPS');
            $identificacao->appendChild($numero);
            $identificacao->appendChild($serie);
            $identificacao->appendChild($tipo);
            $rps->appendChild($identificacao);

            $dataEmissao = $dom->createElement('DataEmissao', $rpsData['dataEmissao']);
            $rps->appendChild($dataEmissao);

            $valorServicos = $dom->createElement('ValorServicos', number_format($rpsData['valorServicos'], 2, '.', ''));
            $rps->appendChild($valorServicos);

            $codigoServico = $dom->createElement('CodigoServico', $rpsData['codigoServico']);
            $rps->appendChild($codigoServico);

            // Tomador
            $tomador = $dom->createElement('Tomador');
            $cpfCnpjTomador = $dom->createElement('CPFCNPJ');
            $cnpjTomador = $dom->createElement('CNPJ', $rpsData['cnpjTomador']);
            $cpfCnpjTomador->appendChild($cnpjTomador);
            $tomador->appendChild($cpfCnpjTomador);

            $razao = $dom->createElement('RazaoSocial', $rpsData['razaoSocialTomador']);
            $tomador->appendChild($razao);

            $email = $dom->createElement('Email', $rpsData['emailTomador']);
            $tomador->appendChild($email);

            $rps->appendChild($tomador);

            $discriminacao = $dom->createElement('Discriminacao', htmlspecialchars($rpsData['discriminacao']));
            $rps->appendChild($discriminacao);
        }

        return $dom->saveXML();
    }

    protected function buildNfeInquiryXml(string $invoiceNumber, string $verificationCode): string
    {
        // Sanitiza o código de verificação (apenas letras/números e máximo 8 caracteres)
        $verificationCode = substr(preg_replace('/[^A-Za-z0-9]/', '', $verificationCode), 0, 8);

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // ===== Elemento raiz =====
        $root = $dom->createElementNS('http://www.prefeitura.sp.gov.br/nfe', 'PedidoConsultaNFe');
        $dom->appendChild($root);

        // ===== Cabeçalho =====
        $cabecalho = $dom->createElement('Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $root->appendChild($cabecalho);

        $cpfCnpjRemetente = $dom->createElement('CPFCNPJRemetente');
        $cabecalho->appendChild($cpfCnpjRemetente);

        $cnpj = $dom->createElement('CNPJ', $this->cnpj);
        $cpfCnpjRemetente->appendChild($cnpj);

        // ===== Detalhe =====
        $detalhe = $dom->createElement('Detalhe');
        $root->appendChild($detalhe);

        $chaveNFe = $dom->createElement('ChaveNFe');
        $detalhe->appendChild($chaveNFe);

        $inscricao = $dom->createElement('InscricaoPrestador', $this->municipalRegistration);
        $chaveNFe->appendChild($inscricao);

        $numeroNFe = $dom->createElement('NumeroNFe', $invoiceNumber);
        $chaveNFe->appendChild($numeroNFe);

        $codigoVerificacao = $dom->createElement('CodigoVerificacao', $verificationCode);
        $chaveNFe->appendChild($codigoVerificacao);

        return $dom->saveXML();
    }


    protected function buildCancelXml(string $invoiceNumber, string $verificationCode): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // Elemento raiz
        $root = $dom->createElementNS('http://www.prefeitura.sp.gov.br/nfe', 'PedidoCancelamentoNFe');
        $dom->appendChild($root);

        // ===== Cabeçalho =====
        $cabecalho = $dom->createElement('Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $root->appendChild($cabecalho);

        $cpfCnpj = $dom->createElement('CPFCNPJRemetente');
        $cabecalho->appendChild($cpfCnpj);

        $cnpj = $dom->createElement('CNPJ', $this->cnpj);
        $cpfCnpj->appendChild($cnpj);

        // ===== Detalhe =====
        $detalhe = $dom->createElement('Detalhe');
        $root->appendChild($detalhe);

        $chaveNfe = $dom->createElement('ChaveNFe');
        $detalhe->appendChild($chaveNfe);

        $inscricao = $dom->createElement('InscricaoPrestador', $this->municipalRegistration);
        $chaveNfe->appendChild($inscricao);

        $numero = $dom->createElement('NumeroNFe', $invoiceNumber);
        $chaveNfe->appendChild($numero);

        // Código de verificação — garantindo que não ultrapasse 8 caracteres
        $verificationCode = substr(preg_replace('/[^A-Za-z0-9]/', '', $verificationCode), 0, 8);
        $codigo = $dom->createElement('CodigoVerificacao', $verificationCode);
        $chaveNfe->appendChild($codigo);

        return $dom->saveXML();
    }
}
