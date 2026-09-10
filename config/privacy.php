<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Política de Privacidade
    |--------------------------------------------------------------------------
    |
    | Versão da Política de Privacidade que fica registrada junto ao
    | consentimento de cada titular. Alterações de conteúdo da política
    | exigem versão nova; um consentimento registrado sempre referencia a
    | versão que estava vigente no momento do aceite.
    |
    */

    'version' => env('PRIVACY_POLICY_VERSION', '1.0'),

    'purpose' => 'Análise da solicitação de locação de veículo, incluindo conferência de identidade, habilitação e residência.',
];
