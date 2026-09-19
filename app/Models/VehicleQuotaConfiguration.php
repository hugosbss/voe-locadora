<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function soldCount(): int
    {
        return ClientRegistration::query()
            ->where('vehicle_id', $this->vehicle_id)
            ->where('quota_type_id', $this->quota_type_id)
            ->where('status', RegistrationStatus::Aprovado->value)
            ->count();
    }

    public function availableCount(): int
    {
        return max($this->quantity - $this->soldCount(), 0);
    }
}
