<?php

namespace App\DTOs\Nfses;

class NfseBatchCreateDTO
{
    public function __construct(
        public string $batch_code,
        public string $sent_at,
        public string $status,
        public ?string $xml_sent,
        public ?string $xml_response,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            batch_code:    $data['batch_code'],
            sent_at:       $data['sent_at'],
            status:        $data['status'],
            xml_sent:      $data['xml_sent'] ?? null,
            xml_response:  $data['xml_response'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'batch_code'        => $this->batch_code,
            'sent_at'           => $this->sent_at,
            'status'            => $this->status,
            'xml_sent'          => $this->xml_sent,
            'xml_response'      => $this->xml_response,
        ];
    }
}
