<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use DOMDocument;

class BuildRps
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
        $this->xsdPath = config("nfse.xsdPath").'schemas_v2/PedidoEnvioLoteRPS_v02.xsd';
        $this->validadeXml = new ValidadeXml();
        $this->signXml = new SignXml($certificates);
    }

    public function build(array $rpsData): string
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

        // ===== Assinatura digital =====
        $xmlUnsigned = $dom->saveXML();
        $signedXml = $this->signXml->sign($xmlUnsigned, $this->xsdPath);

        return $signedXml;
    }
}
