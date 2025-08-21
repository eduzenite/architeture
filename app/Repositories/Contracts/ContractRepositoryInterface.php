<?php

namespace App\Repositories\Contracts;

use App\Models\Contracts\Contract;

interface ContractRepositoryInterface
{
    public function list(array $filters = []);
    public function find(int $id): ?Contract;
    public function create(array $data): Contract;
    public function update(int $id, array $data): ?Contract;
    public function delete(int $id): bool;
}
