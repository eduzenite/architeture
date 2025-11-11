<?php

namespace App\Infra\PrefeituraSaoPaulo;

use DOMDocument;

use GuzzleHttp\Client;
use Exception;
use GuzzleHttp\Exception\RequestException;

class RequestApi
{
    protected string $certPath;
    protected string $keyPath;

    public function __construct(string $certPath, string $keyPath)
    {
        $this->certPath = $certPath;
        $this->keyPath = $keyPath;
    }

    public function request(string $method, string $xml, string $endpoint, string $SOAPAction): array
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $envelope = $dom->createElementNS('http://www.w3.org/2003/05/soap-envelope', 'soap12:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $dom->appendChild($envelope);

        $body = $dom->createElement('soap12:Body');
        $envelope->appendChild($body);

        $methodElement = $dom->createElementNS('http://www.prefeitura.sp.gov.br/nfe', $method);
        $body->appendChild($methodElement);

        // <VersaoSchema>1</VersaoSchema>
        $versao = $dom->createElement('VersaoSchema', '1');
        $methodElement->appendChild($versao);

        // Limpa e normaliza XML original
//        $xml = trim($xml);
//        $xml = preg_replace('/^\xEF\xBB\xBF/', '', $xml);

        // <MensagemXML></MensagemXML> Sem serialize
        $mensagemXml = $dom->createElement('MensagemXML');
        $mensagemXml->appendChild($dom->createTextNode($xml));
        $methodElement->appendChild($mensagemXml);

        // Converte o DOM em string XML
        $xml = $dom->saveXML();
//        echo $xml;
//        die();

        $client = new Client([
            'verify' => false,
            'cert' => $this->certPath,
            'ssl_key' => $this->keyPath,
            'timeout' => 60,
        ]);

        try {
            echo $xml;
            print_r([
                'endpoint' => $endpoint,
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction'   => $SOAPAction
                ],
                'body' => $xml,
            ]);
            die();
            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction'   => $SOAPAction
                ],
                'body' => $xml,
            ]);

            $body = (string) $response->getBody();

            return [$body];
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 'sem resposta';
            $body = $response ? (string) $response->getBody() : 'nenhum conteúdo';

            \Log::error("Erro HTTP ao processar {$method}", [
                'status' => $statusCode,
                'mensagem' => $e->getMessage(),
                'request_xml' => $xml,
                'response_xml' => $body,
            ]);

            throw new Exception("Erro HTTP ({$statusCode}) ao processar {$method}: " . $e->getMessage());

        } catch (Exception $e) {
            throw new Exception("Erro inesperado ao processar {$method}: " . $e->getMessage());
        }
    }
}
