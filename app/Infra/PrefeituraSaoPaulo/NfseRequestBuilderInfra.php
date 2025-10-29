<?php

namespace App\Infra\PrefeituraSaoPaulo;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * Classe responsável por montar o corpo (XML)
 * das requisições enviadas à API de NFSe / DPS da
 * Prefeitura de São Paulo.
 *
 * Ela gera o XML no formato esperado pelo WebService,
 * de forma reutilizável e validada.
 *
 * Essa classe NÃO envia requisições, apenas gera o conteúdo.
 */
class NfseRequestBuilderInfra
{
    /**
     * Ambiente atual (homologação ou produção)
     * @var string
     */
    protected string $environment;

    /**
     * Identificação do prestador (CNPJ, Inscrição Municipal, etc.)
     * @var array
     */
    protected array $provider;

    /**
     * Construtor
     *
     * @param string|null $environment
     */
    public function __construct(?string $environment = null)
    {
        $this->environment = $environment ?? config('nfse.environment', 'homolog');
        $this->provider = [
            'cnpj' => config('nfse.provider.cnpj'),
            'im'   => config('nfse.provider.im'),
            'razao_social' => config('nfse.provider.razao_social'),
        ];
    }

    /**
     * Monta o XML de envio de uma DPS individual
     *
     * @param array $data
     * @return string XML pronto para envio
     * @throws Exception
     */
    public function buildDps(array $data): string
    {
        $this->validateDpsData($data);

        $xml = new \SimpleXMLElement('<DPS xmlns="http://www.prefeitura.sp.gov.br/nfse"/>');

        $identificacao = $xml->addChild('IdentificacaoDPS');
        $identificacao->addChild('Numero', $data['numero'] ?? '');
        $identificacao->addChild('Serie', $data['serie'] ?? 'A');
        $identificacao->addChild('DataEmissao', (new DateTime())->format('Y-m-d\TH:i:s'));

        $prestador = $xml->addChild('Prestador');
        $prestador->addChild('Cnpj', $this->provider['cnpj']);
        $prestador->addChild('InscricaoMunicipal', $this->provider['im']);

        $tomador = $xml->addChild('Tomador');
        $tomador->addChild('RazaoSocial', $data['tomador_razao']);
        $tomador->addChild('CpfCnpj', $data['tomador_cnpj']);
        $tomador->addChild('Email', $data['tomador_email'] ?? '');

        $servico = $xml->addChild('Servico');
        $servico->addChild('CodigoTributacaoMunicipio', $data['codigo_servico']);
        $servico->addChild('Discriminacao', htmlspecialchars($data['descricao']));
        $servico->addChild('ValorServicos', number_format($data['valor'], 2, '.', ''));
        $servico->addChild('Aliquota', number_format($data['aliquota'] ?? 0.05, 4, '.', ''));

        return $xml->asXML();
    }

    /**
     * Monta o XML de envio de Lote de DPS (assíncrono)
     *
     * @param array $lote
     * @param string|null $loteId
     * @return string XML do lote
     * @throws Exception
     */
    public function buildLoteDps(array $lote, ?string $loteId = null): string
    {
        if (empty($lote)) {
            throw new Exception("O lote de DPS não pode estar vazio.");
        }

        $xml = new \SimpleXMLElement('<LoteDPS xmlns="http://www.prefeitura.sp.gov.br/nfse"/>');
        $xml->addChild('IdentificacaoLote', $loteId ?? uniqid('Lote_', true));
        $xml->addChild('QuantidadeDPS', count($lote));

        $lista = $xml->addChild('ListaDPS');

        foreach ($lote as $dps) {
            $dpsXml = $this->buildDps($dps);
            $this->appendXmlChild($lista, $dpsXml);
        }

        return $xml->asXML();
    }

    /**
     * Monta XML para consulta de lote
     *
     * @param string $loteId
     * @return string
     */
    public function buildConsultaLote(string $loteId): string
    {
        $xml = new \SimpleXMLElement('<ConsultarLote xmlns="http://www.prefeitura.sp.gov.br/nfse"/>');
        $xml->addChild('IdentificacaoLote', $loteId);
        $xml->addChild('Cnpj', $this->provider['cnpj']);
        $xml->addChild('InscricaoMunicipal', $this->provider['im']);
        return $xml->asXML();
    }

    /**
     * Monta XML para cancelamento de nota
     *
     * @param string $numero
     * @param string $motivo
     * @return string
     */
    public function buildCancelamento(string $numero, string $motivo): string
    {
        $xml = new \SimpleXMLElement('<CancelarNfse xmlns="http://www.prefeitura.sp.gov.br/nfse"/>');
        $xml->addChild('NumeroNfse', $numero);
        $xml->addChild('Cnpj', $this->provider['cnpj']);
        $xml->addChild('InscricaoMunicipal', $this->provider['im']);
        $xml->addChild('MotivoCancelamento', htmlspecialchars($motivo));
        return $xml->asXML();
    }

    /**
     * Adiciona XML filho dentro de outro SimpleXMLElement
     *
     * @param \SimpleXMLElement $parent
     * @param string $xml
     * @return void
     */
    protected function appendXmlChild(\SimpleXMLElement $parent, string $xml): void
    {
        $domParent = dom_import_simplexml($parent);
        $domChild = dom_import_simplexml(new \SimpleXMLElement($xml));
        $domChild = $domParent->ownerDocument->importNode($domChild, true);
        $domParent->appendChild($domChild);
    }

    /**
     * Valida dados mínimos obrigatórios da DPS
     *
     * @param array $data
     * @throws Exception
     */
    protected function validateDpsData(array $data): void
    {
        $required = ['tomador_razao', 'tomador_cnpj', 'codigo_servico', 'descricao', 'valor'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo obrigatório ausente na DPS: {$field}");
            }
        }
    }

    /**
     * Retorna o ambiente atual
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }
}
