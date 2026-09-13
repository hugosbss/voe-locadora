@extends('layouts.admin')

@section('title', 'Cadastro')

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
                <h2 class="section-title mb-4">Fotos e documentos</h2>
                <div class="mx-auto grid w-full max-w-2xl grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach (App\Models\ClientRegistration::DOCUMENTS as $doc => $label)
                        <div class="min-w-0">
                            <div class="mb-2 flex min-h-[2.5rem] items-start">
                                <p class="text-xs font-medium leading-snug text-zinc-500">{{ $label }}</p>
                            </div>

                            @if ($registration->{$doc . '_path'})
                                <a
                                    href="{{ route('admin.registrations.photo', [$registration, $doc]) }}"
                                    data-fancybox="cadastro-docs"
                                    data-type="image"
                                    data-caption="{{ $label }}"
                                    aria-label="Ampliar {{ $label }}"
                                    class="group relative block cursor-zoom-in focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    <img
                                        src="{{ route('admin.registrations.photo', [$registration, $doc]) }}"
                                        alt="{{ $label }}"
                                        loading="lazy"
                                        class="aspect-[3/4] w-full rounded-lg object-cover ring-1 ring-line-dark transition-[box-shadow,ring] duration-150 group-hover:ring-2 group-hover:ring-brand group-focus-visible:ring-2 group-focus-visible:ring-brand">
                                    <span class="pointer-events-none absolute inset-0 flex items-end justify-center rounded-lg pb-2 opacity-0 transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-black/70 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" /></svg>
                                            Ampliar
                                        </span>
                                    </span>
                                </a>
                            @else
                                <div class="flex aspect-[3/4] w-full items-center justify-center rounded-lg bg-surface-800 text-xs text-zinc-600">
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
                <h2 class="section-title mb-4">Status</h2>

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
                        Salvar
                    </button>
                </form>
            </div>

            <div class="card p-5">
                <h2 class="section-title mb-3">Validação facial</h2>
                <div class="flex items-center gap-2">
                    @include('components.status-badge', ['status' => $registration->facial_status])
                </div>
                <p class="mt-3 text-sm text-zinc-400">
                    {{ $registration->facial_status->label() }}.
                </p>            </div>
        </aside>
    </div>

    <x-confirm-dialog
        id="status-reject-dialog"
        variant="danger"
        title="Reprovar este cadastro?"
        message="O cadastro deixará de aparecer como novo. Você pode alterar o status novamente depois."
        confirmLabel="Reprovar cadastro"
        cancelLabel="Cancelar"
        confirm-target="#status-form"
        confirm-when="reprovado" />
@endsection