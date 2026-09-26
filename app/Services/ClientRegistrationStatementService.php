<?php

namespace App\Services;

use App\Models\ClientRegistration;
use App\Models\QuotaType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Monta o extrato do cliente a partir dos dados que o cadastro JÁ possui.
 *
 * O extrato é somente uma leitura: nada é gravado e nenhuma entidade nova é
 * criada. A quantidade de dias exibida é lida de `quota_types.days` (a cota
 * cadastrada) — nenhuma regra nova de duração é criada aqui, e nenhum valor
 * é fixo no código. As datas exibidas são os dias já registrados no período
 * da cota (`start_date` → `end_date`) que caem no mês visualizado, agrupados
 * por semana de segunda a domingo e rotulados com o dia da semana resolvido
 * pelo Carbon conforme o locale da aplicação.
 */
class ClientRegistrationStatementService
{
    public function __construct(
        private readonly QuotaAvailabilityService $quotaAvailability,
    ) {}

    /**
     * @return array{
     *     month: array{label: string, input: string, previous: string, next: string},
     *     client: array{name: string, cpf: string},
     *     quota: array{code: string|null, name: string|null, days: int|null},
     *     vehicle: array{model: string|null, plate: string|null},
     *     period: array{start: string|null, end: string|null},
     *     weeks: array<int, array{ordinal: string, days: array<int, array{day: string, date: string, weekday: string}>}>,
     *     total: int|null,
     *     daysInMonth: int
     * }
     */
    public function build(ClientRegistration $registration, CarbonInterface $month): array
    {
        $registration->loadMissing(['vehicle', 'quotaType']);

        $quota = $registration->quotaType;
        $quotaDays = $quota instanceof QuotaType ? (int) $quota->days : null;
        $period = $this->periodFor($registration, $quota);
        $weeks = $this->weeksForMonth($period, $month);

        return [
            'month' => [
                'label' => mb_strtoupper($month->translatedFormat('F/Y')),
                'input' => $month->format('Y-m'),
                'previous' => $month->copy()->subMonth()->format('Y-m'),
                'next' => $month->copy()->addMonth()->format('Y-m'),
            ],
            'client' => [
                'name' => (string) $registration->full_name,
                'cpf' => $registration->maskedCpf(),
            ],
            'quota' => [
                'code' => $quota?->code,
                'name' => $quota?->name,
                'days' => $quotaDays,
            ],
            'vehicle' => [
                'model' => $registration->vehicle?->model,
                'plate' => $registration->vehicle?->plate,
            ],
            'period' => [
                'start' => $period['start']?->format('d/m/Y'),
                'end' => $period['end']?->format('d/m/Y'),
            ],
            'weeks' => $weeks,
            'total' => $quotaDays,
            'daysInMonth' => $this->countDays($weeks),
        ];
    }

    /**
     * Período da cota já registrado no cadastro. Sem `start_date` não há
     * calendário a exibir; sem `end_date`, o fim é obtido pela regra já
     * existente em QuotaAvailabilityService (a mesma da venda).
     *
     * @return array{start: CarbonInterface|null, end: CarbonInterface|null}
     */
    private function periodFor(ClientRegistration $registration, ?QuotaType $quota): array
    {
        if (! $registration->start_date) {
            return ['start' => null, 'end' => null];
        }

        $start = Carbon::parse($registration->start_date)->startOfDay();

        $end = $registration->end_date
            ? Carbon::parse($registration->end_date)->startOfDay()
            : ($quota instanceof QuotaType
                ? Carbon::parse($this->quotaAvailability->endDateFor($quota, $start)->toDateString())->startOfDay()
                : null);

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Dias do período que caem no mês, agrupados por semana (segunda a
     * domingo) e numerados a partir da primeira semana com dias da cota.
     *
     * @param  array{start: CarbonInterface|null, end: CarbonInterface|null}  $period
     * @return array<int, array{ordinal: string, days: array<int, array{day: string, date: string, weekday: string}>}>
     */
    private function weeksForMonth(array $period, CarbonInterface $month): array
    {
        if ($period['start'] === null || $period['end'] === null) {
            return [];
        }

        $first = $period['start']->greaterThan($month->copy()->startOfMonth())
            ? $period['start']->copy()
            : $month->copy()->startOfMonth();

        $last = $period['end']->lessThan($month->copy()->endOfMonth())
            ? $period['end']->copy()
            : $month->copy()->endOfMonth();

        if ($first->greaterThan($last)) {
            return [];
        }

        $byWeek = [];

        for ($day = $first->copy(); $day->lessThanOrEqualTo($last); $day->addDay()) {
            $key = $day->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString();

            $byWeek[$key][] = [
                'day' => $day->format('d/m'),
                'date' => $day->format('d/m/Y'),
                'weekday' => Str::ucfirst($day->translatedFormat('l')),
            ];
        }

        $weeks = [];
        $number = 0;

        foreach ($byWeek as $days) {
            $weeks[] = [
                'ordinal' => ++$number.'ª',
                'days' => $days,
            ];
        }

        return $weeks;
    }

    /**
     * @param  array<int, array{ordinal: string, days: array<int, array{day: string, date: string, weekday: string}>}>  $weeks
     */
    private function countDays(array $weeks): int
    {
        return array_sum(array_map(
            static fn (array $week): int => count($week['days']),
            $weeks,
        ));
    }
}
