<?php

namespace App\DTOs\Contracts;

class ContractCreateDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $started_at,
        public ?string $ended_at,
        public ?string $canceled_at,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title:       $data['title'],
            description: $data['description'] ?? null,
            started_at:  $data['started_at'],
            ended_at:    $data['ended_at'] ?? null,
            canceled_at: $data['canceled_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'started_at'  => $this->started_at,
            'ended_at'    => $this->ended_at,
            'canceled_at' => $this->canceled_at,
        ];
    }
}
