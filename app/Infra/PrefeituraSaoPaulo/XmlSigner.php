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
        // Carrega o certificado
        $certContent = file_get_contents($this->certPath);
        if (!$certContent) {
            throw new Exception("Não foi possível ler o certificado em: {$this->certPath}");
        }

        // Extrai chave privada e certificado público
        $certData = [];
        if (!openssl_pkcs12_read($certContent, $certData, $this->certPass)) {
            throw new Exception("Erro ao ler certificado PKCS12. Verifique a senha.");
        }

        $privateKey = $certData['pkey'];
        $publicCert = $certData['cert'];

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
}
