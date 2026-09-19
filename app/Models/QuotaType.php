<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ocupação de uma cota é sempre calculada por período por
 * App\Services\QuotaAvailabilityService. Contadores globais (sem datas)
 * não representam a regra de negócio e não devem existir aqui.
 */
class QuotaType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'days',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'days' => 'integer',
        ];
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(VehicleQuotaConfiguration::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ClientRegistration::class);
    }
}
