<?php

namespace App\DTOs\Contracts;

use App\Models\Contracts\Contract;
use JsonSerializable;

class ContractDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $started_at,
        public readonly ?string $ended_at,
        public readonly ?string $canceled_at,
    ) {}

    public static function fromModel(Contract $contract): self
    {
        return new self(
            id:          $contract->id,
            title:       $contract->title,
            description: $contract->description,
            started_at:  $contract->started_at?->format('Y-m-d'),
            ended_at:    $contract->ended_at?->format('Y-m-d'),
            canceled_at: $contract->canceled_at?->format('Y-m-d'),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'started_at'  => $this->started_at,
            'ended_at'    => $this->ended_at,
            'canceled_at' => $this->canceled_at,
        ];
    }
}
