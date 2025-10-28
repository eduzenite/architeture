<?php

namespace App\Http\Controllers\Api\Nfse;

use App\Services\Nfse\NfseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NfseController extends Controller
{
    /**
     * Serviço responsável pela integração NFSe.
     *
     * @var NfseService
     */
    protected NfseService $nfseService;

    /**
     * Construtor do controller.
     *
     * @param NfseService $nfseService
     */
    public function __construct(NfseService $nfseService)
    {
        $this->nfseService = $nfseService;
    }

    /**
     * Cria uma única DPS/NFS-e.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $data = $request->validate([
            'invoice' => 'required|array',
        ]);

        $result = $this->nfseService->create($data['invoice']);

        return response()->json([
            'success' => true,
            'message' => 'Nota enviada com sucesso!',
            'data' => $result,
        ]);
    }

    /**
     * Cria um lote de notas (DPS em lote).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBatch(Request $request)
    {
        $data = $request->validate([
            'invoices' => 'required|array',
        ]);

        $result = $this->nfseService->createBatch($data);

        return response()->json([
            'success' => true,
            'message' => 'Nota enviada com sucesso!',
            'data' => $result,
        ]);
    }

    /**
     * Envia uma única DPS/NFS-e.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(string $nfseId): JsonResponse
    {
        $result = $this->nfseService->send($nfseId);

        return response()->json([
            'success' => true,
            'message' => 'Nota enviada com sucesso!',
            'data' => $result,
        ]);
    }

    /**
     * Envia um lote de notas (DPS em lote).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendBatch(string $batchId): JsonResponse
    {
        $result = $this->nfseService->sendBatch($batchId);

        return response()->json([
            'success' => true,
            'message' => 'Lote de notas enviado com sucesso!',
            'data' => $result,
        ]);
    }

    /**
     * Consulta o status de uma nota individual.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(string $nfseId): JsonResponse
    {
        $result = $this->nfseService->check($nfseId);

        return response()->json([
            'success' => true,
            'message' => 'Consulta de NFSe realizada com sucesso!',
            'data' => $result,
        ]);
    }

    /**
     * Consulta o status de um lote de notas enviado.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkBatch(string $batchId): JsonResponse
    {
        $result = $this->nfseService->checkBatch($batchId);

        return response()->json([
            'success' => true,
            'message' => 'Consulta de lote realizada com sucesso!',
            'data' => $result,
        ]);
    }

    /**
     * Cancela uma NFSe emitida.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(Request $request, string $nfseId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $result = $this->nfseService->cancel($nfseId, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'NFSe cancelada com sucesso!',
            'data' => $result,
        ]);
    }
}
