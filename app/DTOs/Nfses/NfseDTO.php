<?php

namespace App\DTOs\Nfses;

use App\Models\Nfses\Nfse;
use JsonSerializable;

class NfseDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $invoice_charges_id,
        public ?int $batch_id,
        public ?string $nfse_number,
        public ?string $verification_code,
        public string $status,
        public ?string $xml_sent,
        public ?string $xml_response,
        public ?string $json_response,
        public ?string $issued_at,
        public ?string $error_message,
    ) {}

    public static function fromModel(Nfse $nfse): self
    {
        return new self(
            id:                     $nfse->id,
            invoice_charges_id:     $nfse->invoice_charges_id,
            batch_id:               $nfse->batch_id,
            nfse_number:            $nfse->nfse_number,
            verification_code:      $nfse->verification_code,
            status:                 $nfse->status,
            xml_sent:               $nfse->xml_sent,
            xml_response:           $nfse->xml_response,
            json_response:          $nfse->json_response,
            issued_at:              $nfse->issued_at,
            error_message:          $nfse->error_message,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                        => $this->id,
            'invoice_charges_id'        => $this->invoice_charges_id,
            'batch_id'                  => $this->batch_id,
            'nfse_number'               => $this->nfse_number,
            'verification_code'         => $this->verification_code,
            'status'                    => $this->status,
            'xml_sent'                  => $this->xml_sent,
            'xml_response'              => $this->xml_response,
            'json_response'             => $this->json_response,
            'issued_at'                 => $this->issued_at,
            'error_message'             => $this->error_message,
        ];
    }
}
