<?php

namespace App\Services\Nfse;

use App\Infra\PrefeituraSaoPaulo\NfseClientInfra;
use App\Repositories\Nfse\EloquentNfseRepository;

class NfseService
{
    private EloquentNfseRepository $clientRepository;
    private NfseClientInfra $clientInfra;

    public function __construct(EloquentNfseRepository $clientRepository, NfseClientInfra $clientInfra)
    {
        $this->clientRepository = $clientRepository;
        $this->clientInfra = $clientInfra;
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
    public function send(string $dados): array
    {
        $dados = "<EnviarLoteRpsEnvio xmlns=\"http://www.prefeitura.sp.gov.br/nfse.xsd\">
                        <LoteRps>
                            <NumeroLote>1</NumeroLote>
                            <Cnpj>12345678000195</Cnpj>
                            <InscricaoMunicipal>12345678</InscricaoMunicipal>
                            <QuantidadeRps>1</QuantidadeRps>
                            <ListaRps>
                                {$dados}
                            </ListaRps>
                        </LoteRps>
                    </EnviarLoteRpsEnvio>";
        $rawResponse = $this->clientInfra->send($dados);
        return $rawResponse;
//        $rawResponse = $this->client->send($dados);
//        return $rawResponse;
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
    public function check(string $numeroNfse): array
    {
        $rawResponse = $this->clientRepository->check($numeroNfse);
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
