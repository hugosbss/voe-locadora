@extends('layouts.admin')

@section('title', 'Editar informações do veículo')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.registrations.show', $registration) }}" class="back-link">Voltar ao cadastro</a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-white">Editar informações do veículo</h1>
        </div>
        @include('components.status-badge', ['status' => $registration->status, 'size' => 'md'])
    </div>

    <form method="POST" action="{{ route('admin.registrations.update', $registration) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="card p-5 sm:p-6">
            <h2 class="section-title mb-4">Informações do veículo</h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                @foreach ([
                    'vehicle_pickup_photo' => ['label' => 'Foto da retirada', 'document' => 'vehicle_pickup', 'path' => $registration->vehicle_pickup_photo_path],
                    'vehicle_delivery_photo' => ['label' => 'Foto da entrega', 'document' => 'vehicle_delivery', 'path' => $registration->vehicle_delivery_photo_path],
                ] as $field => $photo)
                    <div
                        @class(['upload-card', 'is-filled' => $photo['path'], 'has-error' => $errors->has($field)])
                        data-admin-upload-card
                        data-existing-src="{{ $photo['path'] ? route('admin.registrations.photo', [$registration, $photo['document']]) : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 pt-2">
                                <p class="text-sm font-medium text-gray-100">{{ $photo['label'] }}</p>
                                <p class="text-xs text-zinc-500">{{ $photo['path'] ? 'Foto atual' : 'Nenhuma imagem selecionada' }}</p>
                                <p class="file-name mt-1 max-w-[12rem] truncate text-xs font-medium text-brand">{{ $photo['path'] ? 'Foto salva' : 'Nenhuma imagem selecionada' }}</p>
                            </div>

                            @include('components.document-preview', [
                                'doc' => $photo['document'],
                                'src' => $photo['path'] ? route('admin.registrations.photo', [$registration, $photo['document']]) : null,
                                'alt' => $photo['label'],
                            ])
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <label class="upload-btn cursor-pointer">
                                <input type="file" name="{{ $field }}" id="{{ $field }}" accept="image/*" capture="environment" class="document-input sr-only" data-admin-upload-input>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h4l1.5-2H15l1.5 2H20v12H4V6Zm8 10a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 16Z"/></svg>
                                {{ $photo['path'] ? 'Alterar foto' : 'Tirar foto' }}
                            </label>
                            <label class="upload-btn cursor-pointer">
                                <input type="file" accept="image/*" class="gallery-input sr-only" data-admin-gallery-input>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5a3 3 0 1 0 0 6 3.5 3.5 0 0 0 0-6ZM16 16v-2H8v2h8Zm2-1H22A10 10 0 0 1 2 15h6v-3h4.5a3.5 3.5 0 0 1 6.3-2L22 8v2Z"/></svg>
                                Galeria
                            </label>
                        </div>

                        <p class="field-hint">JPG, PNG ou WEBP, até 5 MB.</p>
                        @error($field)<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-4 space-y-1.5">
                <label for="vehicle_observation" class="form-label">Observação do veículo</label>
                <textarea name="vehicle_observation" id="vehicle_observation" rows="4" class="form-input">{{ old('vehicle_observation', $registration->vehicle_observation) }}</textarea>
                @error('vehicle_observation')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.registrations.show', $registration) }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
    </form>
@endsection
