<?php

namespace App\Http\Controllers\Web\Contracts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['title', 'started_at', 'ended_at']);
        $contracts = app('ContractService')->list($filters);

        return response()->json([
            'message' => 'Lista de contratos',
            'data' => $contracts,
        ], 200);
    }

    public function show($id)
    {
        $contract = app('ContractService')->find($id);
        if (!$contract) {
            return response()->json(['message' => 'Contrato não encontrado'], 404);
        }

        return response()->json([
            'message' => 'Detalhes do contrato',
            'data' => $contract,
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date',
            'canceled_at' => 'nullable|date',
        ]);

        $contract = app('ContractService')->create($data);

        return response()->json([
            'message' => 'Contrato criado com sucesso',
            'data' => $contract,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'started_at' => 'sometimes|required|date',
            'ended_at' => 'sometimes|nullable|date',
            'canceled_at' => 'sometimes|nullable|date',
        ]);

        $contract = app('ContractService')->update($id, $data);
        if (!$contract) {
            return response()->json(['message' => 'Contrato não encontrado'], 404);
        }

        return response()->json([
            'message' => 'Contrato atualizado com sucesso',
            'data' => $contract,
        ], 200);
    }

    public function destroy($id)
    {
        $contract = app('ContractService')->find($id);
        if (!$contract) {
            return response()->json(['message' => 'Contrato não encontrado'], 404);
        }

        $contract->delete();

        return response()->json(['message' => 'Contrato excluído com sucesso'], 200);
    }
}
