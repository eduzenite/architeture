<?php

namespace App\Infra\PrefeituraSaoPaulo;

use GuzzleHttp\Client;
use Exception;
use GuzzleHttp\Exception\RequestException;

class SoapClient
{
    protected string $certPath;
    protected string $keyPath;
    protected string $certPass;
    protected string $cacertPath;

    public function __construct(string $certPath, string $keyPath, string $certPass, string $cacertPath)
    {
        $this->certPath = $certPath;
        $this->keyPath = $keyPath;
        $this->certPass = $certPass;
        $this->cacertPath = $cacertPath;
    }

    /**
     * Envia requisição SOAP para o endpoint
     *
     * @param string $endpoint URL do serviço
     * @param string $soapAction Nome da ação SOAP
     * @param string $xmlBody XML assinado do body
     * @return string Resposta XML
     * @throws Exception
     */
    public function sendRequest(string $endpoint, string $soapAction, string $xmlBody): string
    {
        $soapEnvelope = $this->buildSoapEnvelope($xmlBody);

        if (!file_exists($this->certPath)) {
            throw new Exception("Arquivo de certificado não encontrado: {$this->certPath}");
        }

        if (!file_exists($this->keyPath)) {
            throw new Exception("Arquivo de chave privada não encontrado: {$this->keyPath}");
        }

        try {
            $client = new Client([
                'verify' => $this->cacertPath,
                'cert' => $this->certPath,
                'ssl_key' => [$this->keyPath, $this->certPass],
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => $soapAction,
                ],
                'timeout' => 30,
                'debug' => false, // Ative para debug: true
            ]);

            $response = $client->post($endpoint, [
                'body' => $soapEnvelope,
            ]);

            $responseBody = (string) $response->getBody();

            return $this->extractSoapBody($responseBody);

        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $errorBody = (string) $e->getResponse()->getBody();
                throw new Exception("Erro na requisição SOAP: {$errorMessage}\nResposta: {$errorBody}");
            }

            throw new Exception("Erro na requisição SOAP: {$errorMessage}");
        }
    }

    /**
     * Constrói o envelope SOAP
     */
    protected function buildSoapEnvelope(string $xmlBody): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Body>
    {$xmlBody}
  </soap:Body>
</soap:Envelope>
XML;
    }

    /**
     * Extrai o body da resposta SOAP
     */
    protected function extractSoapBody(string $soapResponse): string
    {
        $dom = new \DOMDocument();
        $dom->loadXML($soapResponse);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

        $body = $xpath->query('//soap:Body/*')->item(0);

        if ($body) {
            return $dom->saveXML($body);
        }

        return $soapResponse;
    }
}
