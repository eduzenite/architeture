<?php

namespace App\Http\Controllers\Web\Contracts;

use App\DTOs\Contracts\ContractCreateDTO;
use App\DTOs\Contracts\ContractUpdateDTO;
use App\Http\Controllers\Controller;
use App\Services\Contracts\ContractService;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(
        protected readonly ContractService $contractService,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['title', 'started_at', 'ended_at']);
        $contracts = $this->contractService->list($filters, $request->get('per_page', 15));

        return response()->json([
            'status'  => 'success',
            'message' => 'Lista de contratos',
            'data'    => $contracts['items'],
            'meta'    => $contracts['meta'],
        ]);
    }

    public function show(int $id)
    {
        $contract = $this->contractService->find($id);

        if (!$contract) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato não encontrado'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detalhes do contrato',
            'data'    => $contract, // DTO único
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'started_at'   => 'required|date',
            'ended_at'     => 'nullable|date',
            'canceled_at'  => 'nullable|date',
        ]);

        $dto = ContractCreateDTO::fromArray($data);
        $contract = $this->contractService->create($dto);

        return response()->json([
            'status'  => 'success',
            'message' => 'Contrato criado com sucesso',
            'data'    => $contract,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'sometimes|nullable|string',
            'started_at'   => 'sometimes|required|date',
            'ended_at'     => 'sometimes|nullable|date',
            'canceled_at'  => 'sometimes|nullable|date',
        ]);

        $dto = ContractUpdateDTO::fromArray($data);
        $contract = $this->contractService->update($id, $dto);

        if (!$contract) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato não encontrado'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Contrato atualizado com sucesso',
            'data'    => $contract,
        ]);
    }

    public function destroy(int $id)
    {
        $deleted = $this->contractService->delete($id);

        if (!$deleted) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Contrato excluído com sucesso'
        ]);
    }
}
