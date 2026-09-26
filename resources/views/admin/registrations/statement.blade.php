@extends('layouts.admin')

@section('title', 'Extrato do cliente')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.registrations.show', $registration) }}" class="back-link">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 17l-5-5m0 0 5-5m-5 5h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Voltar
            </a>
            <h1 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">Extrato do cliente</h1>
        </div>
        <a href="{{ route('admin.registrations.edit', $registration) }}" class="btn btn-secondary btn-sm self-start sm:self-auto">Editar cadastro</a>
    </div>

    {{-- Seleção do mês (apenas visualização: nada é gravado) --}}
    <div class="mb-6 rounded-2xl bg-surface-900 p-4 ring-1 ring-line-dark">
        <form method="GET" action="{{ route('admin.registrations.statement', $registration) }}" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-auto">
                <label class="form-label mb-1.5" for="statement-month">Mês do extrato</label>
                <input
                    type="month"
                    name="mes"
                    id="statement-month"
                    value="{{ $statement['month']['input'] }}"
                    class="form-input w-full sm:w-44">
            </div>

            <div class="flex w-full items-center gap-2 sm:w-auto">
                <x-button type="submit">Ver</x-button>
                <a
                    href="{{ route('admin.registrations.statement', [$registration, 'mes' => $statement['month']['previous']]) }}"
                    class="btn btn-ghost btn-sm"
                    aria-label="Mês anterior">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    Anterior
                </a>
                <a
                    href="{{ route('admin.registrations.statement', [$registration, 'mes' => $statement['month']['next']]) }}"
                    class="btn btn-ghost btn-sm"
                    aria-label="Próximo mês">
                    Próximo
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Cadastro geral --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-4">Cadastro geral</h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    @include('components.data-row', ['label' => 'Cliente', 'value' => $statement['client']['name']])
                    @include('components.data-row', ['label' => 'CPF', 'value' => $statement['client']['cpf']])
                    @include('components.data-row', [
                        'label' => 'Cota',
                        'value' => $statement['quota']['code'] ?: 'Não informada',
                    ])
                    @include('components.data-row', [
                        'label' => 'Nome da cota',
                        'value' => $statement['quota']['name'] ?: 'Não informado',
                    ])
                    @include('components.data-row', [
                        'label' => 'Veículo',
                        'value' => $statement['vehicle']['model'] ?: 'Não informado',
                    ])
                    @include('components.data-row', [
                        'label' => 'Placa',
                        'value' => $statement['vehicle']['plate'] ?: 'Não informada',
                    ])
                    @include('components.data-row', [
                        'label' => 'Dias da cota',
                        'value' => $statement['total'] !== null
                            ? $statement['total'].' '.($statement['total'] === 1 ? 'dia' : 'dias')
                            : 'Não informado',
                    ])
                    @include('components.data-row', [
                        'label' => 'Período da cota',
                        'value' => $statement['period']['start'] && $statement['period']['end']
                            ? $statement['period']['start'].' → '.$statement['period']['end']
                            : 'Não informado',
                    ])
                </dl>
            </div>

            {{-- Dias do mês --}}
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-1">Dias da cota</h2>
                <p class="mb-4 text-sm text-zinc-400">{{ $statement['month']['label'] }}</p>

                @forelse ($statement['weeks'] as $week)
                    <div class="mb-3 rounded-xl border border-line-dark bg-surface-850 p-4 last:mb-0">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ $week['ordinal'] }} semana</p>
                        <ul class="space-y-2">
                            @foreach ($week['days'] as $day)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5 text-sm">
                                    <span class="font-medium text-gray-100">{{ $day['day'] }} <span class="text-zinc-600">—</span> {{ $day['weekday'] }}</span>
                                    <span class="text-xs text-zinc-500">{{ $day['date'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">
                        @if ($statement['period']['start'])
                            Não há dias da cota em {{ $statement['month']['label'] }}.
                        @else
                            Este cadastro não possui período de cota registrado.
                        @endif
                    </p>
                @endforelse

                <p class="mt-4 border-t border-line-dark pt-4 text-sm text-zinc-300">
                    Total:
                    <span class="font-semibold text-white">
                        {{ $statement['total'] !== null
                            ? $statement['total'].' '.($statement['total'] === 1 ? 'dia' : 'dias')
                            : 'Não informado' }}
                    </span>
                    @if ($statement['daysInMonth'] > 0 && $statement['daysInMonth'] !== $statement['total'])
                        <span class="text-zinc-500">({{ $statement['daysInMonth'] }} em {{ $statement['month']['label'] }})</span>
                    @endif
                </p>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="card p-5">
                <h2 class="section-title mb-3">Resumo do mês</h2>
                <dl class="space-y-3 text-sm">
                    @include('components.data-row', ['label' => 'Mês', 'value' => $statement['month']['label']])
                    @include('components.data-row', ['label' => 'Semanas com dias da cota', 'value' => count($statement['weeks'])])
                    @include('components.data-row', [
                        'label' => 'Dias exibidos no mês',
                        'value' => $statement['daysInMonth'].' '.($statement['daysInMonth'] === 1 ? 'dia' : 'dias'),
                    ])
                </dl>
            </div>
        </aside>
    </div>
@endsection
