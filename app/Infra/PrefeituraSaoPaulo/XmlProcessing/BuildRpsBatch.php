<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use DOMDocument;

class BuildRpsBatch
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

    public function build(array $rpsList): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // Define o namespace padrão (targetNamespace do XSD)
        $ns = 'http://www.prefeitura.sp.gov.br/nfe';

        // Cria o elemento raiz com o namespace correto
        $root = $dom->createElementNS($ns, 'PedidoEnvioLoteRPS');
        $dom->appendChild($root);

        // ===== Cabeçalho =====
        // CORRIGIDO: Usa createElementNS para Cabecalho
        $cabecalho = $dom->createElementNS($ns, 'Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $root->appendChild($cabecalho);

        // CORRIGIDO: Usa createElementNS para todos os elementos filhos do Cabecalho
        $cpfCnpjRemetente = $dom->createElementNS($ns, 'CPFCNPJRemetente');
        $cnpj = $dom->createElementNS($ns, 'CNPJ', $this->cnpj);
        $cpfCnpjRemetente->appendChild($cnpj);
        $cabecalho->appendChild($cpfCnpjRemetente);

        // campos em minúsculo conforme XSD
        $transacao = $dom->createElementNS($ns, 'transacao', 'false');
        $cabecalho->appendChild($transacao);

        $dtInicio = $dom->createElementNS($ns, 'dtInicio', date('Y-m-d'));
        $cabecalho->appendChild($dtInicio);

        $dtFim = $dom->createElementNS($ns, 'dtFim', date('Y-m-d'));
        $cabecalho->appendChild($dtFim);

        $qtdeRPS = $dom->createElementNS($ns, 'QtdRPS', count($rpsList));
        $cabecalho->appendChild($qtdeRPS);

        // Soma dos valores para o campo obrigatório
        $valorTotalServicos = array_sum(array_column($rpsList, 'valorServicos'));
        $cabecalho->appendChild($dom->createElementNS(
            $ns,
            'ValorTotalServicos',
            number_format($valorTotalServicos, 2, '.', '')
        ));

        // Campo opcional, pode ser zero
        $cabecalho->appendChild($dom->createElementNS($ns, 'ValorTotalDeducoes', '0.00'));

        // ===== Lote de RPS =====
        foreach ($rpsList as $rpsData) {
            // CORRIGIDO: Usa createElementNS para RPS
            $rps = $dom->createElementNS($ns, 'RPS');
            $root->appendChild($rps);

            // CORRIGIDO: Usa createElementNS para todos os elementos filhos do RPS
            $identificacao = $dom->createElementNS($ns, 'IdentificacaoRPS');
            $identificacao->appendChild($dom->createElementNS($ns, 'Numero', $rpsData['numero']));
            $identificacao->appendChild($dom->createElementNS($ns, 'Serie', $rpsData['serie']));
            $identificacao->appendChild($dom->createElementNS($ns, 'Tipo', 'RPS'));
            $rps->appendChild($identificacao);

            $rps->appendChild($dom->createElementNS($ns, 'DataEmissao', $rpsData['dataEmissao']));
            $rps->appendChild($dom->createElementNS($ns, 'ValorServicos', number_format($rpsData['valorServicos'], 2, '.', '')));
            $rps->appendChild($dom->createElementNS($ns, 'CodigoServico', $rpsData['codigoServico']));

            // Tomador e sub-elementos
            $tomador = $dom->createElementNS($ns, 'Tomador');
            $cpfCnpjTomador = $dom->createElementNS($ns, 'CPFCNPJ');
            // CORRIGIDO: Usa createElementNS para CNPJ dentro de CPFCNPJ
            $cpfCnpjTomador->appendChild($dom->createElementNS($ns, 'CNPJ', $rpsData['cnpjTomador']));
            $tomador->appendChild($cpfCnpjTomador);
            $tomador->appendChild($dom->createElementNS($ns, 'RazaoSocial', $rpsData['razaoSocialTomador']));
            $tomador->appendChild($dom->createElementNS($ns, 'Email', $rpsData['emailTomador']));
            $rps->appendChild($tomador);

            $rps->appendChild($dom->createElementNS($ns, 'Discriminacao', htmlspecialchars($rpsData['discriminacao'])));
        }

        $signedXml = $this->signXml->sign($dom->saveXML());

        if($this->validadeXml->validate($dom->saveXML(), $this->xsdPath)){
            return $signedXml;
        }else{
            return false;
        }
    }
}
