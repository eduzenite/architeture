<?php

namespace App\Repositories\Contracts;

use App\Models\Contracts\Contract;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentContractRepository implements ContractRepositoryInterface
{
    public function list(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $query = Contract::query();

        if (isset($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }
        if (isset($filters['started_at'])) {
            $query->where('started_at', '>=', $filters['started_at']);
        }
        if (isset($filters['ended_at'])) {
            $query->where('ended_at', '<=', $filters['ended_at']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Contract
    {
        return Contract::find($id);
    }

    public function create(array $data): Contract
    {
        return Contract::create($data);
    }

    public function update(int $id, array $data): ?Contract
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return null;
        }
        $contract->update($data);
        return $contract;
    }

    public function delete(int $id): bool
    {
        $contract = Contract::find($id);
        return $contract ? $contract->delete() : false;
    }
}
