<?php

namespace App\Infra\PrefeituraSaoPaulo\XmlProcessing;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

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

    public function xsdToXml(string $xsdPath): string
    {
        $xsd = new DOMDocument();
        $xsd->load($xsdPath);

        $xpath = new DOMXPath($xsd);
        $xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        // Localiza o elemento raiz
        $rootElement = $xpath->query('//xs:element')->item(0);
        if (!$rootElement) {
            throw new Exception('Elemento raiz não encontrado no XSD.');
        }

        $rootName = $rootElement->getAttribute('name');
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;

        $rootXml = $this->buildExampleFromElement($rootElement, $xpath, $doc);
        $doc->appendChild($rootXml);

        return $doc->saveXML();
    }

    private function buildExampleFromElement(DOMElement $element, DOMXPath $xpath, DOMDocument $doc): DOMElement
    {
        $name = $element->getAttribute('name') ?: 'elemento';
        $xmlElement = $doc->createElement($name, 'exemplo');

        // Se o elemento tem um tipo complexo, gera filhos
        $type = $element->getAttribute('type');
        if ($type) {
            // Busca definição do tipo
            $typeNode = $xpath->query("//xs:complexType[@name='$type']")->item(0);
            if ($typeNode) {
                $this->appendChildrenFromComplexType($xmlElement, $typeNode, $xpath, $doc);
            }
        } else {
            // Se não tem atributo type, pode ter complexType inline
            $complexType = $xpath->query('xs:complexType', $element)->item(0);
            if ($complexType) {
                $this->appendChildrenFromComplexType($xmlElement, $complexType, $xpath, $doc);
            }
        }

        return $xmlElement;
    }

    private function appendChildrenFromComplexType(DOMElement $xmlElement, DOMElement $complexType, DOMXPath $xpath, DOMDocument $doc): void
    {
        // Busca os elementos filhos
        $children = $xpath->query('.//xs:element', $complexType);

        foreach ($children as $child) {
            $childName = $child->getAttribute('name') ?: 'elemento';
            $childXml = $doc->createElement($childName, 'valor_exemplo');

            // Verifica se é aninhado (complexType interno)
            $nestedComplexType = $xpath->query('xs:complexType', $child)->item(0);
            if ($nestedComplexType) {
                $childXml->nodeValue = ''; // limpa valor, pois terá filhos
                $this->appendChildrenFromComplexType($childXml, $nestedComplexType, $xpath, $doc);
            }

            $xmlElement->appendChild($childXml);
        }
    }
}
