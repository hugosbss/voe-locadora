@extends('layouts.admin')

@section('title', 'Editar veículo')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Editar veículo</h1>
        </div>
        <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="back-link">Voltar</a>
    </div>

    <div class="card p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-form-field label="Modelo" name="model" value="{{ old('model', $vehicle->model) }}" placeholder="Ex.: Chevrolet Onix" required />
                <x-form-field label="Placa" name="plate" value="{{ old('plate', $vehicle->plate) }}" placeholder="TXD6J56" required />
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="active" value="1" id="active" @checked(old('active', $vehicle->active)) class="h-4 w-4 rounded border-line-dark bg-surface-800 text-brand" />
                <label for="active" class="text-sm text-zinc-300">Ativo</label>
            </div>

            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </form>
    </div>
@endsection
