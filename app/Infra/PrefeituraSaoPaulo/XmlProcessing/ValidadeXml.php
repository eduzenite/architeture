<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use DOMDocument;

class ValidadeXml
{
    public function validate(string $xmlString, string $xsdPath): bool
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xmlString);

        libxml_use_internal_errors(true);
        $isValid = $dom->schemaValidate($xsdPath);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (!$isValid) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = trim($error->message);
            }

            throw new \Exception(
                "XML inválido conforme o XSD:\n" . implode("\n", $errorMessages)
            );
        }

        return true;
    }
}
