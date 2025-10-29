<?php

namespace App\Infra\PrefeituraSaoPaulo;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Classe responsável pela comunicação direta com a API de NFS-e / DPS
 * da Prefeitura de São Paulo.
 *
 * Suporta envio de lotes, consultas, cancelamentos e leitura de retornos.
 * Utiliza autenticação via certificado digital A1 (.pfx).
 */
class NfseClientInfra
{
    /**
     * Instância de autenticação (NfseAuthInfra)
     * @var NfseAuthInfra
     */
    protected NfseAuthInfra $auth;


    protected string $loteNFe = '';
    protected string $loteNFeAsync = '';

    /**
     * Timeout padrão em segundos
     * @var int
     */
    protected int $timeout = 60;

    /**
     * Mapeamento de endpoints da API
     * (ajustar conforme o swagger oficial: https://www.nfse.gov.br/swagger/contribuintesissqn/)
     * @var array
     */
    protected array $endpoints = [
        'send' => '/sefinnacional',
        'sendBatch' => '/sefinnacional',
        'check' => '/dps',
        'checkBatch' => '/dps',
        'cancel' => '/dps',
    ];

    /**
     * Construtor
     */
    public function __construct(NfseAuthInfra $auth)
    {
        $this->auth = $auth;
        $this->loteNFe = config('nfse.services.prefeitura_sao_paulo.loteNFe');
        $this->loteNFeAsync = config('nfse.services.prefeitura_sao_paulo.loteNFeAsync');
    }

    /**
     * Envia DPS para geração de NFS-e.
     *
     * @param string $xml XML completo
     * @param string|null $identificador Identificador interno
     * @return array Retorno estruturado (status, protocolo, mensagem)
     * @throws Exception
     */
    public function send(string $data, ?string $identificador = null): array
    {
        $xml = $this->parseXml($data);
        $url = $this->endpoint('send');

        $response = $this->request('GET', $url, $data);

        return $this->parseResponse($response, 'Envio de Lote');
    }

    /**
     * Envia um lote de DPS para geração de NFS-e.
     *
     * @param string $xml XML completo do lote
     * @param string|null $identificador Identificador interno do lote
     * @return array Retorno estruturado (status, protocolo, mensagem)
     * @throws Exception
     */
    public function sendBatch(array $xml, ?string $identificador = null): array
    {
        $url = $this->endpoint('sendBatch');
        $response = $this->request('POST', $url, $xml);

        return $this->parseResponse($response, 'Envio de Lote');
    }

    /**
     * Consulta uma NFS-e específica.
     *
     * @param string $numero Número da nota
     * @return array
     * @throws Exception
     */
    public function check(string $numero): array
    {
        $url = $this->endpoint('check', ['numero' => $numero]);

        $response = $this->request('GET', $url);

        return $this->parseResponse($response, 'Consulta de NFS-e');
    }

    /**
     * Consulta o status de um lote enviado anteriormente.
     *
     * @param string $protocolo Número de protocolo retornado no envio
     * @return array
     * @throws Exception
     */
    public function checkBatch(string $protocolo): array
    {
        $url = $this->endpoint('checkBatch', ['protocolo' => $protocolo]);

        $response = $this->request('GET', $url);

        return $this->parseResponse($response, 'Consulta de Lote');
    }

    /**
     * Cancela uma NFS-e emitida.
     *
     * @param string $numero Número da nota
     * @param string $motivo Motivo do cancelamento
     * @return array
     * @throws Exception
     */
    public function cancel(string $numero, string $motivo): array
    {
        $url = $this->endpoint('cancel', ['numero' => $numero]);

        $xmlCancelamento = $this->montarXmlCancelamento($numero, $motivo);

        $response = $this->request('POST', $url, $xmlCancelamento);

        return $this->parseResponse($response, 'Cancelamento de NFS-e');
    }

    /**
     * Executa a requisição HTTP com certificado digital.
     *
     * @param string $method GET|POST
     * @param string $url
     * @param string|null $body XML opcional
     * @return \Illuminate\Http\Client\Response
     * @throws Exception
     */
    protected function request(string $method, string $url, ?string $body = null)
    {
        try {
            $options = [
                'cert' => $this->auth->getPemPath(),
                'ssl_key' => $this->auth->getPemPath(),
                'verify' => config('nfse.certificate.cacert_path'),
                'timeout' => $this->timeout,
            ];

            $headers = $this->auth->getHeaders();

            $request = Http::withOptions($options)->withHeaders($headers);

            $response = $method === 'POST'
                ? $request->send('POST', $this->baseUrl . $url, ['body' => $body])
                : $request->send('GET', $this->baseUrl . $url);

            Log::info("NFSe Request: {$method} {$url}", ['body' => $body, 'response' => $response->body()]);

            return $response;
        } catch (Exception $e) {
            Log::error("Erro ao conectar com NFSe: {$e->getMessage()}");
            throw new Exception("Falha na comunicação com o servidor da Prefeitura: {$e->getMessage()}");
        }
    }

    /**
     * Interpreta a resposta XML ou JSON da API e retorna array estruturado.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param string $context
     * @return array
     */
    protected function parseResponse($response, string $context): array
    {
        $contentType = $response->header('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $data = $response->json();
        } else {
            $data = $this->parseXml($response->body());
        }

        if ($response->failed()) {
            Log::warning("NFSe Erro em {$context}", $data);
        }

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'data' => $data,
            'raw' => $response->body(),
        ];
    }

    /**
     * Realiza o parsing básico de um XML para array.
     *
     * @param string $xml
     * @return array
     */
    protected function parseXml(string $xml): array
    {
        libxml_use_internal_errors(true);

        try {
            $simpleXml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            return json_decode(json_encode($simpleXml), true);
        } catch (Exception $e) {
            Log::error("Erro ao interpretar XML da NFSe: " . $e->getMessage());
            return ['error' => 'XML inválido', 'raw' => $xml];
        }
    }

    /**
     * Monta XML básico para cancelamento de nota.
     *
     * @param string $numero
     * @param string $motivo
     * @return string
     */
    protected function montarXmlCancelamento(string $numero, string $motivo): string
    {
        return <<<XML
            <CancelarNfseRequest>
                <IdentificacaoNfse>
                    <Numero>{$numero}</Numero>
                </IdentificacaoNfse>
                <CodigoCancelamento>2</CodigoCancelamento>
                <Motivo>{$motivo}</Motivo>
            </CancelarNfseRequest>
        XML;
    }

    /**
     * Monta a URL final substituindo parâmetros dinâmicos.
     *
     * @param string $key
     * @param array $params
     * @return string
     */
    protected function endpoint(string $key, array $params = []): string
    {
        $url = $this->endpoints[$key] ?? '';

        foreach ($params as $k => $v) {
            $url = str_replace("{{$k}}", $v, $url);
        }

        return $url;
    }
}
