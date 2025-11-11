<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use DOMDocument;

class BuildCancel
{
    protected string $cnpj;
    protected string $municipalRegistration;
    protected string $xsdPath;
    protected ValidadeXml $validadeXml;
    protected SignXml $signXml;

    public function __construct(string $cnpj, string $municipalRegistration, array $certificates)
    {
        $this->cnpj = $cnpj;
        $this->municipalRegistration = $municipalRegistration;
        $this->xsdPath = config("nfse.xsdPath").'schemas_v2/PedidoCancelamentoNFe_v02.xsd';
        $this->validadeXml = new ValidadeXml();
        $this->signXml = new SignXml($certificates);
    }

    public function build(string $invoiceNumber, string $verificationCode): string
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

        $signedXml = $this->signXml->sign($dom->saveXML());

        if($this->validadeXml->validate($dom->saveXML(), $this->xsdPath)){
            return $signedXml;
        }else{
            return false;
        }
    }
}
