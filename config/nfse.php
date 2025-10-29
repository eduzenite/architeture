<?php

return [
    'environment' => env('NFSE_ENVIRONMENT', 'homolog'), // homolog or production
    'certificate' => [
        'pem_path' => env('NFSE_PEM_PATH', storage_path('app/public/certificates/nfse_certificate.pem')),
        'password' => env('NFSE_CERT_PASSWORD', ''),
        'cacert_path' => env('NFSE_CACERT_PATH', storage_path('app/public/certificates/cacert.pem')),
    ],
    'services' => [
        'prefeitura_sao_paulo' => [
            'loteNFe' => 'https://nfe.prefeitura.sp.gov.br/ws/lotenfe.asmx?WSDL',
            'loteNFeAsync' => 'https://nfews.prefeitura.sp.gov.br/lotenfeasync.asmx?WSDL',
        ],
    ],
];
