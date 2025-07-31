<?php

namespace App\Services\Contracts;

use App\Models\Contracts\Contract;

class ContractService
{
    public function list(array $filters = [])
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

        return $query->get();
    }

    public function find(int $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return null;
        }

        return $contract;
    }

    public function create(array $data)
    {
        $contract = new Contract();
        $contract->title = $data['title'] ?? '';
        $contract->description = $data['description'] ?? '';
        $contract->started_at = $data['started_at'] ?? null;
        $contract->ended_at = $data['ended_at'] ?? null;
        $contract->canceled_at = $data['canceled_at'] ?? null;
        $contract->save();

        return $contract;
    }

    public function update(string $id, array $data)
    {
        $contract = $this->find($id);
        if (!$contract) {
            return null;
        }

        $contract->title = $data['title'] ?? $contract->title;
        $contract->description = $data['description'] ?? $contract->description;
        $contract->started_at = $data['started_at'] ?? $contract->started_at;
        $contract->ended_at = $data['ended_at'] ?? $contract->ended_at;
        $contract->canceled_at = $data['canceled_at'] ?? $contract->canceled_at;
        $contract->save();

        return $contract;
    }

    public function delete(string $id)
    {
        $contract = $this->find($id);
        if (!$contract) {
            return false;
        }

        return $contract->delete();
    }
}
