<?php

namespace App\Infra\PrefeituraSaoPaulo;

use Exception;
use Illuminate\Support\Facades\Storage;

class NfseAuth
{
    /**
     * Caminho do certificado digital (.pfx)
     * @var string
     */
    protected string $certificatePath;

    /**
     * Senha do certificado digital
     * @var string
     */
    protected string $certificatePassword;

    /**
     * Caminho temporário do arquivo PEM convertido
     * @var string|null
     */
    protected ?string $pemPath = null;

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
     * Define se o arquivo .pem temporário deve ser mantido
     * @var bool
     */
    protected bool $keepPem = false;

    /**
     * Construtor
     *
     * @param string|null $environment
     * @throws Exception
     */
    public function __construct(?string $environment = null)
    {
        $this->environment = $environment ?? config('nfse.environment', 'homolog');
        $this->certificatePath = config('nfse.certificate.path');
        $this->certificatePassword = config('nfse.certificate.password');

        if (!file_exists($this->certificatePath)) {
            throw new Exception("Certificado PFX não encontrado em: {$this->certificatePath}");
        }
    }

    /**
     * Executa o processo de autenticação (conversão do PFX para PEM).
     *
     * @return bool
     * @throws Exception
     */
    public function authenticate(): bool
    {
        try {
            if ($this->pemPath && file_exists($this->pemPath)) {
                // Já autenticado anteriormente
                return true;
            }

            $this->pemPath = $this->convertPfxToPem();
            return true;
        } catch (Exception $e) {
            throw new Exception("Falha na autenticação NFSe: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Converte o certificado .pfx para .pem (necessário para cURL/SoapClient)
     *
     * @return string Caminho do arquivo PEM
     * @throws Exception
     */
    protected function convertPfxToPem(): string
    {
        $pfxContent = file_get_contents($this->certificatePath);

        if (!$pfxContent) {
            throw new Exception("Falha ao ler o certificado PFX.");
        }

        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $this->certificatePassword)) {
            throw new Exception("Não foi possível decodificar o certificado PFX. Verifique a senha ou formato do arquivo.");
        }

        $pemContent = $certs['cert'] . $certs['pkey'];
        $pemPath = storage_path('app/nfse_cert_' . md5($this->certificatePath . time()) . '.pem');

        file_put_contents($pemPath, $pemContent);

        return $pemPath;
    }

    /**
     * Retorna o caminho absoluto do arquivo PEM convertido.
     *
     * @return string
     * @throws Exception
     */
    public function getPemPath(): string
    {
        if (!$this->pemPath || !file_exists($this->pemPath)) {
            $this->authenticate();
        }

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
     * @throws Exception
     */
    public function getStreamContext()
    {
        if (!$this->pemPath) {
            $this->authenticate();
        }

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
     * Define se o arquivo PEM temporário deve ser mantido no disco.
     *
     * @param bool $keep
     * @return self
     */
    public function keepPemFile(bool $keep = true): self
    {
        $this->keepPem = $keep;
        return $this;
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

    /**
     * Limpa o arquivo PEM ao destruir o objeto
     */
    public function __destruct()
    {
        if (!$this->keepPem && $this->pemPath && file_exists($this->pemPath)) {
            @unlink($this->pemPath);
        }
    }
}
