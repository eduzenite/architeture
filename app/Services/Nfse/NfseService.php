<?php

namespace App\Services\Nfse;

use App\Infra\PrefeituraSaoPaulo\NfseSynchronousClientInfra;
use App\Repositories\Nfse\EloquentNfseRepository;

class NfseService
{
    private EloquentNfseRepository $clientRepository;
    private NfseSynchronousClientInfra $synchronousClientInfra;

    public function __construct(EloquentNfseRepository $clientRepository, NfseSynchronousClientInfra $synchronousClientInfra)
    {
        $this->clientRepository = $clientRepository;
        $this->synchronousClientInfra = $synchronousClientInfra;
    }

    /**
     * Envia uma NFSe individual (RPS único)
     */
    public function create(string $invoice): array
    {
        $rawResponse = $this->clientRepository->create($invoice);
        return $rawResponse;
    }

    /**
     * Envia um lote de NFSe (vários RPS)
     */
    public function createBatch(array $loteDeNotas): array
    {
        $rawResponse = $this->clientRepository->createBatch($loteDeNotas);
        return $rawResponse;
    }

    /**
     * Envia uma NFSe individual (RPS único)
     */
    public function send(string $nfseId): array
    {
        $rawResponse = $this->synchronousClientInfra->send($nfseId);
        return $rawResponse;
    }

    /**
     * Envia um lote de NFSe (vários RPS)
     */
    public function sendBatch(array $loteDeNotas): array
    {
        $rawResponse = $this->clientRepository->sendBatch($loteDeNotas);
        return $rawResponse;
    }

    /**
     * Consulta uma NFSe emitida
     */
    public function check(string $nfseNumber): array
    {
        $cnpj = "17414457000145";
        $municipalRegistration = "46666931";
        $verificationCode = "XLBX-K7CR";

        $rawResponse = $this->synchronousClientInfra->consultNFe($cnpj, $municipalRegistration, $nfseNumber, $verificationCode);


//            ->consultNFe("17414457000145", "46666931", $nfseNumber, "XLBX-K7CR");
        return $rawResponse;
    }

    /**
     * Consulta um lote de RPS já enviado
     */
    public function checkBatch(string $protocolo): array
    {
        $rawResponse = $this->clientRepository->checkBatch($protocolo);
        return $rawResponse;
    }

    /**
     * Cancela uma NFSe
     */
    public function cancel(string $numeroNfse, string $motivo): array
    {
        $rawResponse = $this->clientRepository->cancel($numeroNfse, $motivo);
        return $rawResponse;
    }
}
