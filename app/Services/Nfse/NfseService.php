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
    public function check(string $nfseId)
    {
        //Nota
        $invoiceNumber = "1060162";
        $verificationCode = "XLBXK7CR";
        $rawResponse = $this->synchronousClientInfra->NFeInquiry($invoiceNumber, $verificationCode);

//        $rpsList = [
//            [
//                'numero' => '1060162',
//                'serie' => 'XLBXK7CR',
//                'dataEmissao' => '2023-01-01',
//                'valorServicos' => 1000.00,
//                'codigoServico' => '0101',
//                'cnpjTomador' => '22620045000100',
//                'razaoSocialTomador' => 'E.R. DO NASCIMENTO BRITO TECNOLOGIA',
//                'emailTomador' => 'e.nascimento@opera2.com.br',
//                'discriminacao' => 'Isso é um teste'
//            ]
//        ];
//        $rawResponse = $this->synchronousClientInfra->RPSBatchSubmissionTest($rpsList);

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
