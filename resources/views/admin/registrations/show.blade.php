@extends('layouts.admin')

@section('title', $registration->full_name . ' · Painel da Locadora')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.registrations.index') }}" class="back-link">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 17l-5-5m0 0 5-5m-5 5h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Voltar
        </a>
        @include('components.status-badge', ['status' => $registration->status, 'size' => 'md'])
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Dados do cliente --}}
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-4">Dados do cliente</h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    @include('components.data-row', ['label' => 'Nome completo', 'value' => $registration->full_name])
                    @include('components.data-row', ['label' => 'CPF', 'value' => $registration->maskedCpf()])
                    @include('components.data-row', ['label' => 'Data de nascimento', 'value' => $registration->birth_date->format('d/m/Y')])
                    @include('components.data-row', ['label' => 'Telefone', 'value' => $registration->phone])
                    @include('components.data-row', ['label' => 'WhatsApp', 'value' => $registration->whatsapp])
                    @include('components.data-row', ['label' => 'E-mail', 'value' => $registration->email])
                    @include('components.data-row', ['label' => 'Data do cadastro', 'value' => $registration->created_at->format('d/m/Y H:i')])
                </dl>
            </div>

            {{-- Endereço --}}
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-4">Endereço</h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    @include('components.data-row', ['label' => 'CEP', 'value' => $registration->cep])
                    @include('components.data-row', ['label' => 'Rua', 'value' => $registration->address])
                    @include('components.data-row', ['label' => 'Número', 'value' => $registration->address_number])
                    @include('components.data-row', ['label' => 'Bairro', 'value' => $registration->neighborhood])
                    @include('components.data-row', ['label' => 'Cidade', 'value' => $registration->city])
                    @include('components.data-row', ['label' => 'Estado', 'value' => $registration->state])
                </dl>
            </div>

            {{-- CNH --}}
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-4">Dados da CNH</h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                    @include('components.data-row', ['label' => 'Número da CNH', 'value' => $registration->cnh_number])
                    @include('components.data-row', ['label' => 'Categoria', 'value' => $registration->cnh_category])
                    @include('components.data-row', ['label' => 'Validade', 'value' => $registration->cnh_expiry_date->format('d/m/Y')])
                </dl>
            </div>

            {{-- Documentos --}}
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-1">Fotos e documentos</h2>
                <p class="mb-4 text-xs text-slate-400">Acesso restrito. Clique para ampliar.</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach (App\Models\ClientRegistration::DOCUMENTS as $doc => $label)
                        <div>
                            <p class="mb-2 text-xs font-medium text-slate-600">{{ $label }}</p>
                            @if ($registration->{$doc . '_path'})
                                <a href="{{ route('admin.registrations.photo', [$registration, $doc]) }}" target="_blank" rel="noopener">
                                    <img src="{{ route('admin.registrations.photo', [$registration, $doc]) }}"
                                        alt="{{ $label }}" loading="lazy"
                                        class="aspect-[3/4] w-full rounded-lg object-cover ring-1 ring-slate-200 transition-colors duration-150 hover:ring-2 hover:ring-indigo-400">
                                </a>
                            @else
                                <div class="flex aspect-[3/4] w-full items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">
                                    Não enviado
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Status --}}
        <aside class="space-y-6">
            <div class="card p-5">
                <h2 class="section-title mb-4">Status do cadastro</h2>

                <form method="POST" id="status-form" action="{{ route('admin.registrations.status', $registration) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-1.5">
                        <label for="status" class="form-label">Alterar status</label>
                        <div data-custom-select data-cs-label="Alterar status" data-cs-placeholder="Selecione...">
                            <select name="status" id="status" class="form-input">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($registration->status === $status)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Salvar status
                    </button>
                </form>
            </div>

            <div class="card p-5">
                <h2 class="section-title mb-3">Validação facial</h2>
                <div class="flex items-center gap-2">
                    @include('components.status-badge', ['status' => $registration->facial_status])
                </div>
                <p class="mt-3 text-sm text-slate-600">
                    {{ $registration->facial_status->label() }}.
                </p>
                <p class="mt-2 text-xs text-slate-400">
                    A comparação automática entre selfie e CNH estará disponível em integração futura.
                </p>
            </div>
        </aside>
    </div>
@endsection