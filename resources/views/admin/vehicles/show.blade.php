@extends('layouts.admin')

@section('title', 'Detalhe do veículo')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.vehicles.index') }}" class="back-link">Voltar</a>
            <h1 class="text-2xl font-semibold tracking-tight text-white">{{ $vehicle->model }}</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-secondary btn-sm">Editar</a>
            <form method="POST" action="{{ route('admin.vehicles.toggle-status', $vehicle) }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">{{ $vehicle->active ? 'Inativar' : 'Ativar' }}</button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-5 sm:p-6 lg:col-span-2">
            <h2 class="section-title mb-4">Dados do veículo</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Modelo</dt>
                    <dd class="mt-1 text-sm text-white">{{ $vehicle->model }}</dd>
                </div>
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Placa</dt>
                    <dd class="mt-1 text-sm text-white">{{ $vehicle->plate }}</dd>
                </div>
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Status</dt>
                    <dd class="mt-1 text-sm text-white">{{ $vehicle->active ? 'Ativo' : 'Inativo' }}</dd>
                </div>
                <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Total de cotas</dt>
                    <dd class="mt-1 text-sm text-white">{{ $vehicle->totalQuotaCount() }}</dd>
                </div>
            </dl>
        </div>

        <div class="card p-5 sm:p-6">
            <h2 class="section-title mb-4">Resumo de hoje</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between"><dt class="text-zinc-400">Total de cotas</dt><dd class="font-semibold text-white">{{ $vehicleStats['total'] }}</dd></div>
                <div class="flex items-center justify-between"><dt class="text-zinc-400">Reservadas hoje</dt><dd class="font-semibold text-white">{{ $vehicleStats['reserved_today'] }}</dd></div>
                <div class="flex items-center justify-between"><dt class="text-zinc-400">Disponíveis hoje</dt><dd class="font-semibold text-white">{{ $vehicleStats['available_today'] }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="mt-6 card p-5 sm:p-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="section-title">Cotas</h2>
            <form method="POST" action="{{ route('admin.vehicles.add-quota', $vehicle) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <div data-custom-select data-cs-label="Tipo de cota" data-cs-placeholder="Tipo" class="w-full sm:w-44">
                    <select name="quota_type_id" class="form-input" required>
                        <option value="">Tipo</option>
                        @foreach ($quotaTypes as $quotaType)
                            <option value="{{ $quotaType->id }}">{{ $quotaType->code }} - {{ $quotaType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="number" name="quantity" min="0" value="1" class="form-input w-24 text-sm" required>
                <button type="submit" class="btn btn-primary btn-sm">+ Adicionar</button>
            </form>
        </div>

        @if ($vehicle->quotaConfigurations->isEmpty())
            <p class="text-sm text-zinc-400">Não há cotas configuradas para este veículo.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm text-zinc-200">
                    <thead class="border-b border-line-dark text-xs uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-3 py-3">Tipo</th>
                        <th class="px-3 py-3">Nome</th>
                        <th class="px-3 py-3">Quantidade</th>
                        <th class="px-3 py-3">Dias</th>
                        <th class="px-3 py-3">Reservadas hoje</th>
                        <th class="px-3 py-3">Disponíveis hoje</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($vehicle->quotaConfigurations as $configuration)
                        <tr class="border-b border-line-dark/80">
                            <td class="px-3 py-3">{{ $configuration->quotaType->code }}</td>
                            <td class="px-3 py-3">{{ $configuration->quotaType->name }}</td>
                            <td class="px-3 py-3">{{ $configuration->quantity }}</td>
                            <td class="px-3 py-3">{{ $configuration->quotaType->days }}</td>
                            <td class="px-3 py-3">{{ $configurationStats[$configuration->id]['reserved_today'] ?? 0 }}</td>
                            <td class="px-3 py-3">{{ $configurationStats[$configuration->id]['available_today'] ?? 0 }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $configuration->active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-zinc-700/60 text-zinc-300' }}">
                                    {{ $configuration->active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.vehicles.update-quota', [$vehicle, $configuration]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $configuration->quantity }}">
                                        <button type="submit" class="btn btn-secondary btn-sm">Atualizar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.vehicles.remove-quota', [$vehicle, $configuration]) }}">
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
