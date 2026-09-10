@extends('layouts.public')

@section('title', 'Cadastro de Cliente')

@section('content')
    <div class="card p-5 sm:p-8">
        <header class="mb-6">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Cadastro de Cliente</h1>
        </header>

        {{-- Barra de progresso (mobile) --}}
        <div class="mb-6 sm:hidden">
            <div class="flex items-center justify-between">
                <p id="step-count" class="text-xs font-semibold text-indigo-600" aria-live="polite">Etapa 1 de 6</p>
            </div>
            <div
                id="step-progress"
                class="relative mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200"
                role="progressbar"
                aria-valuemin="1"
                aria-valuemax="6"
                aria-valuenow="1"
                aria-label="Progresso do cadastro">
                <div
                    id="stepbar-fill"
                    class="absolute inset-y-0 left-0 rounded-full bg-indigo-600 transition-[width] duration-300 ease-out"
                    style="width: 16.666%"></div>
            </div>
            <h1 id="step-title" class="mt-3 text-lg font-semibold tracking-tight text-slate-900">Dados pessoais</h1>
        </div>

        {{-- Indicador de etapas (desktop) --}}
        <ol
            id="step-indicator"
            class="mb-8 hidden items-stretch gap-4 sm:flex"
            aria-label="Progresso do cadastro">
            @foreach ($steps as $index => $step)
                <li class="flex-1">
                    <div class="flex flex-col items-center gap-1.5">
                        <span
                            class="step-dot"
                            data-step="{{ $index + 1 }}"
                            data-active="false">{{ $index + 1 }}</span>
                        <span class="step-label" data-step="{{ $index + 1 }}" data-active="false">{{ $step }}</span>
                    </div>
                </li>
            @endforeach
        </ol>

        <form
            id="registration-form"
            method="POST"
            action="{{ route('client-registrations.store') }}"
            enctype="multipart/form-data"
            novalidate>
            @csrf

            {{-- Etapa 1: Dados pessoais --}}
            <section class="step-panel space-y-5" data-panel="1" data-title="Dados pessoais">
                <h2 class="section-title mb-4 hidden sm:block">1. Dados pessoais</h2>

                <x-form-field label="Nome completo" name="full_name" value="{{ old('full_name') }}"
                    placeholder="Digite seu nome completo" autocomplete="name" required />

                <x-form-field label="CPF" name="cpf" value="{{ old('cpf') }}"
                    placeholder="000.000.000-00" inputmode="numeric" data-mask="cpf" autocomplete="off" required />

                <x-form-field label="Data de nascimento" name="birth_date" value="{{ old('birth_date') }}"
                    type="date" max="{{ \Carbon\Carbon::now()->subYears(18)->format('Y-m-d') }}" required />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Telefone" name="phone" value="{{ old('phone') }}"
                        placeholder="(00) 0000-0000" inputmode="tel" data-mask="phone" autocomplete="tel" required />

                    <x-form-field label="WhatsApp" name="whatsapp" value="{{ old('whatsapp') }}"
                        placeholder="(00) 00000-0000" inputmode="tel" data-mask="phone" autocomplete="tel" required />
                </div>

                <x-form-field label="E-mail" name="email" value="{{ old('email') }}"
                    type="email" placeholder="voce@email.com" inputmode="email" autocomplete="email" required />
            </section>

            {{-- Etapa 2: Endereço --}}
            <section class="step-panel space-y-5 hidden" data-panel="2" data-title="Endereço">
                <h2 class="section-title mb-4 hidden sm:block">2. Endereço</h2>

                <div data-field="cep">
                    <label for="cep" class="form-label">CEP <span class="text-red-500" aria-hidden="true">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" id="cep" name="cep" value="{{ old('cep') }}"
                            class="form-input flex-1" placeholder="00000-000" inputmode="numeric" data-mask="cep"
                            aria-describedby="cep-feedback cep-error" required>
                        <x-button type="button" id="cep-search" variant="secondary" class="whitespace-nowrap">
                            <svg data-cep-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"
                                aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"/></svg>
                            <span data-cep-label>Buscar</span>
                        </x-button>
                    </div>
                    <p id="cep-feedback" class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500" hidden></p>
                    <p id="cep-error" class="field-error" role="alert" hidden></p>
                </div>

                <x-form-field label="Rua" name="address" value="{{ old('address') }}"
                    placeholder="Nome da rua / avenida" autocomplete="address-line1" required />

                <x-form-field label="Número" name="address_number" value="{{ old('address_number') }}"
                    placeholder="123" inputmode="numeric" autocomplete="address-line2" required />

                <x-form-field label="Bairro" name="neighborhood" value="{{ old('neighborhood') }}"
                    placeholder="Nome do bairro" autocomplete="address-level2" required />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form-field label="Cidade" name="city" value="{{ old('city') }}"
                        placeholder="Cidade" autocomplete="address-level2" required />

                    <x-select-field label="Estado" name="state" placeholder="Selecione o estado..." required>
                        @foreach ($states as $uf => $name)
                            <option value="{{ $uf }}" @selected(old('state') === $uf)>{{ $uf }} - {{ $name }}</option>
                        @endforeach
                    </x-select-field>
                </div>
            </section>

            {{-- Etapa 3: CNH --}}
            <section class="step-panel space-y-5 hidden" data-panel="3" data-title="CNH">
                <h2 class="section-title mb-4 hidden sm:block">3. CNH</h2>

                <x-form-field label="Número da CNH" name="cnh_number" value="{{ old('cnh_number') }}"
                    placeholder="Número impresso na CNH" inputmode="numeric" required />

                <x-select-field label="Categoria" name="cnh_category" placeholder="Selecione a categoria..." required>
                    @foreach ($cnhCategories as $category)
                        <option value="{{ $category }}" @selected(old('cnh_category') === $category)>{{ $category }}</option>
                    @endforeach
                </x-select-field>

                <x-form-field label="Data de validade" name="cnh_expiry_date" value="{{ old('cnh_expiry_date') }}"
                    type="date" min="{{ now()->addDay()->format('Y-m-d') }}" required
                    hint="A CNH deve estar dentro da validade." />
            </section>

            {{-- Etapa 4: Fotos dos documentos --}}
            <section class="step-panel space-y-5 hidden" data-panel="4" data-title="Documentos">
                <h2 class="section-title mb-4 hidden sm:block">4. Documentos</h2>
                <p class="text-sm text-slate-500">JPG, PNG ou WebP &middot; até 5 MB por arquivo.</p>

                @foreach (['cnh_front' => 'CNH (frente)', 'cnh_back' => 'CNH (verso)', 'proof_of_residence' => 'Comprovante de residência'] as $doc => $label)
                    <div @class(['upload-card', 'has-error' => $errors->has($doc . '_file')])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 pt-2">
                                <p class="text-sm font-medium text-slate-800">{{ $label }}</p>
                                <p class="text-xs text-slate-500">Obrigatório</p>
                                <p class="file-name mt-1 hidden max-w-[12rem] truncate text-xs font-medium text-indigo-600"></p>
                            </div>
                            @include('components.document-preview', ['doc' => $doc])
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <label class="upload-btn cursor-pointer">
                                <input type="file" name="{{ $doc }}_file" accept="image/*" capture="environment"
                                    class="document-input sr-only" data-doc="{{ $doc }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h4l1.5-2H15l1.5 2H20v12H4V6Zm8 10a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 16Z"/></svg>
                                Tirar foto
                            </label>
                            <label class="upload-btn cursor-pointer">
                                <input type="file" accept="image/*"
                                    class="gallery-input sr-only" data-doc="{{ $doc }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm8 11v-2H8v2h8Zm2-1H22a10 10 0 0 1-20 0h6v-3h4.5a3.5 3.5 0 0 1 6.3-2L22 8v2Z"/></svg>
                                Galeria
                            </label>
                        </div>
                        <p id="{{ $doc }}_file-error" class="field-error" role="alert"
                            @unless ($errors->has($doc . '_file')) hidden @endunless>{{ $errors->first($doc . '_file') }}</p>
                    </div>
                @endforeach
            </section>

            {{-- Etapa 5: Selfie / validação facial --}}
            <section class="step-panel space-y-5 hidden" data-panel="5" data-title="Selfie">
                <h2 class="section-title mb-4 hidden sm:block">5. Selfie</h2>
                <div>
                    <p class="text-sm text-slate-600">Tire uma selfie em ambiente claro, sem óculos e de frente para a câmera.</p>
                </div>

                <div @class(['upload-card', 'has-error' => $errors->has('selfie_file')])>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 pt-2">
                            <p class="text-sm font-medium text-slate-800">Sua selfie</p>
                            <p class="text-xs text-slate-500">Obrigatório</p>
                            <p class="file-name mt-1 hidden max-w-[12rem] truncate text-xs font-medium text-indigo-600"></p>
                        </div>
                        @include('components.document-preview', ['doc' => 'selfie'])
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <label class="upload-btn cursor-pointer">
                            <input type="file" name="selfie_file" accept="image/*" capture="user"
                                class="document-input sr-only" data-doc="selfie">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h4l1.5-2H15l1.5 2H20v12H4V6Zm8 10a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 16Z"/></svg>
                            Tirar selfie
                        </label>
                        <label class="upload-btn cursor-pointer">
                            <input type="file" accept="image/*"
                                class="gallery-input sr-only" data-doc="selfie">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm8 11v-2H8v2h8Zm2-1H22a10 10 0 0 1-20 0h6v-3h4.5a3.5 3.5 0 0 1 6.3-2L22 8v2Z"/></svg>
                            Escolher da galeria
                        </label>
                    </div>
                    <p id="selfie_file-error" class="field-error" role="alert"
                        @unless ($errors->has('selfie_file')) hidden @endunless>{{ $errors->first('selfie_file') }}</p>
                </div>
            </section>

            {{-- Etapa 6: Enviar cadastro --}}
            <section class="step-panel space-y-5 hidden" data-panel="6" data-title="Revisão">
                <h2 class="section-title mb-4 hidden sm:block">6. Revisão</h2>

                <div class="rounded-xl bg-slate-50 p-4 text-sm ring-1 ring-inset ring-slate-100">
                    <p class="mb-1 font-medium text-slate-500">Confira seus dados antes de enviar.</p>
                    <p id="summary-name" class="font-semibold text-slate-900"></p>
                    <p id="summary-cpf" class="text-slate-700"></p>
                    <p id="summary-phone" class="text-slate-700"></p>
                    <p id="summary-email" class="break-words text-slate-700"></p>
                    <p id="summary-city" class="text-slate-700"></p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="mb-3 text-sm font-medium text-slate-800">Declaração de veracidade</p>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="veracity_declaration_accepted" value="1"
                            class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 accent-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" required>
                        <span class="text-sm text-slate-600">Li e confirmo que as informações fornecidas são verdadeiras.</span>
                    </label>
                    <p id="veracity_declaration_accepted-error" class="field-error" role="alert"
                        @unless ($errors->has('veracity_declaration_accepted')) hidden @endunless>{{ $errors->first('veracity_declaration_accepted') }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="mb-3 text-sm font-medium text-slate-800">Política de privacidade</p>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="privacy_policy_accepted" value="1"
                            class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 accent-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" required>
                        <span class="text-sm text-slate-600">Autorizo o uso dos meus dados e documentos para análise da minha locação. Conheça nossa
                            <a href="{{ route('policy.index') }}" target="_blank" rel="noopener" class="text-indigo-600 underline">Política de Privacidade</a>.
                        </span>
                    </label>
                    <p id="privacy_policy_accepted-error" class="field-error" role="alert"
                        @unless ($errors->has('privacy_policy_accepted')) hidden @endunless>{{ $errors->first('privacy_policy_accepted') }}</p>
                </div>

                <x-button type="submit" id="submit-btn" size="lg" class="w-full">
                    <svg data-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"
                        aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"/></svg>
                    <span data-submit-label>Enviar cadastro</span>
                </x-button>
            </section>

            {{-- Navegação entre etapas --}}
            <div class="mt-8 flex items-center justify-between gap-3">
                <x-button type="button" id="prev-btn" variant="ghost" class="invisible shrink-0">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 17l-5-5m0 0 5-5m-5 5h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Voltar
                </x-button>
                <span id="step-counter" class="text-sm font-medium text-slate-400">1 / 6</span>
                <x-button type="button" id="next-btn" class="flex-1 sm:flex-none sm:px-8">
                    Continuar
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 7l5 5m0 0-5 5m5-5H6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </x-button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        window.registrationForm = {
            totalSteps: {{ count($steps) }},
            cepLookupUrl: @json(route('cep.lookup')),
        };
    </script>
@endpush