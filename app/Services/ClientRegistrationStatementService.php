<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use App\Models\QuotaType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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
 *
 * O extrato pertence a cadastros aprovados. Um cliente pode ter mais de um
 * cadastro aprovado (ciclos de cota diferentes), e é por isso que o histórico
 * de "anterior/próximo" navega entre os extratos do MESMO cliente (mesmo CPF) —
 * nunca entre clientes diferentes.
 */
class ClientRegistrationStatementService
{
    public function __construct(
        private readonly QuotaAvailabilityService $quotaAvailability,
    ) {}

    /**
     * Extratos disponíveis: apenas cadastros aprovados, do mais recente para
     * o mais antigo. A ordenação usa o início da cota já registrado no
     * cadastro e, como desempate, a data de cadastro — é a mesma ordenação
     * usada na listagem do módulo e no histórico de anterior/próximo.
     */
    public function approvedStatements(): Builder
    {
        return ClientRegistration::query()
            ->where('status', RegistrationStatus::Aprovado->value)
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{
     *     uuid: string,
     *     month: array{label: string, input: string, previous: string, next: string},
     *     client: array{name: string, cpf: string},
     *     quota: array{code: string|null, name: string|null, days: int|null},
     *     vehicle: array{model: string|null, plate: string|null},
     *     period: array{start: string|null, end: string|null},
     *     reference: array{label: string, date: string, sort: string},
     *     weeks: array<int, array{ordinal: string, days: array<int, array{day: string, date: string, weekday: string}>}>,
     *     total: int|null,
     *     daysInMonth: int
     * }
     */
    public function build(ClientRegistration $registration, CarbonInterface $month): array
    {
        $registration->loadMissing(['vehicle', 'quotaType']);

        $period = $this->periodFor($registration, $registration->quotaType);
        $weeks = $this->weeksForMonth($period, $month);

        return [
            'uuid' => (string) $registration->uuid,
            'month' => [
                'label' => mb_strtoupper($month->translatedFormat('F/Y')),
                'input' => $month->format('Y-m'),
                'previous' => $month->copy()->subMonth()->format('Y-m'),
                'next' => $month->copy()->addMonth()->format('Y-m'),
            ],
            'client' => $this->clientFor($registration),
            'quota' => $this->quotaFor($registration),
            'vehicle' => $this->vehicleFor($registration),
            'period' => $this->periodLabels($period),
            'reference' => $this->referenceFor($registration),
            'weeks' => $weeks,
            'total' => $this->totalFor($registration),
            'daysInMonth' => $this->countDays($weeks),
        ];
    }

    /**
     * Resumo do extrato para a listagem do módulo: os mesmos dados do extrato,
     * sem montar o calendário de dias do mês.
     *
     * @return array{
     *     uuid: string,
     *     client: array{name: string, cpf: string},
     *     quota: array{code: string|null, name: string|null, days: int|null},
     *     vehicle: array{model: string|null, plate: string|null},
     *     period: array{start: string|null, end: string|null},
     *     reference: array{label: string, date: string, sort: string},
     *     total: int|null
     * }
     */
    public function summaryFor(ClientRegistration $registration): array
    {
        $registration->loadMissing(['vehicle', 'quotaType']);

        return [
            'uuid' => (string) $registration->uuid,
            'client' => $this->clientFor($registration),
            'quota' => $this->quotaFor($registration),
            'vehicle' => $this->vehicleFor($registration),
            'period' => $this->periodLabels($this->periodFor($registration, $registration->quotaType)),
            'reference' => $this->referenceFor($registration),
            'total' => $this->totalFor($registration),
        ];
    }

    /**
     * Histórico de extratos do cliente do cadastro visualizado, do mais
     * recente para o mais antigo. `previous` ("Anterior") é o extrato mais
     * antigo e `next` ("Próximo") o mais recente; ambos são do MESMO cliente
     * (mesmo CPF), nunca de outro cliente. No extrato mais recente não há
     * "Próximo" e no mais antigo não há "Anterior".
     *
     * @return array{
     *     position: int,
     *     total: int,
     *     previous: array{registration: ClientRegistration, reference: string}|null,
     *     next: array{registration: ClientRegistration, reference: string}|null
     * }
     */
    public function historyFor(ClientRegistration $registration): array
    {
        $statements = $this->approvedStatements()
            ->where('cpf', $registration->cpf)
            ->get();

        $total = $statements->count();
        $position = $statements->search(
            fn (ClientRegistration $statement): bool => $statement->uuid === $registration->uuid,
        );

        if ($position === false) {
            return ['position' => 1, 'total' => $total, 'previous' => null, 'next' => null];
        }

        return [
            // A posição 1 é sempre o extrato mais recente.
            'position' => $position + 1,
            'total' => $total,
            'previous' => $this->historyEntry($statements[$position + 1] ?? null),
            'next' => $this->historyEntry($position > 0 ? $statements[$position - 1] : null),
        ];
    }

    /**
     * @return array{registration: ClientRegistration, reference: string}|null
     */
    private function historyEntry(?ClientRegistration $statement): ?array
    {
        return $statement === null ? null : [
            'registration' => $statement,
            'reference' => $this->referenceFor($statement)['date'],
        ];
    }

    /**
     * @return array{name: string, cpf: string}
     */
    private function clientFor(ClientRegistration $registration): array
    {
        return [
            'name' => (string) $registration->full_name,
            'cpf' => $registration->maskedCpf(),
        ];
    }

    /**
     * @return array{code: string|null, name: string|null, days: int|null}
     */
    private function quotaFor(ClientRegistration $registration): array
    {
        $quota = $registration->quotaType;

        return [
            'code' => $quota?->code,
            'name' => $quota?->name,
            'days' => $quota instanceof QuotaType ? (int) $quota->days : null,
        ];
    }

    /**
     * @return array{model: string|null, plate: string|null}
     */
    private function vehicleFor(ClientRegistration $registration): array
    {
        return [
            'model' => $registration->vehicle?->model,
            'plate' => $registration->vehicle?->plate,
        ];
    }

    /**
     * Total de dias exibido no extrato: exatamente a quantidade cadastrada
     * para a cota do cliente (`quota_types.days`).
     */
    private function totalFor(ClientRegistration $registration): ?int
    {
        return $this->quotaFor($registration)['days'];
    }

    /**
     * Data de referência do extrato — também é a data usada para ordenar a
     * listagem do módulo. Sem período de cota, cai para a data do cadastro.
     *
     * @return array{label: string, date: string, sort: string}
     */
    private function referenceFor(ClientRegistration $registration): array
    {
        if ($registration->start_date) {
            $date = Carbon::parse($registration->start_date)->startOfDay();

            return [
                'label' => 'Início da cota',
                'date' => $date->format('d/m/Y'),
                'sort' => $date->toDateString(),
            ];
        }

        $date = ($registration->created_at ?? Carbon::now(config('app.timezone')))
            ->timezone(config('app.timezone'));

        return [
            'label' => 'Data do cadastro',
            'date' => $date->format('d/m/Y'),
            'sort' => $date->toDateString(),
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
     * @param  array{start: CarbonInterface|null, end: CarbonInterface|null}  $period
     * @return array{start: string|null, end: string|null}
     */
    private function periodLabels(array $period): array
    {
        return [
            'start' => $period['start']?->format('d/m/Y'),
            'end' => $period['end']?->format('d/m/Y'),
        ];
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
