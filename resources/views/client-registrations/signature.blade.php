@extends('layouts.public')

@section('title', 'Assinar contrato')

@section('content')
    <div class="mx-auto w-full max-w-2xl">
        <div class="card panel-card p-5 sm:p-8">
            <header class="mb-6">
                <p class="mt-1 text-sm text-zinc-400">
                    Confira seus dados, leia o contrato e assine.
                </p>
            </header>

            <div class="rounded-xl bg-surface-850 p-4 text-sm ring-1 ring-inset ring-line-dark">
                <p class="mb-1 font-medium text-zinc-400">Seus dados</p>
                <p class="text-gray-300">
                    Confira as informações abaixo. Este formulário não altera o seu cadastro.
                </p>

                <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    @include('components.data-row', ['label' => 'Nome', 'value' => $registration->full_name])
                    @include('components.data-row', ['label' => 'CPF', 'value' => $registration->partiallyMaskedCpf()])
                    @include('components.data-row', ['label' => 'CNH', 'value' => $registration->partiallyMaskedCnh()])
                    @include('components.data-row', [
                        'label' => 'Período',
                        'value' => $registration->start_date
                            ? $registration->start_date->format('d/m/Y').' a '.($registration->end_date?->format('d/m/Y') ?? '—')
                            : '—',
                    ])
                    @if ($registration->quotaType)
                        @include('components.data-row', [
                            'label' => 'Cota',
                            'value' => $registration->quotaType->code.' · '.$registration->quotaType->name,
                        ])
                    @endif
                    @if ($registration->vehicle)
                        @include('components.data-row', ['label' => 'Veículo', 'value' => $registration->vehicle->model])
                    @endif
                </dl>

                <a
                    href="{{ $contractUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-line-dark bg-surface-800 px-4 py-2 text-sm font-semibold text-white transition hover:border-brand hover:text-brand sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    Visualizar o contrato completo
                </a>
            </div>

            <form
                class="mt-6 space-y-5"
                method="POST"
                action="{{ route('client-registrations.signature.store', $registration) }}"
                data-signature-form
                novalidate>
                @csrf

                <div>
                    <p class="form-label">Signatário</p>
                    <p class="form-input cursor-not-allowed opacity-70">{{ $registration->full_name }}</p>
                    <p class="field-hint">O signatário é sempre o titular do cadastro.</p>
                </div>

                <div>
                    <p class="form-label">Sua assinatura</p>
                    <div
                        id="signature-canvas-wrap"
                        class="relative overflow-hidden rounded-xl bg-white ring-1 ring-inset ring-line-dark @error('contract_signature') ring-red-400 @enderror"
                        style="touch-action: none;">
                        <canvas
                            id="signature-canvas"
                            class="block w-full"
                            height="200"
                            aria-label="Área para desenhar sua assinatura"
                            role="img"></canvas>
                        <p id="signature-hint"
                            class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm font-medium text-zinc-400">
                            Desenhe sua assinatura aqui
                        </p>
                        <button
                            type="button"
                            id="signature-clear"
                            class="absolute end-2 top-2 rounded-md border border-line-dark bg-white px-2 py-1 text-xs font-semibold text-zinc-600 transition hover:border-red-300 hover:text-red-500">
                            Limpar
                        </button>
                    </div>
                    <input type="hidden" id="contract_signature" name="contract_signature" value="">
                    <p class="field-hint">Use o dedo ou o mouse para desenhar sua assinatura.</p>
                    <p id="contract_signature-error" class="field-error" role="alert"
                        @unless ($errors->has('contract_signature')) hidden @endunless>{{ $errors->first('contract_signature') }}</p>
                </div>

                <div class="rounded-xl border border-line-dark p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" id="contract_accepted" name="contract_accepted" value="1"
                            @checked(old('contract_accepted'))
                            class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-muted accent-brand focus:ring-2 focus:ring-brand focus:ring-offset-0" required>
                        <span class="text-sm text-zinc-300">Declaro que li o Contrato do Clube de Mobilidade da VCA e aceito seus termos.</span>
                    </label>
                    <p id="contract_accepted-error" class="field-error" role="alert"
                        @unless ($errors->has('contract_accepted')) hidden @endunless>{{ $errors->first('contract_accepted') }}</p>
                </div>

                <div class="flex justify-center">
                    <x-button type="submit" size="lg" class="w-full sm:min-w-64 sm:w-auto" data-signature-submit disabled>
                        <svg data-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"
                            aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"/></svg>
                        <span data-submit-label>Assinar contrato</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
