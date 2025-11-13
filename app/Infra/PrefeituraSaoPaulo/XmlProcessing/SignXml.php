<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use Exception;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use DOMDocument;

class SignXml
{
    protected string $certPass;
    protected string $certPath;
    protected string $keyPath;
    protected ValidadeXml $validadeXml;

    public function __construct($certificates){
        $this->certPass = $certificates['pfxPath'];
        $this->certPath = $certificates['certPath'];
        $this->keyPath = $certificates['keyPath'];
        $this->validadeXml = new ValidadeXml();
    }

    public function sign(string $xmlContent, string $xsdPath): string
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xmlContent, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

        $root = $doc->documentElement;
        $id = $root->getAttribute('Id') ?: 'Assinatura1';

        $canonicalData = $root->C14N(true, false);

        $digestValue = base64_encode(hash('sha256', $canonicalData, true));

        $signedInfo = new DOMDocument('1.0', 'utf-8');
        $signedInfo->formatOutput = false;

        $si = $signedInfo->createElement('SignedInfo');

        $cm = $signedInfo->createElement('CanonicalizationMethod');
        $cm->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $si->appendChild($cm);

        $sm = $signedInfo->createElement('SignatureMethod');
        $sm->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $si->appendChild($sm);

        $ref = $signedInfo->createElement('Reference');
        $ref->setAttribute('URI', '#' . $id);

        $tm = $signedInfo->createElement('Transforms');
        $enveloped = $signedInfo->createElement('Transform');
        $enveloped->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $tm->appendChild($enveloped);
        $ref->appendChild($tm);

        $dm = $signedInfo->createElement('DigestMethod');
        $dm->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        $ref->appendChild($dm);

        $dv = $signedInfo->createElement('DigestValue', $digestValue);
        $ref->appendChild($dv);

        $si->appendChild($ref);
        $signedInfo->appendChild($si);

        $canonicalSignedInfo = $si->C14N(true, false);

        $pkey = openssl_pkey_get_private(file_get_contents($this->keyPath), $this->certPass);
        if (!$pkey) {
            throw new Exception('Erro ao carregar chave privada.');
        }
        openssl_sign($canonicalSignedInfo, $signatureValue, $pkey, OPENSSL_ALGO_SHA256);
        $signatureValue = base64_encode($signatureValue);

        $certContent = file_get_contents($this->certPath);
        $certContent = str_replace(["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\r", "\n"], '', $certContent);

        $signature = $doc->createElement('Signature');
        $signature->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $importedSignedInfo = $doc->importNode($si, true);
        $signature->appendChild($importedSignedInfo);

        $sigValue = $doc->createElement('SignatureValue', $signatureValue);
        $signature->appendChild($sigValue);

        $keyInfo = $doc->createElement('KeyInfo');
        $x509Data = $doc->createElement('X509Data');
        $x509Cert = $doc->createElement('X509Certificate', $certContent);
        $x509Data->appendChild($x509Cert);
        $keyInfo->appendChild($x509Data);
        $signature->appendChild($keyInfo);

        $root->appendChild($signature);

        if ($this->validadeXml->validate($doc->saveXML(), $xsdPath)) {
            return $doc->saveXML();
        }else{
            return false;
        }
    }
}
