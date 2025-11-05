<?php

namespace App\Console\Commands;

use App\Infra\PrefeituraSaoPaulo\NfseSynchronousClientInfra;
use Illuminate\Console\Command;

class TestNfseXml extends Command
{
    protected $signature = 'test:nfse-xml';
    protected $description = 'Testa a geração do XML de consulta NFSe';

    public function handle()
    {
        try {
            $client = new NfseSynchronousClientInfra();

            // Usa reflection para acessar os métodos protegidos
            $reflection = new \ReflectionClass($client);

            $requestBuilderProp = $reflection->getProperty('requestBuilder');
            $requestBuilderProp->setAccessible(true);
            $requestBuilder = $requestBuilderProp->getValue($client);

            $xmlSignerProp = $reflection->getProperty('xmlSigner');
            $xmlSignerProp->setAccessible(true);
            $xmlSigner = $xmlSignerProp->getValue($client);

            // Gera o XML
            $xml = $requestBuilder->buildConsultNFeRequest('123456', 'ABC123XYZ');

            $this->info("=== XML ANTES DA ASSINATURA ===");
            $this->line($xml);
            $this->line("");

            // Assina o XML
            $signedXml = $xmlSigner->signXml($xml, 'Cabecalho');

            $this->info("=== XML APÓS ASSINATURA ===");
            $this->line($signedXml);
            $this->line("");

            // Salva em arquivo
            $path = storage_path('logs/nfse_test_' . date('YmdHis') . '.xml');
            file_put_contents($path, $signedXml);

            $this->info("XML salvo em: {$path}");

        } catch (\Exception $e) {
            $this->error("Erro: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
        }
    }
}
