@extends('layouts.admin')

@section('title', 'Nova cota')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Nova cota</h1>
        </div>
        <a href="{{ route('admin.quotas.index') }}" class="back-link">Voltar</a>
    </div>

    <div class="card p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.quotas.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-form-field label="Código" name="code" value="{{ old('code') }}" placeholder="S" required />
                <x-form-field label="Nome" name="name" value="{{ old('name') }}" placeholder="Semanal" required />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-form-field label="Dias" name="days" type="number" min="1" value="{{ old('days') }}" required />
                <div class="flex items-center gap-3 pt-7">
                    <input type="checkbox" name="active" value="1" id="active" @checked(old('active', true)) class="h-4 w-4 rounded border-line-dark bg-surface-800 text-brand" />
                    <label for="active" class="text-sm text-zinc-300">Ativo</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar cota</button>
        </form>
    </div>
@endsection
