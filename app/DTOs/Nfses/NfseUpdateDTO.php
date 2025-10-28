<?php

namespace App\DTOs\Nfses;

class NfseUpdateDTO
{
    public function __construct(
        public string $status,
        public ?string $error_message,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status:                 $data['status'],
            error_message:          $data['error_message'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'status' =>                 $this->status,
            'error_message' =>          $this->error_message,
        ];
    }
}
