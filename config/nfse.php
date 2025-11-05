<?php

return [
    'certificate' => [
        'pemPath' => storage_path('app/public/certificates/pem1/nfse_certificate.pem'),
        'pemPassword' => env('NFSE_CERT_PASSWORD'),
        'cacertPath' => env('NFSE_CACERT_PATH', storage_path('app/public/certificates/cacert.pem')),
    ],
    'endpoint' => [
        'NF' => env('NFSE_ENDPOINT_NF', 'https://nfe.prefeitura.sp.gov.br/ws/lotenfe.asmx?wsdl'),
        'NFAsync' => env('NFSE_ENDPOINT_NF_ASYNC', 'https://nfe.prefeitura.sp.gov.br/ws/lotenfe.asmx?wsdl'),
        'NFTS' => env('NFSE_ENDPOINT_NFTS', 'https://nfe.prefeitura.sp.gov.br/ws/consultacnpj.asmx?wsdl'),
    ],
    'company' => [
        'cnpj' => env('NFSE_COMPANY_CNPJ'),
        'municipalRegistration' => env('NFSE_COMPANY_MUNICIPAL_REGISTRATION'),
    ],
    'homologation' => env('NFSE_HOMOLOGACAO', false),
];
