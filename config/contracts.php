<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contrato de adesão do cliente VCA
    |--------------------------------------------------------------------------
    |
    | Configuração centralizada da etapa final do cadastro público. A versão
    | é registrada junto ao cadastro no momento do aceite; o PDF assinado é
    | derivado do modelo estático (12 páginas) por overlay (FPDF + FPDI).
    |
    */

    // Versão vigente do contrato. Qualquer alteração de conteúdo exige
    // versão nova; o consentimento registrado sempre referencia a versão
    // do momento do aceite.
    'version' => env('CONTRACT_VERSION', '1.0'),

    // Modelo oficial — somente leitura, disco privado, nunca em public/.
    'template_path' => 'contracts/templates/Contrato_VCA_Clube_de_Mobilidade.pdf',

    // Nomes de arquivos gerados pelo servidor (diretório por UUID).
    'signature_filename' => 'signature.png',
    'signed_default_filename' => 'contrato-assinado.pdf',

    // Área de assinatura do PARTICIPANTE na página 12 do modelo real
    // (A4 retrato, largura 595,276 pts, altura 841,89 pts). Coordenadas
    // em pontos, origem topo-esquerda (FPDF), calibradas na análise do
    // modelo: linha "Assinatura:" em y≈767–792 a partir do topo, x até a
    // margem direita (≈540). A imagem é escalada preservando a proporção.
    'pdf' => [
        'template_page' => 12,
        'page_width' => 595.276,
        'page_height' => 841.89,
        'font' => 'Helvetica',
        'signature' => [
            'x' => 130.0,
            'y' => 767.0,
            'max_width' => 330.0,
            'max_height' => 33.0,
        ],
        'signer_name' => [
            'x' => 115.0,
            'y' => 736.0,
            'size' => 10,
        ],
        'signer_cpf' => [
            'x' => 115.0,
            'y' => 718.0,
            'size' => 9,
        ],
        'signed_date' => [
            'x' => 115.0,
            'y' => 509.0,
            'size' => 9,
        ],
    ],
];
