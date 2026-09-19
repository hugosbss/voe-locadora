<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function soldCount(): int
    {
        return $this->registrations()
            ->where('status', RegistrationStatus::Aprovado->value)
            ->count();
    }
}
