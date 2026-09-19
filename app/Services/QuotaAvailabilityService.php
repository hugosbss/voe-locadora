<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Exceptions\QuotaUnavailableException;
use App\Models\ClientRegistration;
use App\Models\QuotaType;
use App\Models\Vehicle;
use App\Models\VehicleQuotaConfiguration;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Ponto único de verdade para disponibilidade de cotas.
 *
 * Regras (configuráveis em config/quotas.php):
 *  - Datas são INCLUSIVAS: dois períodos conflitam quando
 *    start_a <= end_b e end_a >= start_b.
 *  - Apenas os status listados em quotas.consuming_statuses ocupam vaga.
 *  - A duração do período é comparada com quota_type.days conforme
 *    quotas.duration_mode (exact|max|min).
 *  - Com quotas.vehicle_exclusive = true, qualquer reserva consumidora do
 *    veículo ocupa a vaga, independente do tipo de cota.
 */
class QuotaAvailabilityService
{
    /**
     * @return array<int, string>
     */
    public function consumingStatusValues(): array
    {
        return array_map(
            static fn (RegistrationStatus $status): string => $status->value,
            RegistrationStatus::consuming(),
        );
    }

    public function durationMode(): string
    {
        return (string) config('quotas.duration_mode', 'exact');
    }

    public function vehicleExclusive(): bool
    {
        return (bool) config('quotas.vehicle_exclusive', false);
    }

    /**
     * Quantidade de dias do período, considerando as duas pontas.
     */
    public function periodDays(CarbonInterface $start, CarbonInterface $end): int
    {
        $startDay = Carbon::parse($start->toDateString());
        $endDay = Carbon::parse($end->toDateString());

        return (int) $startDay->diffInDays($endDay, true) + 1;
    }

    public function isPeriodValidForType(QuotaType $type, CarbonInterface $start, CarbonInterface $end): bool
    {
        if ($end->lessThan($start)) {
            return false;
        }

        $days = $this->periodDays($start, $end);
        $expected = (int) $type->days;

        return match ($this->durationMode()) {
            'max' => $days <= $expected,
            'min' => $days >= $expected,
            default => $days === $expected,
        };
    }

    public function endDateFor(QuotaType $type, CarbonInterface $start): CarbonInterface
    {
        return Carbon::parse($start->toDateString())->addDays(max(0, (int) $type->days - 1));
    }

    public function configurationFor(int $vehicleId, int $quotaTypeId): ?VehicleQuotaConfiguration
    {
        return VehicleQuotaConfiguration::query()
            ->with('quotaType')
            ->where('vehicle_id', $vehicleId)
            ->where('quota_type_id', $quotaTypeId)
            ->first();
    }

    public function lockVehicleFor(int $vehicleId): void
    {
        Vehicle::query()->whereKey($vehicleId)->lockForUpdate()->first();
    }

    public function lockConfigurationFor(int $vehicleId, int $quotaTypeId): VehicleQuotaConfiguration
    {
        $configuration = VehicleQuotaConfiguration::query()
            ->with('quotaType')
            ->where('vehicle_id', $vehicleId)
            ->where('quota_type_id', $quotaTypeId)
            ->lockForUpdate()
            ->first();

        if (! $configuration) {
            throw QuotaUnavailableException::notConfigured();
        }

        return $configuration;
    }

    public function reservedFor(
        VehicleQuotaConfiguration $configuration,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $ignoreRegistrationId = null,
        ?bool $exclusive = null,
    ): int {
        $exclusive ??= $this->vehicleExclusive();

        $query = ClientRegistration::query()
            ->whereIn('status', $this->consumingStatusValues())
            ->where('vehicle_id', $configuration->vehicle_id)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());

        if (! $exclusive) {
            $query->where('quota_type_id', $configuration->quota_type_id);
        }

        if ($ignoreRegistrationId !== null) {
            $query->whereKeyNot($ignoreRegistrationId);
        }

        return $query->count();
    }

    public function availableFor(
        VehicleQuotaConfiguration $configuration,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $ignoreRegistrationId = null,
        ?bool $exclusive = null,
    ): int {
        $reserved = $this->reservedFor($configuration, $start, $end, $ignoreRegistrationId, $exclusive);

        return max(0, (int) $configuration->quantity - $reserved);
    }

    /**
     * Valida a reserva contra o estado atual da cota. Quando $lock = true,
     * trava a linha da configuração (SELECT ... FOR UPDATE) para serializar
     * requisições concorrentes.
     *
     * @throws QuotaUnavailableException
     */
    public function assertCanReserve(
        int $vehicleId,
        int $quotaTypeId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $ignoreRegistrationId = null,
        bool $lock = false,
    ): VehicleQuotaConfiguration {
        if ($lock && $this->vehicleExclusive()) {
            $this->lockVehicleFor($vehicleId);
        }

        $configuration = $lock
            ? $this->lockConfigurationFor($vehicleId, $quotaTypeId)
            : $this->configurationFor($vehicleId, $quotaTypeId);

        if (! $configuration) {
            throw QuotaUnavailableException::notConfigured();
        }

        if (! $this->isPeriodValidForType($configuration->quotaType, $start, $end)) {
            throw QuotaUnavailableException::invalidDuration();
        }

        if (! $configuration->active || (int) $configuration->quantity <= 0) {
            throw QuotaUnavailableException::unavailable();
        }

        if ($this->availableFor($configuration, $start, $end, $ignoreRegistrationId) <= 0) {
            throw QuotaUnavailableException::unavailable();
        }

        return $configuration;
    }

    public function hasActiveReservations(VehicleQuotaConfiguration $configuration, ?CarbonInterface $from = null): bool
    {
        $from ??= now(config('app.timezone'));

        return ClientRegistration::query()
            ->whereIn('status', $this->consumingStatusValues())
            ->where('vehicle_id', $configuration->vehicle_id)
            ->where('quota_type_id', $configuration->quota_type_id)
            ->whereDate('end_date', '>=', $from->toDateString())
            ->exists();
    }

    /**
     * Maior número de reservas consumidoras simultâneas (varredura de
     * eventos fim+1 / início). As datas são inclusivas, então o fim é
     * convertido para um limite exclusivo somando um dia.
     */
    public function peakConcurrentReservations(VehicleQuotaConfiguration $configuration): int
    {
        $events = [];

        $registrations = ClientRegistration::query()
            ->whereIn('status', $this->consumingStatusValues())
            ->where('vehicle_id', $configuration->vehicle_id)
            ->where('quota_type_id', $configuration->quota_type_id)
            ->get(['start_date', 'end_date']);

        foreach ($registrations as $registration) {
            $start = Carbon::parse($registration->start_date)->startOfDay();
            $end = Carbon::parse($registration->end_date)->startOfDay()->addDay();

            $events[] = [$start->timestamp, 1];
            $events[] = [$end->timestamp, -1];
        }

        usort($events, static fn (array $a, array $b): int => ($a[0] <=> $b[0]) ?: ($a[1] <=> $b[1]));

        $current = 0;
        $peak = 0;

        foreach ($events as [, $delta]) {
            $current += $delta;
            $peak = max($peak, $current);
        }

        return $peak;
    }

    /**
     * Disponibilidade de todas as cotas ativas de um veículo no período.
     *
     * @return array<int, array<string, mixed>>
     */
    public function availabilityMapForVehicle(int $vehicleId, CarbonInterface $start, CarbonInterface $end): array
    {
        return $this->activeConfigurationsForVehicle($vehicleId)
            ->map(function (VehicleQuotaConfiguration $configuration) use ($start, $end): array {
                $reserved = $this->reservedFor($configuration, $start, $end);

                return [
                    'quota_type_id' => (int) $configuration->quota_type_id,
                    'code' => $configuration->quotaType->code,
                    'name' => $configuration->quotaType->name,
                    'days' => (int) $configuration->quotaType->days,
                    'quantity' => (int) $configuration->quantity,
                    'reserved' => $reserved,
                    'available' => max(0, (int) $configuration->quantity - $reserved),
                    'valid_duration' => $this->isPeriodValidForType($configuration->quotaType, $start, $end),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{quantity:int,reserved_today:int,available_today:int}
     */
    public function statsForConfiguration(VehicleQuotaConfiguration $configuration): array
    {
        $today = now(config('app.timezone'));
        $reserved = $this->reservedFor($configuration, $today, $today);

        return [
            'quantity' => (int) $configuration->quantity,
            'reserved_today' => $reserved,
            'available_today' => max(0, (int) $configuration->quantity - $reserved),
        ];
    }

    /**
     * @return array{total:int,reserved_today:int,available_today:int}
     */
    public function statsForVehicle(Vehicle $vehicle): array
    {
        $today = now(config('app.timezone'));

        $total = 0;
        $reserved = 0;

        foreach ($vehicle->quotaConfigurations()->with('quotaType')->get() as $configuration) {
            $total += (int) $configuration->quantity;
            $reserved += $this->reservedFor($configuration, $today, $today);
        }

        return [
            'total' => $total,
            'reserved_today' => $reserved,
            'available_today' => max(0, $total - $reserved),
        ];
    }

    /**
     * @return Collection<int, VehicleQuotaConfiguration>
     */
    public function activeConfigurationsForVehicle(int $vehicleId): Collection
    {
        return VehicleQuotaConfiguration::query()
            ->with('quotaType')
            ->where('vehicle_id', $vehicleId)
            ->where('active', true)
            ->where('quantity', '>', 0)
            ->whereHas('quotaType', static fn ($query) => $query->where('active', true))
            ->get();
    }
}
