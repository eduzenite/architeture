<?php

namespace App\Infra\PrefeituraSaoPaulo;

use SimpleXMLElement;
use Exception;

class NfseResponseParser
{
    private string $rawResponse;
    private ?SimpleXMLElement $xml = null;

    /**
     * Construtor recebe a resposta bruta (XML ou JSON)
     */
    public function __construct(string $rawResponse)
    {
        $this->rawResponse = $rawResponse;
        $this->parseResponse();
    }

    /**
     * Tenta interpretar a resposta como XML
     */
    private function parseResponse(): void
    {
        try {
            $this->xml = new SimpleXMLElement($this->rawResponse);
        } catch (\Exception $e) {
            // Se falhar, podemos tentar JSON ou lançar exceção
            throw new Exception("Erro ao interpretar a resposta NFSe: " . $e->getMessage());
        }
    }

    /**
     * Retorna se a NFSe foi emitida com sucesso
     */
    public function isSuccess(): bool
    {
        if (!$this->xml) {
            return false;
        }

        // Ajuste dependendo do XML da sua prefeitura
        return isset($this->xml->Sucesso) && (string)$this->xml->Sucesso === 'true';
    }

    /**
     * Retorna mensagens de erro, caso existam
     */
    public function getErrors(): array
    {
        if (!$this->xml) {
            return ["Resposta inválida"];
        }

        $errors = [];
        if (isset($this->xml->Erros->Erro)) {
            foreach ($this->xml->Erros->Erro as $erro) {
                $errors[] = (string)$erro->Mensagem;
            }
        }

        return $errors;
    }

    /**
     * Retorna o número da NFSe, se disponível
     */
    public function getNfseNumber(): ?string
    {
        if (!$this->xml) {
            return null;
        }

        return isset($this->xml->Numero) ? (string)$this->xml->Numero : null;
    }

    /**
     * Retorna o XML completo como string
     */
    public function getRawXml(): string
    {
        return $this->rawResponse;
    }

    /**
     * Retorna o SimpleXMLElement para consultas customizadas
     */
    public function getXmlObject(): ?SimpleXMLElement
    {
        return $this->xml;
    }
}
