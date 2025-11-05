<?php

namespace App\Infra\PrefeituraSaoPaulo;

use GuzzleHttp\Client;
use Exception;

class SoapClient
{
    protected string $certPath;
    protected string $certPass;
    protected string $cacertPath;

    public function __construct(string $certPath, string $certPass, string $cacertPath)
    {
        $this->certPath = $certPath;
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
        // Monta o envelope SOAP
        $soapEnvelope = $this->buildSoapEnvelope($xmlBody);

        // Converte certificado PKCS12 para PEM temporariamente
        $tempPemPath = $this->convertCertToPem();

        try {
            $client = new Client([
                'verify' => $this->cacertPath,
                'cert' => [$tempPemPath, $this->certPass],
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => $soapAction,
                ],
                'timeout' => 30,
            ]);

            $response = $client->post($endpoint, [
                'body' => $soapEnvelope,
            ]);

            $responseBody = (string) $response->getBody();

            // Remove arquivo temporário
            @unlink($tempPemPath);

            return $this->extractSoapBody($responseBody);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            @unlink($tempPemPath);

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

    /**
     * Converte certificado PKCS12 para PEM temporário
     */
    protected function convertCertToPem(): string
    {
        $certContent = file_get_contents($this->certPath);
        $certData = [];

        if (!openssl_pkcs12_read($certContent, $certData, $this->certPass)) {
            throw new Exception("Erro ao converter certificado para PEM");
        }

        $pemContent = $certData['cert'] . "\n" . $certData['pkey'];

        $tempPath = sys_get_temp_dir() . '/cert_' . uniqid() . '.pem';
        file_put_contents($tempPath, $pemContent);

        return $tempPath;
    }
}
