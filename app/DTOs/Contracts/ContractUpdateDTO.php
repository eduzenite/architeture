<?php

namespace App\DTOs\Contracts;

class ContractUpdateDTO
{
    public function __construct(private array $fields)
    {
        // $fields contém apenas o que deve atualizar (inclusive null, se for o caso)
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return $this->fields;
    }
}
