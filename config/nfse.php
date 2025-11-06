<?php

return [
    'certificate' => [
        'certPath' => env('NFSE_CERT_PATH', storage_path('app/public/certificates/pem1/cert.pem')),
        'keyPath' => env('NFSE_KEY_PATH', storage_path('app/public/certificates/pem1/key.pem')),
        'pemPassword' => env('NFSE_CERT_PASSWORD'),
        'cacertPath' => env('NFSE_CACERT_PATH', storage_path('app/public/certificates/cacert.pem')),
    ],
    'endpoint' => [
        'NF' => env('NFSE_ENDPOINT_NF', 'https://nfe.prefeitura.sp.gov.br/ws/lotenfe.asmx'),
        'NFAsync' => env('NFSE_ENDPOINT_NF_ASYNC', 'https://nfe.prefeitura.sp.gov.br/ws/lotenfe.asmx'),
        'NFTS' => env('NFSE_ENDPOINT_NFTS', 'https://nfe.prefeitura.sp.gov.br/ws/consultacnpj.asmx'),
    ],
    'company' => [
        'cnpj' => env('NFSE_COMPANY_CNPJ'),
        'municipalRegistration' => env('NFSE_COMPANY_MUNICIPAL_REGISTRATION'),
    ],
    'homologation' => env('NFSE_HOMOLOGACAO', false),
];
