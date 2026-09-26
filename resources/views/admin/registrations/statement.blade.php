@extends('layouts.admin')

@section('title', 'Extrato do cliente')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.registrations.show', $registration) }}" class="back-link">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 17l-5-5m0 0 5-5m-5 5h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Voltar
            </a>
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">Extrato do cliente</h1>
                <p class="mt-0.5 text-sm text-zinc-500">{{ $statement['client']['name'] }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('admin.registrations.show', $registration) }}" class="btn btn-secondary btn-sm">Ver cadastro</a>
            <a href="{{ route('admin.registrations.edit', $registration) }}" class="btn btn-secondary btn-sm">Editar cadastro</a>
        </div>
    </div>

    {{-- Cliente --}}
    <div class="card mb-6 p-5 sm:p-6">
        <h2 class="section-title mb-4">Cliente</h2>
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
            @include('components.data-row', ['label' => 'Nome completo', 'value' => $statement['client']['name']])
            @include('components.data-row', ['label' => 'CPF', 'value' => $statement['client']['cpf']])
            @include('components.data-row', [
                'label' => $statement['reference']['label'],
                'value' => $statement['reference']['date'],
            ])
        </dl>
    </div>

    {{-- Cota e veículo --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card p-5 sm:p-6">
            <h2 class="section-title mb-4">Cota</h2>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                @include('components.data-row', ['label' => 'Cota', 'value' => $statement['quota']['code'] ?: 'Não informada'])
                @include('components.data-row', ['label' => 'Nome da cota', 'value' => $statement['quota']['name'] ?: 'Não informado'])
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

        <div class="card p-5 sm:p-6">
            <h2 class="section-title mb-4">Veículo</h2>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                @include('components.data-row', ['label' => 'Veículo', 'value' => $statement['vehicle']['model'] ?: 'Não informado'])
                @include('components.data-row', ['label' => 'Placa', 'value' => $statement['vehicle']['plate'] ?: 'Não informada'])
            </dl>
        </div>
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
                    Mês anterior
                </a>
                <a
                    href="{{ route('admin.registrations.statement', [$registration, 'mes' => $statement['month']['next']]) }}"
                    class="btn btn-ghost btn-sm"
                    aria-label="Próximo mês">
                    Próximo mês
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
        </form>
    </div>

    {{-- Dias do mês --}}
    <div class="card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="section-title">Dias da cota</h2>
            <p class="text-sm text-zinc-400">{{ $statement['month']['label'] }}</p>
        </div>

        @forelse ($statement['weeks'] as $week)
            <div class="mb-3 rounded-xl border border-line-dark bg-surface-850 p-4 last:mb-0">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ $week['ordinal'] }} semana</p>
                <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($week['days'] as $day)
                        <li class="flex items-baseline justify-between gap-x-3 rounded-lg bg-surface-900/60 px-3 py-2 text-sm">
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

    {{-- Histórico de extratos do cliente: navegação nunca sai deste cliente --}}
    <nav class="card mt-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" aria-label="Histórico de extratos do cliente">
        <div class="sm:w-1/3">
            @if ($history['previous'])
                <a
                    href="{{ route('admin.registrations.statement', $history['previous']['registration']) }}"
                    data-statement-nav="previous"
                    class="btn btn-secondary btn-sm w-full sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    Anterior
                </a>
                <p class="mt-1 hidden text-xs text-zinc-500 sm:block">{{ $history['previous']['reference'] }}</p>
            @endif
        </div>

        <p class="order-first text-center text-xs text-zinc-500 sm:order-none">
            Extrato {{ $history['position'] }} de {{ $history['total'] }}
        </p>

        <div class="text-right sm:w-1/3">
            @if ($history['next'])
                <a
                    href="{{ route('admin.registrations.statement', $history['next']['registration']) }}"
                    data-statement-nav="next"
                    class="btn btn-secondary btn-sm w-full sm:ml-auto sm:w-auto">
                    Próximo
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
                <p class="mt-1 hidden text-xs text-zinc-500 sm:block">{{ $history['next']['reference'] }}</p>
            @endif
        </div>
    </nav>
@endsection
