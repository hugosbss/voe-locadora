<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function totalSoldQuotaCount(): int
    {
        return $this->registrations()
            ->where('status', RegistrationStatus::Aprovado->value)
            ->count();
    }

    public function totalAvailableQuotaCount(): int
    {
        return $this->totalQuotaCount() - $this->totalSoldQuotaCount();
    }

    /**
     * Retorna apenas as configurações ativas com disponibilidade real.
     */
    public function availableQuotaConfigurations(): Collection
    {
        return $this->quotaConfigurations()
            ->where('active', true)
            ->with('quotaType')
            ->get()
            ->filter(fn (VehicleQuotaConfiguration $configuration) => $configuration->availableCount() > 0)
            ->values();
    }
}
