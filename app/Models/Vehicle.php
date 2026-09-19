<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estatísticas de cotas (total, reservadas, disponíveis) são calculadas por
 * App\Services\QuotaAvailabilityService, que considera período e status.
 * Não reintroduza contadores globais aqui.
 */
class Vehicle extends Model
{
    protected $fillable = [
        'model',
        'plate',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function quotaConfigurations(): HasMany
    {
        return $this->hasMany(VehicleQuotaConfiguration::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ClientRegistration::class);
    }

    public function totalQuotaCount(): int
    {
        return (int) $this->quotaConfigurations()->sum('quantity');
    }
}
