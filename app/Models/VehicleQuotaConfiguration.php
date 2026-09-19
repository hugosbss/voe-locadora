<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Disponibilidade e ocupação de cotas são responsabilidade exclusiva de
 * App\Services\QuotaAvailabilityService. Não adicione contadores globais
 * (sem período) aqui: eles ignoram datas e status configuráveis.
 */
class VehicleQuotaConfiguration extends Model
{
    protected $table = 'vehicle_quota_configurations';

    protected $fillable = [
        'vehicle_id',
        'quota_type_id',
        'quantity',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function quotaType(): BelongsTo
    {
        return $this->belongsTo(QuotaType::class);
    }
}
