<?php

namespace App\DTOs\Nfses;

class NfseCreateDTO
{
    public function __construct(
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

    public static function fromArray(array $data): self
    {
        return new self(
            invoice_charges_id:     $data['invoice_charges_id'],
            batch_id:               $data['batch_id'] ?? null,
            nfse_number:            $data['nfse_number'] ?? null,
            verification_code:      $data['verification_code'] ?? null,
            status:                 $data['status'],
            xml_sent:               $data['xml_sent'] ?? null,
            xml_response:           $data['xml_response'] ?? null,
            json_response:          $data['json_response'] ?? null,
            issued_at:              $data['issued_at'] ?? null,
            error_message:          $data['error_message'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'invoice_charges_id' =>     $this->invoice_charges_id,
            'batch_id' =>               $this->batch_id,
            'nfse_number' =>            $this->nfse_number,
            'verification_code' =>      $this->verification_code,
            'status' =>                 $this->status,
            'xml_sent' =>               $this->xml_sent,
            'xml_response' =>           $this->xml_response,
            'json_response' =>          $this->json_response,
            'issued_at' =>              $this->issued_at,
            'error_message' =>          $this->error_message,
        ];
    }
}
