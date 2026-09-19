<?php

namespace Tests\Concerns;

use App\Models\QuotaType;
use App\Models\Vehicle;
use App\Models\VehicleQuotaConfiguration;

trait CreatesQuotaContext
{
    private static int $quotaContextSequence = 0;

    /**
     * Cria um veículo com um tipo de cota e uma configuração com vagas
     * suficientes para os cenários legados.
     *
     * @return array{0: Vehicle, 1: QuotaType}
     */
    protected function createQuotaContext(int $days, int $quantity = 50): array
    {
        $sequence = ++self::$quotaContextSequence;

        $quota = QuotaType::query()->create([
            'code' => 'Q'.$sequence,
            'name' => 'Mensal',
            'days' => $days,
            'active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'model' => 'Civic 2025',
            'plate' => 'QTA-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'active' => true,
        ]);

        VehicleQuotaConfiguration::query()->create([
            'vehicle_id' => $vehicle->id,
            'quota_type_id' => $quota->id,
            'quantity' => $quantity,
            'active' => true,
        ]);

        return [$vehicle, $quota];
    }

    /**
     * Período inclusivo começando hoje (America/Sao_Paulo) com a duração
     * indicada, sempre compatível com a regra `exact`.
     *
     * @return array{0: string, 1: string}
     */
    protected function bookingPeriod(int $days): array
    {
        $start = now('America/Sao_Paulo')->startOfDay();
        $end = $start->copy()->addDays($days - 1);

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }
}
