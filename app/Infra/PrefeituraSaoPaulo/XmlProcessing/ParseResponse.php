<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use DOMDocument;

class ParseResponse
{
    protected function parseResponse(string $xmlResponse): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlResponse);

        $body = $dom->getElementsByTagName('Body')->item(0);
        $success = $dom->getElementsByTagName('Sucesso')->item(0);

        return [
            'success' => $success ? strtolower($success->nodeValue) === 'true' : false,
            'raw' => $dom->saveXML($body),
        ];
    }
}
