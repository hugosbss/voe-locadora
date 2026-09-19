<?php

use App\Enums\RegistrationStatus;

return [
    /*
    |--------------------------------------------------------------------------
    | Regras de disponibilidade de cotas
    |--------------------------------------------------------------------------
    |
    | Ponto único de configuração da regra de negócio de cotas. Alterar
    | qualquer valor aqui muda o comportamento de TODAS as telas e
    | validações, que passam por App\Services\QuotaAvailabilityService.
    |
    */

    /*
     * Status que CONSOMEM (ocupam) uma vaga no período. Somente "reprovado"
     * não consome — os demais seguem ocupando a vaga até serem reprovados ou
     * até o período deixar de sobrepor as datas consultadas.
     */
    'consuming_statuses' => [
        RegistrationStatus::Novo->value,
        RegistrationStatus::EmAnalise->value,
        RegistrationStatus::Aprovado->value,
    ],

    /*
     * Regra de duração do período do cadastro em relação a quota_type.days:
     *   - 'exact': (end_date - start_date + 1) dias == days
     *   - 'max':   (end_date - start_date + 1) dias <= days
     *   - 'min':   (end_date - start_date + 1) dias >= days
     */
    'duration_mode' => env('QUOTA_DURATION_MODE', 'exact'),

    /*
     * TTL (em horas) de cadastros no status "novo". DESLIGADO nesta etapa
     * (null). A chave fica documentada para uma evolução futura; nenhuma
     * expiração automática é implementada agora, e nenhum Schedule/Job é
     * criado.
     */
    'novo_ttl_hours' => null,

    /*
     * Exclusividade física do veículo. Quando true, qualquer reserva
     * consumidora do veículo ocupa a vaga, independente do tipo de cota
     * (o veículo físico não pode estar em dois períodos sobrepostos).
     * Quando false (padrão), a disponibilidade é por veículo + tipo de cota.
     */
    'vehicle_exclusive' => (bool) env('QUOTA_VEHICLE_EXCLUSIVE', false),
];
