<?php

namespace App\Repositories\Nfse;

use App\Models\Nfses\Nfse;
use App\Models\Nfses\NfseBatch;
use Illuminate\Pagination\LengthAwarePaginator;

interface NfseRepositoryInterface
{
    public function list(array $filters = []): LengthAwarePaginator;
    public function find(int $id): ?Nfse;
    public function create(array $data): Nfse;
    public function createBatch(array $data): NfseBatch;
    public function send(array $data): array;
    public function sendBatch(array $data): array;
    public function update(int $id, array $data): ?Nfse;
    public function check(int $id, array $data): array;
    public function checkBatch(int $id, array $data): array;
    public function cancel(int $id, array $data): array;
    public function delete(int $id): bool;
}
