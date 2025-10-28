<?php
namespace App\Services\Contracts;

use App\DTOs\Contracts\NfseCreateDTO;
use App\DTOs\Contracts\NfseDTO;
use App\DTOs\Contracts\NfseUpdateDTO;
use App\Repositories\Contracts\ContractRepositoryInterface;

class ContractService
{
    private ContractRepositoryInterface $repository;

    public function __construct(ContractRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function list(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->repository->list($filters, $perPage);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($contract) => NfseDTO::fromModel($contract))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ];
    }

    public function find(int $id)
    {
        $model = $this->repository->find($id);
        return $model ? NfseDTO::fromModel($model) : null;
    }

    public function create(NfseCreateDTO $input)
    {
        $model = $this->repository->create($input->toArray());
        return NfseDTO::fromModel($model);
    }

    public function update(int $id, NfseUpdateDTO $input)
    {
        $model = $this->repository->update($id, $input->toArray());
        return $model ? NfseDTO::fromModel($model) : null;
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
