<?php

namespace App\DTOs\Nfses;

class NfseBatchUpdateDTO
{
    public function __construct(
        public string $status,
        public ?string $xml_sent,
        public ?string $xml_response,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status:        $data['status'],
            xml_sent:      $data['xml_sent'] ?? null,
            xml_response:  $data['xml_response'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'status'            => $this->status,
            'xml_sent'          => $this->xml_sent,
            'xml_response'      => $this->xml_response,
        ];
    }
}
