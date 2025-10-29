<?php

namespace App\Repositories\Nfse;

use App\DTOs\Nfses\NfseCreateDTO;
use App\Models\Nfses\Nfse;
use App\Models\Nfses\NfseBatch;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentNfseRepository implements NfseRepositoryInterface
{
    public function list(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $query = Nfse::query();

        if (isset($filters['nfse_number'])) {
            $query->where('nfse_number', $filters['nfse_number']);
        }
        if (isset($filters['json_response'])) {
            $query->where('json_response', $filters['json_response']);
        }
        if (isset($filters['created_at '])) {
            $query->where('created_at ', $filters['created_at ']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Nfse
    {
        return Nfse::find($id);
    }

    public function create(array $data): Nfse
    {
        return Nfse::create($data);
    }

    public function createBatch(array $data): NfseBatch
    {
        return Nfse::create($data);
    }

    public function send(array $data): array
    {
        return [];
    }

    public function sendBatch(array $data): array
    {
        return [];
    }

    public function update(int $id, array $data): ?Nfse
    {
        $contract = Nfse::find($id);
        if (!$contract) {
            return null;
        }
        $contract->update($data);
        return $contract;
    }

    public function check(int $id, array $data): array
    {
        return [];
    }

    public function checkBatch(int $id, array $data): array
    {
        return [];
    }

    public function cancel(int $id, array $data): array
    {
        return [];
    }

    public function delete(int $id): bool
    {
        $contract = Nfse::find($id);
        return $contract ? $contract->delete() : false;
    }
}
