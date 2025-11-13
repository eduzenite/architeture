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
        $dom->formatOutput = true; // Use true para facilitar a visualização do resultado

        // Variável de controle de namespace
        $ns = 'http://www.prefeitura.sp.gov.br/nfe';

        // ===== Elemento raiz (com namespace, utilizando prefixo para evitar herança) =====
        // Usamos um prefixo 'nfe' no nome qualificado para que os filhos não herdem o default NS
        $root = $dom->createElementNS($ns, 'PedidoEnvioLoteRPS');
        $dom->appendChild($root);
        // Adiciona a declaração do namespace
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:nfe', $ns);

        // ===== Cabeçalho (AGORA SEMPRE COM NAMESPACE VAZIO: '') =====
        $cabecalho = $dom->createElementNS('', 'Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $root->appendChild($cabecalho);

        // CPFCNPJRemetente
        // FIX: Usando '' para garantir NO namespace
        $cpfCnpjRemetente = $dom->createElementNS('', 'CPFCNPJRemetente');
        $cpfCnpjRemetente->appendChild($dom->createElementNS('', 'CNPJ', $this->cnpj));
        $cabecalho->appendChild($cpfCnpjRemetente);

        // Demais campos do cabeçalho
        // FIX: Usando '' para garantir NO namespace
        $cabecalho->appendChild($dom->createElementNS('', 'transacao', 'false'));
        $cabecalho->appendChild($dom->createElementNS('', 'dtInicio', date('Y-m-d')));
        $cabecalho->appendChild($dom->createElementNS('', 'dtFim', date('Y-m-d')));
        $cabecalho->appendChild($dom->createElementNS('', 'QtdRPS', (string) count($rpsList)));

        $valorTotalServicos = array_sum(array_column($rpsList, 'valorServicos'));
        $cabecalho->appendChild($dom->createElementNS('', 'ValorTotalServicos', number_format($valorTotalServicos, 2, '.', '')));
        $cabecalho->appendChild($dom->createElementNS('', 'ValorTotalDeducoes', '0.00'));

        // ===== Lote de RPS (SEM namespace: '') =====
        foreach ($rpsList as $rpsData) {
            // ===== RPS (SEM namespace: '') =====
            // FIX: Usando '' para garantir NO namespace
            $rps = $dom->createElementNS('', 'RPS');
            $root->appendChild($rps);

            // ----- Identificação do RPS -----
            // FIX: Usando '' para garantir NO namespace
            $identificacao = $dom->createElementNS('', 'IdentificacaoRPS');
            $identificacao->appendChild($dom->createElementNS('', 'Numero', (string) $rpsData['numero']));
            $identificacao->appendChild($dom->createElementNS('', 'Serie', $rpsData['serie']));
            $identificacao->appendChild($dom->createElementNS('', 'Tipo', 1));
            $rps->appendChild($identificacao);

            // ----- Demais campos obrigatórios -----
            // FIX: Usando '' para garantir NO namespace
            $rps->appendChild($dom->createElementNS('', 'DataEmissao', $rpsData['dataEmissao']));
            $rps->appendChild($dom->createElementNS('', 'ValorServicos', number_format($rpsData['valorServicos'], 2, '.', '')));
            $rps->appendChild($dom->createElementNS('', 'CodigoServico', $rpsData['codigoServico']));

            // ----- Tomador -----
            // FIX: Usando '' para garantir NO namespace
            $tomador = $dom->createElementNS('', 'Tomador');
            $cpfCnpjTomador = $dom->createElementNS('', 'CPFCNPJ');
            $cnpjTom = preg_replace('/\D/', '', $rpsData['cnpjTomador']);

            if (strlen($cnpjTom) === 11) {
                $cpfCnpjTomador->appendChild($dom->createElementNS('', 'CPF', $cnpjTom));
            } else {
                $cpfCnpjTomador->appendChild($dom->createElementNS('', 'CNPJ', $cnpjTom));
            }

            $tomador->appendChild($cpfCnpjTomador);
            $tomador->appendChild($dom->createElementNS('', 'RazaoSocial', $rpsData['razaoSocialTomador']));
            $tomador->appendChild($dom->createElementNS('', 'Email', $rpsData['emailTomador']));
            $rps->appendChild($tomador);

            // ----- Discriminação (CDATA) -----
            // FIX: Usando '' para garantir NO namespace
            $discNode = $dom->createElementNS('', 'Discriminacao');
            $discNode->appendChild($dom->createCDATASection($rpsData['discriminacao']));
            $rps->appendChild($discNode);
        }

        // ===== Assinatura digital =====
        $xmlUnsigned = $dom->saveXML();
        $signedXml = $this->signXml->sign($xmlUnsigned, $this->xsdPath);

        return $signedXml;
    }

}
