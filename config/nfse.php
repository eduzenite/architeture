<?php

return [
    'certificate' => [
        'pemPath' => env('NFSE_PEM_PATH', storage_path('app/public/certificates/nfse_certificate.pem')),
        'password' => env('NFSE_CERT_PASSWORD', ''),
        'cacertPath' => env('NFSE_CACERT_PATH', storage_path('app/public/certificates/cacert.pem')),
    ],
    'services' => [
        'prefeituraSaoPaulo' => [
            'endpointNFe' => 'https://nfe.prefeitura.sp.gov.br',
            'endpointNFeAsync' => 'https://nfews.prefeitura.sp.gov.br',
        ],
    ],
];
