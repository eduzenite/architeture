<?php

namespace App\Infra\PrefeituraSaoPaulo;

use DOMDocument;
use DOMXPath;
use Exception;

class XmlSigner
{
    protected string $certPath;
    protected string $certPass;

    public function __construct(string $certPath, string $certPass)
    {
        $this->certPath = $certPath;
        $this->certPass = $certPass;
    }

    /**
     * Assina o XML usando o certificado digital
     *
     * @param string $xml
     * @param string $tagToSign Nome da tag que será assinada
     * @return string XML assinado
     * @throws Exception
     */
    public function signXml(string $xml, string $tagToSign = 'Pedido'): string
    {
        // Carrega o certificado PEM
        if (!file_exists($this->certPath)) {
            throw new Exception("Arquivo de certificado não encontrado: {$this->certPath}");
        }

        $certContent = file_get_contents($this->certPath);
        if (!$certContent) {
            throw new Exception("Não foi possível ler o certificado em: {$this->certPath}");
        }

        // Verifica se é PEM
        if (strpos($certContent, '-----BEGIN') === false) {
            throw new Exception("O certificado deve estar no formato PEM");
        }

        \Log::info('Carregando certificado PEM', [
            'path' => $this->certPath,
            'file_size' => strlen($certContent)
        ]);

        // Carrega a chave privada
        $privateKey = openssl_pkey_get_private($certContent, $this->certPass);
        if (!$privateKey) {
            $error = openssl_error_string();
            throw new Exception("Não foi possível ler a chave privada do certificado PEM. OpenSSL Error: " . ($error ?: 'Desconhecido'));
        }

        // Extrai o certificado público do arquivo PEM
        $publicCert = $this->extractPublicCert($certContent);
        if (!$publicCert) {
            throw new Exception("Não foi possível extrair o certificado público do arquivo PEM");
        }

        \Log::info('Certificado PEM carregado com sucesso');

        // Carrega o XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);

        // Localiza o nó a ser assinado
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//*[local-name()='{$tagToSign}']");

        if ($nodes->length === 0) {
            throw new Exception("Tag '{$tagToSign}' não encontrada no XML");
        }

        $nodeToSign = $nodes->item(0);

        // Gera ID único se não existir
        $nodeId = $nodeToSign->getAttribute('Id');
        if (empty($nodeId)) {
            $nodeId = 'ID_' . uniqid();
            $nodeToSign->setAttribute('Id', $nodeId);
        }

        // Canonicaliza o nó
        $canonicalData = $nodeToSign->C14N(true, false);

        // Calcula o hash SHA-1
        $digestValue = base64_encode(hash('sha1', $canonicalData, true));

        // Cria o XML da assinatura
        $signatureXml = $this->buildSignatureXml($nodeId, $digestValue);

        // Assina o SignedInfo
        $signedInfo = $this->extractSignedInfo($signatureXml);
        $signature = '';
        openssl_sign($signedInfo, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        $signatureValue = base64_encode($signature);

        // Extrai informações do certificado
        $certInfo = openssl_x509_parse($publicCert);
        $certData = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"], '', $publicCert);

        // Monta o XML final da assinatura
        $finalSignature = $this->buildFinalSignatureXml($nodeId, $digestValue, $signatureValue, $certData, $certInfo);

        // Adiciona a assinatura ao XML
        $signatureNode = $dom->createDocumentFragment();
        $signatureNode->appendXML($finalSignature);
        $nodeToSign->appendChild($signatureNode);

        return $dom->saveXML();
    }

    /**
     * Constrói o XML base da assinatura
     */
    protected function buildSignatureXml(string $uri, string $digestValue): string
    {
        return <<<XML
<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
  <SignedInfo>
    <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
    <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
    <Reference URI="#{$uri}">
      <Transforms>
        <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
        <Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
      </Transforms>
      <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
      <DigestValue>{$digestValue}</DigestValue>
    </Reference>
  </SignedInfo>
</Signature>
XML;
    }

    /**
     * Extrai o SignedInfo para assinar
     */
    protected function extractSignedInfo(string $signatureXml): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($signatureXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $signedInfo = $xpath->query('//ds:SignedInfo')->item(0);
        return $signedInfo->C14N(true, false);
    }

    /**
     * Constrói o XML final da assinatura com todos os elementos
     */
    protected function buildFinalSignatureXml(
        string $uri,
        string $digestValue,
        string $signatureValue,
        string $certData,
        array $certInfo
    ): string {
        $issuerName = $this->formatIssuerName($certInfo);
        $serialNumber = $certInfo['serialNumber'] ?? '';

        return <<<XML
<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
  <SignedInfo>
    <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
    <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
    <Reference URI="#{$uri}">
      <Transforms>
        <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
        <Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
      </Transforms>
      <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
      <DigestValue>{$digestValue}</DigestValue>
    </Reference>
  </SignedInfo>
  <SignatureValue>{$signatureValue}</SignatureValue>
  <KeyInfo>
    <X509Data>
      <X509Certificate>{$certData}</X509Certificate>
      <X509IssuerSerial>
        <X509IssuerName>{$issuerName}</X509IssuerName>
        <X509SerialNumber>{$serialNumber}</X509SerialNumber>
      </X509IssuerSerial>
    </X509Data>
  </KeyInfo>
</Signature>
XML;
    }

    /**
     * Formata o Issuer Name do certificado
     */
    protected function formatIssuerName(array $certInfo): string
    {
        $issuer = $certInfo['issuer'] ?? [];
        $parts = [];

        if (isset($issuer['CN'])) $parts[] = "CN={$issuer['CN']}";
        if (isset($issuer['O'])) $parts[] = "O={$issuer['O']}";
        if (isset($issuer['OU'])) $parts[] = "OU={$issuer['OU']}";
        if (isset($issuer['C'])) $parts[] = "C={$issuer['C']}";

        return implode(', ', $parts);
    }

    /**
     * Extrai o certificado público do arquivo PEM
     */
    protected function extractPublicCert(string $pemContent): string|false
    {
        // Procura pelo certificado no arquivo PEM
        preg_match('/-----BEGIN CERTIFICATE-----(.+?)-----END CERTIFICATE-----/s', $pemContent, $matches);

        if (isset($matches[0])) {
            return $matches[0];
        }

        return false;
    }
}
