<?php

namespace App\Services\Nfse;

use App\Infra\PrefeituraSaoPaulo\NfseResponseParser;
use App\Repositories\Nfse\EloquentNfseRepository;
use App\Repositories\Nfse\NfseRepositoryInterface;
use Exception;

class NfseService
{
    private EloquentNfseRepository $client;

    public function __construct(NfseRepositoryInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Envia uma NFSe individual (RPS único)
     */
    public function create(string $invoice): NfseResponseParser
    {
        $rawResponse = $this->client->create($invoice);
        return new NfseResponseParser($rawResponse);
    }

    /**
     * Envia um lote de NFSe (vários RPS)
     */
    public function createBatch(array $loteDeNotas): NfseResponseParser
    {
        $rawResponse = $this->client->createBatch($loteDeNotas);
        return new NfseResponseParser($rawResponse);
    }

    /**
     * Envia uma NFSe individual (RPS único)
     */
    public function send(string $dados): NfseResponseParser
    {
        $rawResponse = $this->client->send($dados);
        return new NfseResponseParser($rawResponse);
    }

    /**
     * Envia um lote de NFSe (vários RPS)
     */
    public function sendBatch(array $loteDeNotas): NfseResponseParser
    {
        $rawResponse = $this->client->sendBatch($loteDeNotas);
        return new NfseResponseParser($rawResponse);
    }

    /**
     * Consulta uma NFSe emitida
     */
    public function check(string $numeroNfse): NfseResponseParser
    {
        $rawResponse = $this->client->check($numeroNfse);
        return new NfseResponseParser($rawResponse);
    }

    /**
     * Consulta um lote de RPS já enviado
     */
    public function checkBatch(string $protocolo): NfseResponseParser
    {
        $rawResponse = $this->client->checkBatch($protocolo);
        return new NfseResponseParser($rawResponse);
    }

    /**
     * Cancela uma NFSe
     */
    public function cancel(string $numeroNfse, string $motivo): NfseResponseParser
    {
        $rawResponse = $this->client->cancel($numeroNfse, $motivo);
        return new NfseResponseParser($rawResponse);
    }
}
