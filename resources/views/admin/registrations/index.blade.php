@extends('layouts.admin')

@section('title', 'Cadastros')

@section('content')
    @php
        $currentStatusLabel = collect($statuses)->first(fn ($status) => $status->value === $currentStatus)?->label();
    @endphp

    <div class="mb-6 rounded-2xl bg-surface-900 p-4 ring-1 ring-line-dark">
        <form method="GET" action="{{ route('admin.registrations.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-auto">
                <label class="form-label mb-1.5" for="status-filter">Status</label>
                <div data-custom-select data-cs-label="Filtrar por status" data-cs-placeholder="Todos os status" class="w-full sm:w-44">
                    <select name="status" id="status-filter" class="form-input">
                        <option value="">Todos os status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="w-full sm:w-auto">
                <label class="form-label mb-1.5" for="date-from-filter">Exibir a partir de</label>
                <input type="date" name="date_from" id="date-from-filter"
                    value="{{ $currentDateFrom }}"
                    class="form-input w-full sm:w-44">
            </div>

            <div class="w-full sm:min-w-44 sm:flex-1">
                <label class="form-label mb-1.5" for="name-filter">Buscar por nome</label>
                <input type="search" name="name" id="name-filter"
                    value="{{ $currentName }}"
                    placeholder="Buscar por nome"
                    autocomplete="off"
                    class="form-input w-full">
            </div>

            <div class="flex w-full items-center gap-2 sm:w-auto">
                <x-button type="submit" class="flex-1 sm:flex-none">Filtrar</x-button>
                @if ($hasActiveFilters)
                    <a href="{{ route('admin.registrations.index') }}" class="text-xs font-medium text-brand hover:underline">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    @if ($registrations->isEmpty())
        <div class="card p-10 text-center text-zinc-400">
            @if ($hasActiveFilters)
                Nenhum cadastro encontrado para os filtros aplicados.
            @else
                Nenhum cadastro encontrado.
            @endif
        </div>
    @else
        {{-- Cards (mobile) --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($registrations as $registration)
                <a href="{{ route('admin.registrations.show', $registration) }}" class="admin-row-card">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-gray-100">{{ $registration->full_name }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{ $registration->maskedCpf() }} &middot; {{ $registration->phone }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-600">
                            Cadastrado em {{ $registration->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        @include('components.status-badge', ['status' => $registration->status])
                        <span class="text-xs font-medium text-brand">Ver detalhes</span>
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
                        <th>CPF</th>
                        <th class="hidden md:table-cell">Telefone</th>
                        <th class="hidden lg:table-cell">Data do cadastro</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registrations as $registration)
                        <tr>
                            <td class="font-medium text-gray-100">{{ $registration->full_name }}</td>
                            <td class="text-zinc-400">{{ $registration->maskedCpf() }}</td>
                            <td class="text-zinc-400 hidden md:table-cell">{{ $registration->phone }}</td>
                            <td class="text-zinc-400 hidden lg:table-cell">
                                {{ $registration->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                @include('components.status-badge', ['status' => $registration->status])
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.registrations.show', $registration) }}"
                                    class="text-sm font-medium text-brand hover:text-brand-soft">
                                    Ver detalhes
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $registrations->links() }}
        </div>
    @endif
@endsection
