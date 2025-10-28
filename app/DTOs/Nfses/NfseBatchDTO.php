<?php

namespace App\DTOs\Nfses;

use App\Models\Nfses\NfseBatch;
use JsonSerializable;

class NfseBatchDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public ?string $batch_code,
        public ?string $sent_at,
        public string $status,
        public ?string $xml_sent,
        public ?string $xml_response,
    ) {}

    public static function fromModel(NfseBatch $nfse): self
    {
        return new self(
            id:                     $nfse->id,
            batch_code:             $nfse->batch_code ?? null,
            sent_at:                $nfse->sent_at->format('Y-m-d H:i:s') ?? null,
            status:                 $nfse->status,
            xml_sent:               $nfse->xml_sent ?? null,
            xml_response:           $nfse->xml_response ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                        => $this->id,
            'batch_code'                => $this->batch_code,
            'sent_at'                   => $this->sent_at,
            'status'                    => $this->status,
            'xml_sent'                  => $this->xml_sent,
            'xml_response'              => $this->xml_response,
        ];
    }
}
