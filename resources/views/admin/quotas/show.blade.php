@extends('layouts.admin')

@section('title', 'Detalhe da cota')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.quotas.index') }}" class="back-link">Voltar</a>
            <h1 class="text-2xl font-semibold tracking-tight text-white">{{ $quotaType->code }} · {{ $quotaType->name }}</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.quotas.edit', $quotaType) }}" class="btn btn-secondary btn-sm">Editar</a>
            <form method="POST" action="{{ route('admin.quotas.toggle-status', $quotaType) }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">{{ $quotaType->active ? 'Inativar' : 'Ativar' }}</button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-5 sm:p-6 lg:col-span-2">
            <h2 class="section-title mb-4">Dados da cota</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Código</dt>
                    <dd class="mt-1 text-sm text-white">{{ $quotaType->code }}</dd>
                </div>
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Nome</dt>
                    <dd class="mt-1 text-sm text-white">{{ $quotaType->name }}</dd>
                </div>
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Dias</dt>
                    <dd class="mt-1 text-sm text-white">{{ $quotaType->days }}</dd>
                </div>
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Status</dt>
                    <dd class="mt-1 text-sm text-white">{{ $quotaType->active ? 'Ativo' : 'Inativo' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card p-5 sm:p-6">
            <h2 class="section-title mb-4">Resumo</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between"><dt class="text-zinc-400">Veículos vinculados</dt><dd class="font-semibold text-white">{{ $quotaType->configurations()->count() }}</dd></div>
                <div class="flex items-center justify-between"><dt class="text-zinc-400">Reservadas hoje</dt><dd class="font-semibold text-white">{{ collect($configurationStats)->sum('reserved_today') }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="mt-6 card p-5 sm:p-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="section-title">Veículos vinculados</h2>
            <form method="POST" action="{{ route('admin.quotas.add-vehicle', $quotaType) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <div data-custom-select data-cs-label="Veículo" data-cs-placeholder="Veículo" class="w-full sm:w-44">
                    <select name="vehicle_id" class="form-input" required>
                        <option value="">Veículo</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->model }} · {{ $vehicle->plate }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="number" name="quantity" min="0" value="1" class="form-input w-24 text-sm" required>
                <button type="submit" class="btn btn-primary btn-sm">+ Associar</button>
            </form>
        </div>

        @if ($quotaType->configurations->isEmpty())
            <p class="text-sm text-zinc-400">Não há veículos associados a este tipo de cota.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm text-zinc-200">
                    <thead class="border-b border-line-dark text-xs uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-3 py-3">Veículo</th>
                        <th class="px-3 py-3">Quantidade</th>
                        <th class="px-3 py-3">Reservadas hoje</th>
                        <th class="px-3 py-3">Disponíveis hoje</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($quotaType->configurations as $configuration)
                        <tr class="border-b border-line-dark/80">
                            <td class="px-3 py-3">{{ $configuration->vehicle->model }} · {{ $configuration->vehicle->plate }}</td>
                            <td class="px-3 py-3">{{ $configuration->quantity }}</td>
                            <td class="px-3 py-3">{{ $configurationStats[$configuration->id]['reserved_today'] ?? 0 }}</td>
                            <td class="px-3 py-3">{{ $configurationStats[$configuration->id]['available_today'] ?? 0 }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $configuration->active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-zinc-700/60 text-zinc-300' }}">
                                    {{ $configuration->active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.quotas.update-vehicle', [$quotaType, $configuration]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $configuration->quantity }}">
                                        <button type="submit" class="btn btn-secondary btn-sm">Atualizar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.quotas.remove-vehicle', [$quotaType, $configuration]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Remover</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
