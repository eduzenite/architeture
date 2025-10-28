<?php

namespace App\Repositories\Nfse;

use App\Models\Nfses\Nfse;
use App\Models\Nfses\NfseBatch;

interface NfseRepositoryInterface
{
    public function list(array $filters = []);
    public function find(int $id): ?Nfse;
    public function create(array $data): Nfse;
    public function createBatch(array $data): NfseBatch;
    public function send(array $data): Nfse;
    public function sendBatch(array $data): NfseBatch;
    public function update(int $id, array $data): ?Nfse;
    public function check(int $id, array $data): ?Nfse;
    public function checkBatch(int $id, array $data): ?NfseBatch;
    public function cancel(int $id, array $data): ?Nfse;
    public function delete(int $id): bool;
}
