@extends('layouts.admin')

@section('title', 'Editar cota')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Editar cota</h1>
        </div>
        <a href="{{ route('admin.quotas.show', $quotaType) }}" class="back-link">Voltar</a>
    </div>

    <div class="card p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.quotas.update', $quotaType) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-form-field label="Código" name="code" value="{{ old('code', $quotaType->code) }}" placeholder="Cota F" required />
                <x-form-field label="Nome" name="name" value="{{ old('name', $quotaType->name) }}" placeholder="Família" required />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-form-field label="Dias" name="days" type="number" min="1" value="{{ old('days', $quotaType->days) }}" required />
                <div class="flex items-center gap-3 pt-7">
                    <input type="checkbox" name="active" value="1" id="active" @checked(old('active', $quotaType->active)) class="h-4 w-4 rounded border-line-dark bg-surface-800 text-brand" />
                    <label for="active" class="text-sm text-zinc-300">Ativo</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </form>
    </div>
@endsection
