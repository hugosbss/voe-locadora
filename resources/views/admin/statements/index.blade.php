@extends('layouts.admin')

@section('title', 'Extratos')

@section('content')
    <div class="mb-6 rounded-2xl bg-surface-900 p-4 ring-1 ring-line-dark">
        <form method="GET" action="{{ route('admin.statements.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:min-w-44 sm:flex-1">
                <label class="form-label mb-1.5" for="statement-search">Buscar por nome ou CPF</label>
                <input type="search" name="q" id="statement-search"
                    value="{{ $currentSearch }}"
                    placeholder="Buscar por nome ou CPF"
                    autocomplete="off"
                    class="form-input w-full">
            </div>

            <div class="flex w-full items-center gap-2 sm:w-auto">
                <x-button type="submit" class="flex-1 sm:flex-none">Filtrar</x-button>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.statements.index') }}" class="text-xs font-medium text-brand hover:underline">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    @if ($statements->isEmpty())
        <div class="card p-10 text-center text-zinc-400">
            @if ($hasActiveFilters)
                Nenhum extrato encontrado para os filtros aplicados.
            @else
                Nenhum cadastro aprovado com extrato disponível.
            @endif
        </div>
    @else
        {{-- Cards (mobile) --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($statements as $statement)
                <a href="{{ route('admin.registrations.statement', $statement['uuid']) }}" class="admin-row-card">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-gray-100">{{ $statement['client']['name'] }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $statement['client']['cpf'] }}</p>
                        <p class="mt-0.5 text-xs text-zinc-400">
                            {{ $statement['quota']['code'] ?: 'Cota não informada' }}
                            &middot; {{ $statement['vehicle']['model'] ?: 'Veículo não informado' }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-600">
                            {{ $statement['reference']['label'] }}: {{ $statement['reference']['date'] }}
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        <span class="badge bg-brand/15 text-brand ring-brand/30">
                            {{ $statement['total'] !== null
                                ? $statement['total'].' '.($statement['total'] === 1 ? 'dia' : 'dias')
                                : 'Dias n/i' }}
                        </span>
                        <span class="text-xs font-medium text-brand">Visualizar extrato</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Tabela (desktop) --}}
        <div class="card hidden overflow-hidden sm:block">
            <table class="data-table w-full text-sm">
                <thead class="bg-surface-850">
                    <tr>
                        <th>Cliente</th>
                        <th>Cota</th>
                        <th class="hidden lg:table-cell">Veículo</th>
                        <th>Dias</th>
                        <th>Referência</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statements as $statement)
                        <tr>
                            <td>
                                <p class="font-medium text-gray-100">{{ $statement['client']['name'] }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $statement['client']['cpf'] }}</p>
                            </td>
                            <td>
                                <p class="text-gray-100">{{ $statement['quota']['code'] ?: 'Não informada' }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $statement['quota']['name'] ?: 'Nome não informado' }}</p>
                            </td>
                            <td class="hidden lg:table-cell">
                                <p class="text-gray-100">{{ $statement['vehicle']['model'] ?: 'Não informado' }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $statement['vehicle']['plate'] ?: 'Placa não informada' }}</p>
                            </td>
                            <td class="text-gray-100">
                                {{ $statement['total'] !== null
                                    ? $statement['total'].' '.($statement['total'] === 1 ? 'dia' : 'dias')
                                    : 'Não informado' }}
                            </td>
                            <td>
                                <p class="text-gray-100">{{ $statement['reference']['date'] }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $statement['reference']['label'] }}</p>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.registrations.statement', $statement['uuid']) }}"
                                    class="text-sm font-medium text-brand hover:text-brand-soft">
                                    Visualizar extrato
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $statements->links() }}
        </div>
    @endif
@endsection
