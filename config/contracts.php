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
    'template_path' => 'contracts/templates/Contrato_VCA_Clube_atualizado.pdf',

    // Nomes de arquivos gerados pelo servidor (diretório por UUID).
    'signature_filename' => 'signature.png',
    'signed_default_filename' => 'contrato-assinado.pdf',
    'filled_filename' => 'contrato-preenchido.pdf',

    // Campos preenchidos automaticamente na página 1 do contrato.
    // Coordenadas calibradas sobre o PDF real com pdftotext -bbox (A4
    // retrato 595.276 x 841.89 pt, origem topo-esquerda — mesmo sistema
    // FPDF). O valor de "y" é a linha de base do texto: em writeFieldText
    // o texto é gravado com setXY(x, y-8) + Cell(0, 10), e a base da fonte
    // size 10 cai exatamente em "y" (size 9 cai em y-0.3).
    'fields' => [
        1 => [
            ['source' => 'full_name', 'x' => 128.9, 'y' => 235.3, 'size' => 10, 'max_width' => 400.0],
            ['source' => 'cpf', 'x' => 80.3, 'y' => 253.5, 'size' => 10, 'max_width' => 200.0],
            ['source' => 'cnh_number', 'x' => 99.9, 'y' => 271.7, 'size' => 10, 'max_width' => 200.0],
            ['source' => 'birth_date', 'x' => 155.9, 'y' => 289.9, 'size' => 10, 'max_width' => 150.0],
            ['source' => 'full_address', 'x' => 106.3, 'y' => 308.4, 'size' => 9, 'max_width' => 430.0],
            ['source' => 'phone', 'x' => 102.9, 'y' => 326.3, 'size' => 10, 'max_width' => 200.0],
            ['source' => 'email', 'x' => 91.9, 'y' => 344.5, 'size' => 10, 'max_width' => 430.0],
        ],
    ],

    'pdf' => [
        // Página do modelo em que a assinatura e os dados são sobrepostos.
        'template_page' => 12,
        'page_width' => 595.276,
        'page_height' => 841.89,
        'font' => 'Helvetica',

        // Antes de escrever cada valor, um retângulo branco cobre o
        // placeholder do modelo ("[NOME COMPLETO]", "[●]",
        // "____/____/________") para ele não "vazar" ao redor do texto.
        'mask_placeholder' => true,
        'mask_height' => 11.0,

        // Bloco de assinatura do PARTICIPANTE na página 12 do modelo real.
        // Linha "Assinatura:" em y≈56–66 (x até ≈282): a caixa da imagem
        // (topo y=53, altura até 16) sobrepõe a linha. Demais campos têm a
        // linha de base calibrada nos rótulos "Nome:", "CPF:" e "Data:".
        'signature' => [
            'x' => 111.8,
            'y' => 53.0,
            'max_width' => 140.0,
            'max_height' => 16.0,
        ],
        'signer_name' => [
            'x' => 90.2,
            'y' => 103.9,
            'size' => 10,
        ],
        'signer_cpf' => [
            'x' => 80.3,
            'y' => 122.4,
            'size' => 9,
        ],
        'signed_date' => [
            'x' => 85.0,
            'y' => 334.6,
            'size' => 9,
            // Largura do placeholder "____/____/________" (x 85–165) que o
            // retângulo branco cobre antes do valor da data ser escrito.
            'mask_width' => 80.0,
        ],
    ],
];
