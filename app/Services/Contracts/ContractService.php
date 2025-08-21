<?php
namespace App\Services\Contracts;

use App\DTOs\Contracts\ContractCreateDTO;
use App\DTOs\Contracts\ContractDTO;
use App\DTOs\Contracts\ContractUpdateDTO;
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
                ->map(fn ($contract) => ContractDTO::fromModel($contract))
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
        return $model ? ContractDTO::fromModel($model) : null;
    }

    public function create(ContractCreateDTO $input)
    {
        $model = $this->repository->create($input->toArray());
        return ContractDTO::fromModel($model);
    }

    public function update(int $id, ContractUpdateDTO $input)
    {
        $model = $this->repository->update($id, $input->toArray());
        return $model ? ContractDTO::fromModel($model) : null;
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
