<?php

namespace App\Infra\PrefeituraSaoPaulo;

use Exception;

class NfseEndpointsInfra
{
    /**
     * Ambiente atual (homologação ou produção)
     * @var string
     */
    protected string $environment;

    /**
     * Construtor.
     *
     * @param string|null $environment
     */
    public function __construct(?string $environment = null)
    {
        $this->environment = $environment ?? config('nfse.environment', 'homolog');
    }

    /**
     * Retorna a URL base conforme o ambiente atual.
     *
     * @return string
     * @throws Exception
     */
    public function getBaseUrl(): string
    {
        return match ($this->environment) {
            'production', 'prod' => config('nfe.services.prefeitura_sao_paulo.production'),
            'homolog', 'sandbox', 'test' => config('nfe.services.prefeitura_sao_paulo.homolog'),
            default => throw new Exception("Ambiente NFSe inválido: {$this->environment}"),
        };
    }

    /**
     * ENDPOINTS DE AUTENTICAÇÃO E CERTIFICADOS
     */
    public function getAuthEndpoints(): array
    {
        return [
            'token' => $this->buildUrl('api/v1/autenticacao/token'),
            'certificado' => $this->buildUrl('api/v1/autenticacao/certificado'),
        ];
    }

    /**
     * ENDPOINTS DE DPS (Declaração de Prestação de Serviços)
     * DPS é o novo formato que substituirá o RPS.
     */
    public function getDpsEndpoints(): array
    {
        return [
            // Envio de uma DPS individual
            'enviar' => $this->buildUrl('api/v1/dps/enviar'),

            // Envio de lote de DPS
            'enviarLote' => $this->buildUrl('api/v1/dps/lote'),

            // Consulta de status de DPS individual
            'consultar' => $this->buildUrl('api/v1/dps/consultar'),

            // Consulta de status de lote de DPS
            'consultarLote' => $this->buildUrl('api/v1/dps/lote/consultar'),

            // Cancelamento de uma DPS já emitida
            'cancelar' => $this->buildUrl('api/v1/dps/cancelar'),
        ];
    }

    /**
     * ENDPOINTS DE NFSe (Nota Fiscal de Serviço Eletrônica)
     * Envolvem operações com notas já geradas (consulta, cancelamento, etc.)
     */
    public function getNfseEndpoints(): array
    {
        return [
            // Consulta de nota por número
            'consultar' => $this->buildUrl('api/v1/nfse/consultar'),

            // Consulta de notas por período
            'consultarPeriodo' => $this->buildUrl('api/v1/nfse/consultar-periodo'),

            // Download de XML ou PDF da nota
            'downloadXml' => $this->buildUrl('api/v1/nfse/download/xml'),
            'downloadPdf' => $this->buildUrl('api/v1/nfse/download/pdf'),

            // Cancelamento de uma nota fiscal já emitida
            'cancelar' => $this->buildUrl('api/v1/nfse/cancelar'),
        ];
    }

    /**
     * ENDPOINTS DE STATUS E FILAS DE PROCESSAMENTO
     * (usados para monitorar lotes assíncronos)
     */
    public function getStatusEndpoints(): array
    {
        return [
            'statusSistema' => $this->buildUrl('api/v1/sistema/status'),
            'filaLotes' => $this->buildUrl('api/v1/fila/lotes'),
        ];
    }

    /**
     * Helper para construir URLs completas com base na raiz do ambiente.
     *
     * @param string $path
     * @return string
     */
    protected function buildUrl(string $path): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Retorna o endpoint por nome de chave (acesso dinâmico)
     * Exemplo: getEndpoint('dps.enviar')
     *
     * @param string $key
     * @return string
     * @throws Exception
     */
    public function getEndpoint(string $key): string
    {
        $map = [
            'auth.token' => $this->getAuthEndpoints()['token'],
            'auth.certificado' => $this->getAuthEndpoints()['certificado'],

            'dps.enviar' => $this->getDpsEndpoints()['enviar'],
            'dps.lote' => $this->getDpsEndpoints()['enviarLote'],
            'dps.consultar' => $this->getDpsEndpoints()['consultar'],
            'dps.consultarLote' => $this->getDpsEndpoints()['consultarLote'],
            'dps.cancelar' => $this->getDpsEndpoints()['cancelar'],

            'nfse.consultar' => $this->getNfseEndpoints()['consultar'],
            'nfse.consultarPeriodo' => $this->getNfseEndpoints()['consultarPeriodo'],
            'nfse.downloadXml' => $this->getNfseEndpoints()['downloadXml'],
            'nfse.downloadPdf' => $this->getNfseEndpoints()['downloadPdf'],
            'nfse.cancelar' => $this->getNfseEndpoints()['cancelar'],

            'status.sistema' => $this->getStatusEndpoints()['statusSistema'],
            'status.filaLotes' => $this->getStatusEndpoints()['filaLotes'],
        ];

        if (!isset($map[$key])) {
            throw new Exception("Endpoint NFSe não encontrado para a chave: {$key}");
        }

        return $map[$key];
    }

    /**
     * Retorna o ambiente atual (homologação ou produção)
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }
}
