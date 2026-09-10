@extends('layouts.admin')

@section('title', 'Cadastros · Painel da Locadora')

@section('content')
    @php
        $currentStatusLabel = collect($statuses)->first(fn ($status) => $status->value === $currentStatus)?->label();
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Cadastros</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $registrations->total() }} {{ $registrations->total() === 1 ? 'cadastro' : 'cadastros' }}
                @if ($currentStatus)
                    &middot; filtrando por <span class="font-medium text-slate-700">{{ $currentStatusLabel ?? $currentStatus }}</span>
                @endif
            </p>
        </div>

        <form method="GET" action="{{ route('admin.registrations.index') }}" class="flex items-center gap-2">
            @if ($currentStatus)
                <a href="{{ route('admin.registrations.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Limpar</a>
            @endif
            <label class="sr-only" for="status-filter">Filtrar por status</label>
            <div data-custom-select data-cs-label="Filtrar por status" data-cs-placeholder="Todos os status" class="w-48">
                <select name="status" id="status-filter" class="form-input">
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($registrations->isEmpty())
        <div class="card p-10 text-center text-slate-500">
            @if ($currentStatus)
                Nenhum cadastro com este status.
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
                        <p class="truncate font-medium text-slate-900">{{ $registration->full_name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $registration->maskedCpf() }} &middot; {{ $registration->phone }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Cadastrado em {{ $registration->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        @include('components.status-badge', ['status' => $registration->status])
                        <span class="text-xs font-medium text-indigo-600">Ver detalhes</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Tabela (desktop) --}}
        <div class="card hidden overflow-hidden sm:block">
            <table class="data-table w-full text-sm">
                <thead class="bg-slate-50">
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
                            <td class="font-medium text-slate-900">{{ $registration->full_name }}</td>
                            <td class="text-slate-600">{{ $registration->maskedCpf() }}</td>
                            <td class="text-slate-600 hidden md:table-cell">{{ $registration->phone }}</td>
                            <td class="text-slate-600 hidden lg:table-cell">
                                {{ $registration->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                @include('components.status-badge', ['status' => $registration->status])
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.registrations.show', $registration) }}"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
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