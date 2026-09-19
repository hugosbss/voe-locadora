@extends('layouts.admin')

@section('title', 'Cotas')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Cotas</h1>
        </div>
        <a href="{{ route('admin.quotas.create') }}" class="btn btn-primary">+ Nova cota</a>
    </div>

    <div class="card p-5 sm:p-6">
        @if ($quotaTypes->isEmpty())
            <p class="text-sm text-zinc-400">Não há tipos de cota cadastrados.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm text-zinc-200">
                    <thead class="border-b border-line-dark text-xs uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-3 py-3">Código</th>
                        <th class="px-3 py-3">Nome</th>
                        <th class="px-3 py-3">Dias</th>
                        <th class="px-3 py-3">Veículos</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($quotaTypes as $quotaType)
                        <tr class="border-b border-line-dark/80">
                            <td class="px-3 py-3 font-medium text-white">{{ $quotaType->code }}</td>
                            <td class="px-3 py-3">{{ $quotaType->name }}</td>
                            <td class="px-3 py-3">{{ $quotaType->days }}</td>
                            <td class="px-3 py-3">{{ $quotaType->configurations()->count() }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $quotaType->active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-zinc-700/60 text-zinc-300' }}">
                                    {{ $quotaType->active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.quotas.show', $quotaType) }}" class="btn btn-secondary btn-sm">Visualizar</a>
                                    <form method="POST" action="{{ route('admin.quotas.toggle-status', $quotaType) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">{{ $quotaType->active ? 'Inativar' : 'Ativar' }}</button>
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
