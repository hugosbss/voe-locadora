@extends('layouts.admin')

@section('title', 'Novo veículo')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Novo veículo</h1>
        </div>
        <a href="{{ route('admin.vehicles.index') }}" class="back-link">Voltar</a>
    </div>

    <div class="card p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.vehicles.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-form-field label="Modelo" name="model" value="{{ old('model') }}" placeholder="Ex.: Chevrolet Onix" required />
                <x-form-field label="Placa" name="plate" value="{{ old('plate') }}" required />
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="active" value="1" id="active" @checked(old('active', true)) class="h-4 w-4 rounded border-line-dark bg-surface-800 text-brand" />
                <label for="active" class="text-sm text-zinc-300">Ativo</label>
            </div>

            <div class="rounded-xl border border-line-dark bg-surface-850 p-4">
                <p class="mb-3 text-sm font-medium text-white">Configuração inicial</p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-select-field label="Tipo de cota" name="quota_type_id" placeholder="Selecione...">
                        @foreach ($quotaTypes as $quotaType)
                            <option value="{{ $quotaType->id }}" @selected(old('quota_type_id') == $quotaType->id)>{{ $quotaType->code }} - {{ $quotaType->name }}</option>
                        @endforeach
                    </x-select-field>
                    <x-form-field label="Quantidade" name="quota_quantity" type="number" min="0" value="{{ old('quota_quantity', 0) }}" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar veículo</button>
        </form>
    </div>
@endsection
