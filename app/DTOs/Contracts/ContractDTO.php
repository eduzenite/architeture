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

    public static function fromModel(Contract $c): self
    {
        return new self(
            id:          $c->id,
            title:       $c->title,
            description: $c->description,
            started_at:  $c->started_at?->format('Y-m-d'),
            ended_at:    $c->ended_at?->format('Y-m-d'),
            canceled_at: $c->canceled_at?->format('Y-m-d'),
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
