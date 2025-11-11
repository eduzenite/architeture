<?php

return [
    'xsdPath' => app_path('Infra/PrefeituraSaoPaulo/'),
    'certificate' => [
        'certPath' => env('NFSE_CERT_PATH', storage_path('app/public/certificates/pem1/cert.pem')),
        'keyPath' => env('NFSE_KEY_PATH', storage_path('app/public/certificates/pem1/key.pem')),
        'pemPassword' => env('NFSE_CERT_PASSWORD'),
        'cacertPath' => env('NFSE_CACERT_PATH', storage_path('app/public/certificates/cacert.pem')),
    ],
    'endpoint' => [
        'NF' => 'https://nfe.prefeitura.sp.gov.br/ws/lotenfe.asmx',
        'NFAsync' => 'https://nfe.prefeitura.sp.gov.br/ws/lotenfeasync.asmx',
        'NFTS' => 'https://nfe.prefeitura.sp.gov.br/ws/nfts.asmx',
    ],
    'company' => [
        'cnpj' => env('NFSE_COMPANY_CNPJ'),
        'municipalRegistration' => env('NFSE_COMPANY_MUNICIPAL_REGISTRATION'),
    ],
];
