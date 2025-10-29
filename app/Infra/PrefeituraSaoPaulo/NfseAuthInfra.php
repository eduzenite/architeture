<?php

namespace App\Infra\PrefeituraSaoPaulo;

use Exception;

class NfseAuthInfra
{
    /**
     * Caminho do certificado PEM já convertido.
     * @var string
     */
    protected string $pemPath;

    /**
     * Senha do certificado digital (se aplicável).
     * @var string
     */
    protected string $certificatePassword;

    /**
     * Ambiente atual (homologação ou produção)
     * @var string
     */
    protected string $environment;

    /**
     * Token de autenticação (para APIs REST)
     * @var string|null
     */
    protected ?string $authToken = null;

    /**
     * Construtor
     *
     * @param string|null $environment
     * @throws Exception
     */
    public function __construct(?string $environment = null)
    {
        $this->environment = $environment ?? config('nfse.environment', 'homolog');

        // Lê o caminho e a senha do config
        $this->pemPath = config('nfse.certificate.pem_path', storage_path('app/public/certificates/nfse_certificate.pem'));
        $this->certificatePassword = config('nfse.certificate.password', '');

        if (!file_exists($this->pemPath)) {
            throw new Exception("Certificado PEM não encontrado em: {$this->pemPath}");
        }

        if (!is_readable($this->pemPath)) {
            throw new Exception("Sem permissão de leitura para o arquivo PEM.");
        }
    }

    /**
     * Retorna o caminho absoluto do arquivo PEM.
     *
     * @return string
     */
    public function getPemPath(): string
    {
        return $this->pemPath;
    }

    /**
     * Retorna a senha do certificado (necessária para cURL / OpenSSL)
     *
     * @return string
     */
    public function getCertificatePassword(): string
    {
        return $this->certificatePassword;
    }

    /**
     * Retorna o contexto SSL configurado (útil para SoapClient)
     *
     * @return resource
     */
    public function getStreamContext()
    {
        return stream_context_create([
            'ssl' => [
                'local_cert' => $this->pemPath,
                'passphrase' => $this->certificatePassword,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);
    }

    /**
     * Retorna os headers HTTP padrão para chamadas REST.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/xml;charset=utf-8',
        ];

        if ($this->authToken) {
            $headers['Authorization'] = 'Bearer ' . $this->authToken;
        }

        return $headers;
    }

    /**
     * Retorna o token de autenticação (se houver)
     *
     * @return string|null
     */
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    /**
     * Retorna o ambiente atual
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Gera um token de autenticação (caso o endpoint exija)
     *
     * @return string
     * @throws Exception
     */
    protected function generateAuthToken(): string
    {
        throw new Exception("Geração de token não implementada. Avaliar necessidade conforme endpoint DPS/NFSe.");
    }
}
