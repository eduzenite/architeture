<?php

return [
    'environment' => env('NFSE_ENVIRONMENT', 'homolog'), // homolog or production
    'certificate' => [
        'path' => env('NFSE_CERTIFICATE_PATH', storage_path('certificates/nfse_certificate.pfx')),
        'password' => env('NFSE_CERTIFICATE_PASSWORD', ''),
    ],
    'services' => [
        'prefeitura_sao_paulo' => [
            'homolog' => 'https://www.nfse.gov.br',
            'production' => 'https://homologacao.nfse.gov.br',
        ],
    ],
];
