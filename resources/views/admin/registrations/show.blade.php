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
                    @include('components.data-row', ['label' => 'Data do cadastro', 'value' => $registration->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i')])
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

            {{-- Informações do veículo --}}
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-4">Informações do veículo</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-line-dark bg-surface-850 p-4">
                        <p class="text-xs uppercase tracking-wider text-zinc-500">Fotos da retirada</p>
                        @if ($registration->vehicle_pickup_photo_path)
                            <img src="{{ route('admin.registrations.photo', [$registration, 'vehicle_pickup']) }}" alt="Foto da retirada" class="mt-3 aspect-[4/3] w-full rounded-lg object-cover ring-1 ring-line-dark">
                        @else
                            <p class="mt-3 text-sm text-zinc-400">Não enviada</p>
                        @endif
                    </div>
                    <div class="rounded-xl border border-line-dark bg-surface-850 p-4">
                        <p class="text-xs uppercase tracking-wider text-zinc-500">Fotos da entrega</p>
                        @if ($registration->vehicle_delivery_photo_path)
                            <img src="{{ route('admin.registrations.photo', [$registration, 'vehicle_delivery']) }}" alt="Foto da entrega" class="mt-3 aspect-[4/3] w-full rounded-lg object-cover ring-1 ring-line-dark">
                        @else
                            <p class="mt-3 text-sm text-zinc-400">Não enviada</p>
                        @endif
                    </div>
                </div>
                <div class="mt-4 rounded-xl border border-line-dark bg-surface-850 p-4">
                    <p class="text-xs uppercase tracking-wider text-zinc-500">Obs</p>
                    <p class="mt-2 text-sm text-zinc-200">{{ $registration->vehicle_observation ?: 'Sem observação.' }}</p>
                </div>
            </div>

            {{-- Documentos --}}
            <div class="card p-5 sm:p-6">
                <h2 class="section-title mb-4">Fotos e documentos</h2>
                <div class="mx-auto grid w-full max-w-2xl grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach (App\Models\ClientRegistration::UPLOAD_DOCUMENTS as $doc => $label)
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

            {{-- <div class="card p-5">
                <h2 class="section-title mb-3">Validação facial</h2>
                <div class="flex items-center gap-2">
                    @include('components.status-badge', ['status' => $registration->facial_status])
                </div>
                <p class="mt-3 text-sm text-zinc-400">
                    {{ $registration->facial_status->label() }}.
                </p>            
            </div> --}}

            <div class="card p-5">
                <h2 class="section-title mb-3">Contrato</h2>

                @if ($registration->hasSignedContract())
                    <p class="mb-1 inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-500/30">
                        Contrato assinado
                    </p>

                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm">
                        @include('components.data-row', ['label' => 'Signatário', 'value' => $registration->contract_signer_name])
                        @include('components.data-row', ['label' => 'Versão', 'value' => $registration->contract_version])
                        @include('components.data-row', [
                            'label' => 'Data/hora',
                            'value' => $registration->contract_signed_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'),
                        ])
                    </dl>

                    @if ($registration->contract_signature_path)
                        <img
                            src="{{ route('admin.registrations.contract.signature', $registration) }}"
                            alt="Assinatura do cliente"
                            loading="lazy"
                            class="mt-4 h-24 w-full rounded-lg object-contain ring-1 ring-line-dark">
                    @endif

                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <a
                            href="{{ route('admin.registrations.contract', $registration) }}"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-secondary w-full">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            Visualizar contrato assinado
                        </a>
                        <a
                            href="{{ route('admin.registrations.contract.download', $registration) }}"
                            class="btn btn-primary w-full">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            Baixar contrato
                        </a>
                    </div>
                @else
                    <p class="inline-flex items-center gap-1.5 rounded-full bg-surface-800 px-2.5 py-1 text-xs font-semibold text-zinc-400 ring-1 ring-inset ring-line-dark">
                        Contrato não assinado
                    </p>
                    <p class="mt-3 text-sm text-zinc-400">
                        Este cadastro foi enviado sem contrato digital assinado.
                    </p>
                @endif
            </div>
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
