<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use DOMDocument;

class BuildNfeInquiry
{
    protected string $cnpj;
    protected string $municipalRegistration;
    protected string $xsdPath;
    protected SignXml $signXml;

    public function __construct(string $cnpj, string $municipalRegistration, array $certificates)
    {
        $this->cnpj = $cnpj;
        $this->municipalRegistration = $municipalRegistration;
        $this->xsdPath = config("nfse.xsdPath")."schemas_v2/PedidoConsultaNFe_v02.xsd";
        $this->signXml = new SignXml($certificates);
    }

    public function build(string $invoiceNumber, string $verificationCode): string
    {
        // Sanitiza o código de verificação (apenas letras/números e máximo 8 caracteres)
        $verificationCode = substr(preg_replace('/[^A-Za-z0-9]/', '', $verificationCode), 0, 8);

        // Cria documento XML
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // ===== Elemento raiz (com namespace) =====
        $root = $dom->createElementNS('http://www.prefeitura.sp.gov.br/nfe', 'PedidoConsultaNFe');
        $dom->appendChild($root);

        // ===== Cabeçalho (sem namespace) =====
        $cabecalho = $dom->createElementNS('', 'Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $root->appendChild($cabecalho);

        $cpfCnpjRemetente = $dom->createElementNS('', 'CPFCNPJRemetente');
        $cabecalho->appendChild($cpfCnpjRemetente);

        $cnpj = $dom->createElementNS('', 'CNPJ', $this->cnpj);
        $cpfCnpjRemetente->appendChild($cnpj);

        // ===== Detalhe (sem namespace) =====
        $detalhe = $dom->createElementNS('', 'Detalhe');
        $root->appendChild($detalhe);

        $chaveNFe = $dom->createElementNS('', 'ChaveNFe');
        $detalhe->appendChild($chaveNFe);

        $inscricao = $dom->createElementNS('', 'InscricaoPrestador', $this->municipalRegistration);
        $chaveNFe->appendChild($inscricao);

        $numeroNFe = $dom->createElementNS('', 'NumeroNFe', $invoiceNumber);
        $chaveNFe->appendChild($numeroNFe);

        $codigoVerificacao = $dom->createElementNS('', 'CodigoVerificacao', $verificationCode);
        $chaveNFe->appendChild($codigoVerificacao);

        // ===== Assinatura digital =====
        $xmlUnsigned = $dom->saveXML();
        $signedXml = $this->signXml->sign($xmlUnsigned, $this->xsdPath);

        return $signedXml;
    }
}
