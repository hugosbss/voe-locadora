@extends('layouts.admin')

@section('title', 'Veículos')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Veículos</h1>
        </div>
        <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary">+ Novo veículo</a>
    </div>

    <div class="card p-4 sm:p-6">
        @if ($vehicles->isEmpty())
            <p class="text-sm text-zinc-400">Não há veículos disponíveis no momento.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm text-zinc-200">
                    <thead class="border-b border-line-dark text-xs uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-3 py-3">Veículo</th>
                        <th class="px-3 py-3">Placa</th>
                        <th class="px-3 py-3">Cotas</th>
                        <th class="px-3 py-3">Disponíveis</th>
                        <th class="px-3 py-3">Vendidas</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($vehicles as $vehicle)
                        <tr class="border-b border-line-dark/80 align-top">
                            <td class="px-3 py-3">
                                <div class="font-medium text-white">{{ $vehicle->model }}</div>
                            </td>
                            <td class="px-3 py-3">{{ $vehicle->plate }}</td>
                            <td class="px-3 py-3">{{ $vehicle->totalQuotaCount() }}</td>
                            <td class="px-3 py-3">{{ $vehicle->totalAvailableQuotaCount() }}</td>
                            <td class="px-3 py-3">{{ $vehicle->totalSoldQuotaCount() }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $vehicle->active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-zinc-700/60 text-zinc-300' }}">
                                    {{ $vehicle->active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="btn btn-secondary btn-sm">Visualizar</a>
                                    <form method="POST" action="{{ route('admin.vehicles.toggle-status', $vehicle) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">{{ $vehicle->active ? 'Inativar' : 'Ativar' }}</button>
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
