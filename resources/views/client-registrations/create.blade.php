@extends('layouts.public')

@section('title', 'Cadastro')

@section('content')
    <div class="card panel-card p-5 sm:p-8">
        <header class="mb-6">
            <h1 class="text-xl font-semibold tracking-tight text-white">Cadastro de Cliente</h1>
        </header>

        {{-- Barra de progresso (mobile) --}}
        <div class="mb-6 sm:hidden">
            <div class="flex items-center justify-between">
                <p id="step-count" class="text-xs font-semibold text-brand" aria-live="polite">Etapa 1 de {{ count($steps) }}</p>
            </div>
            <div
                id="step-progress"
                class="relative mt-2 h-1.5 overflow-hidden rounded-full bg-line-dark"
                role="progressbar"
                aria-valuemin="1"
                aria-valuemax="{{ count($steps) }}"
                aria-valuenow="1"
                aria-label="Progresso do cadastro">
                <div
                    id="stepbar-fill"
                    class="stepbar-fill"
                    style="width: {{ 100 / count($steps) }}%"></div>
            </div>
            <h1 id="step-title" class="mt-3 text-lg font-semibold tracking-tight text-white">Dados pessoais</h1>
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
                <h2 class="step-heading mb-4 hidden sm:flex">1. Dados pessoais</h2>

                <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <x-form-field label="Nome completo" name="full_name" value="{{ old('full_name') }}"
                            placeholder="Digite seu nome completo" autocomplete="name" required />
                    </div>

                    <div class="md:col-span-1">
                        <x-form-field label="CPF" name="cpf" value="{{ old('cpf') }}"
                            placeholder="000.000.000-00" inputmode="numeric" data-mask="cpf" autocomplete="off" required />
                    </div>

                    <div class="md:col-span-1">
                        <x-form-field label="Data de nascimento" name="birth_date" value="{{ old('birth_date') }}"
                            type="date" max="{{ \Carbon\Carbon::now()->subYears(18)->format('Y-m-d') }}" required />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:contents">
                        <div class="md:col-span-1 md:order-2">
                            <x-form-field label="Telefone" name="phone" value="{{ old('phone') }}"
                                placeholder="(00) 0000-0000" inputmode="tel" data-mask="phone" autocomplete="tel" required />
                        </div>

                        <div class="md:col-span-1 md:order-3">
                            <x-form-field label="WhatsApp" name="whatsapp" value="{{ old('whatsapp') }}"
                                placeholder="(00) 00000-0000" inputmode="tel" data-mask="phone" autocomplete="tel" required />
                        </div>
                    </div>

                    <div class="md:col-span-1 md:order-1">
                        <x-form-field label="E-mail" name="email" value="{{ old('email') }}"
                            type="email" placeholder="voce@email.com" inputmode="email" autocomplete="email" required />
                    </div>
                </div>
            </section>

            {{-- Etapa 2: Endereço --}}
            <section class="step-panel space-y-5 hidden" data-panel="2" data-title="Endereço">
                <h2 class="step-heading mb-4 hidden sm:flex">2. Endereço</h2>

                <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-12">
                    <div data-field="cep" class="md:col-span-4">
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
                        <p id="cep-feedback" class="mt-1.5 flex items-center gap-1.5 text-xs text-zinc-500" hidden></p>
                        <p id="cep-error" class="field-error" role="alert" hidden></p>
                    </div>

                    <div class="md:col-span-6">
                        <x-form-field label="Rua" name="address" value="{{ old('address') }}"
                            placeholder="Nome da rua / avenida" autocomplete="address-line1" required />
                    </div>

                    <div class="md:col-span-2">
                        <x-form-field label="Número" name="address_number" value="{{ old('address_number') }}"
                            placeholder="123" inputmode="numeric" autocomplete="address-line2" required />
                    </div>

                    <div class="md:col-span-5">
                        <x-form-field label="Bairro" name="neighborhood" value="{{ old('neighborhood') }}"
                            placeholder="Nome do bairro" autocomplete="address-level2" required />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:contents">
                        <div class="md:col-span-4">
                            <x-form-field label="Cidade" name="city" value="{{ old('city') }}"
                                placeholder="Cidade" autocomplete="address-level2" required />
                        </div>

                        <div class="md:col-span-3">
                            <x-select-field label="Estado" name="state" placeholder="Selecione o estado..." required>
                                @foreach ($states as $uf => $name)
                                    <option value="{{ $uf }}" @selected(old('state') === $uf)>{{ $uf }} - {{ $name }}</option>
                                @endforeach
                            </x-select-field>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Etapa 3: CNH --}}
            <section class="step-panel space-y-5 hidden" data-panel="3" data-title="CNH">
                <h2 class="step-heading mb-4 hidden sm:flex">3. CNH</h2>

                <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-12">
                    <div class="md:col-span-5">
                        <x-form-field label="Número da CNH" name="cnh_number" value="{{ old('cnh_number') }}"
                            placeholder="Número impresso na CNH" inputmode="numeric" required />
                    </div>

                    <div class="md:col-span-3">
                        <x-select-field label="Categoria" name="cnh_category" placeholder="Selecione a categoria..." required>
                            @foreach ($cnhCategories as $category)
                                <option value="{{ $category }}" @selected(old('cnh_category') === $category)>{{ $category }}</option>
                            @endforeach
                        </x-select-field>
                    </div>

                    <div class="md:col-span-4 min-w-0">
                        <x-form-field label="Data de validade" name="cnh_expiry_date" value="{{ old('cnh_expiry_date') }}"
                            type="date" min="{{ now()->addDay()->format('Y-m-d') }}" required
                            hint="A CNH deve estar dentro da validade." />
                    </div>

                    <div class="md:col-span-4">
                        <x-select-field label="Veículo" name="vehicle_id" placeholder="Selecione o veículo..." required>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->model }} · {{ $vehicle->plate }}</option>
                            @endforeach
                        </x-select-field>
                    </div>

                    <div class="md:col-span-4 min-w-0">
                        <x-form-field label="Data de início" name="start_date" value="{{ old('start_date') }}"
                            type="date" min="{{ now('America/Sao_Paulo')->format('Y-m-d') }}" required />
                    </div>

                    <div class="md:col-span-4 min-w-0">
                        <x-form-field label="Data de fim" name="end_date" value="{{ old('end_date') }}"
                            type="date" min="{{ now('America/Sao_Paulo')->format('Y-m-d') }}" required />
                    </div>

                    <div class="md:col-span-12">
                        <x-select-field label="Cota" name="quota_type_id" placeholder="Selecione a cota..." required>
                            @foreach ($availableQuotaOptions as $option)
                                <option value="{{ $option['id'] }}" data-vehicle-id="{{ $option['vehicle_id'] }}"
                                    data-days="{{ $option['days'] }}" data-base-label="{{ $option['label'] }}"
                                    @selected(old('quota_type_id') == $option['id'])>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </x-select-field>
                    </div>
                </div>
            </section>

            {{-- Etapa 4: Fotos dos documentos --}}
            <section class="step-panel space-y-5 hidden" data-panel="4" data-title="Documentos">
                <h2 class="step-heading mb-4 hidden sm:flex">4. Documentos</h2>
                <p class="text-sm text-zinc-500">JPG, PNG ou WebP &middot; até 5 MB por arquivo.</p>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                @foreach (['cnh_front' => 'CNH (frente)', 'cnh_back' => 'CNH (verso)', 'proof_of_residence' => 'Comprovante de residência'] as $doc => $label)
                    <div @class(['upload-card', 'has-error' => $errors->has($doc . '_file')])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 pt-2">
                                <p class="text-sm font-medium text-gray-100">{{ $label }}</p>
                                <p class="text-xs text-zinc-500">Obrigatório</p>
                                <p class="file-name mt-1 hidden max-w-[12rem] truncate text-xs font-medium text-brand"></p>
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
                </div>
            </section>

            {{-- Etapa 5: Selfie / validação facial --}}
            <section class="step-panel space-y-5 hidden" data-panel="5" data-title="Selfie">
                <h2 class="step-heading mb-4 hidden sm:flex">5. Selfie</h2>
                <div>
                    <p class="text-sm text-zinc-400">Tire uma selfie agora, em ambiente claro e de frente para a câmera, sem óculos.</p>
                </div>

                <div @class(['upload-card', 'has-error' => $errors->has('selfie_file')])>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 pt-2">
                            <p class="text-sm font-medium text-gray-100">Sua selfie</p>
                            <p class="text-xs text-zinc-500">Obrigatório</p>
                            <p class="file-name mt-1 hidden max-w-[12rem] truncate text-xs font-medium text-brand"></p>
                        </div>
                        @include('components.document-preview', ['doc' => 'selfie'])
                    </div>
                    <div class="mt-3">
                        <label class="upload-btn w-full cursor-pointer justify-center">
                            <input type="file" name="selfie_file" accept="image/*" capture="user"
                                class="document-input sr-only" data-doc="selfie">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h4l1.5-2H15l1.5 2H20v12H4V6Zm8 10a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 16Z"/></svg>
                            Tirar selfie
                        </label>
                    </div>
                    <p id="selfie_file-error" class="field-error" role="alert"
                        @unless ($errors->has('selfie_file')) hidden @endunless>{{ $errors->first('selfie_file') }}</p>
                </div>
            </section>

            {{-- Etapa 6: Enviar cadastro --}}
            <section class="step-panel space-y-5 hidden" data-panel="6" data-title="Revisão">
                <h2 class="step-heading mb-4 hidden sm:flex">6. Revisão</h2>

                {{-- Problemas agrupados por etapa (modo correção) --}}
                <div id="review-problems" hidden>
                    <p id="review-problems-intro" class="mb-3 flex items-center gap-2 text-sm font-medium text-amber-300">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        <span data-review-problems-intro-text></span>
                    </p>
                    <div class="space-y-2" data-review-problems-list></div>
                </div>

                <div class="rounded-xl bg-surface-850 p-4 text-sm ring-1 ring-inset ring-line-dark">
                    <p class="mb-1 font-medium text-zinc-400">Confira seus dados antes de enviar.</p>
                    <p id="summary-name" class="font-semibold text-white"></p>
                    <p id="summary-cpf" class="text-gray-300"></p>
                    <p id="summary-phone" class="text-gray-300"></p>
                    <p id="summary-email" class="break-words text-gray-300"></p>
                    <p id="summary-city" class="text-gray-300"></p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="rounded-xl border border-line-dark p-4">
                        <p class="mb-3 text-sm font-medium text-gray-100">Declaração de veracidade</p>
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="veracity_declaration_accepted" value="1"
                                @checked(old('veracity_declaration_accepted'))
                                class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-muted accent-brand focus:ring-2 focus:ring-brand focus:ring-offset-0" required>
                            <span class="text-sm text-zinc-300">Declaro que os dados e documentos enviados são verdadeiros.</span>
                        </label>
                        <p id="veracity_declaration_accepted-error" class="field-error" role="alert"
                            @unless ($errors->has('veracity_declaration_accepted')) hidden @endunless>{{ $errors->first('veracity_declaration_accepted') }}</p>
                    </div>

                    <div class="rounded-xl border border-line-dark p-4">
                        <p class="mb-3 text-sm font-medium text-gray-100">Política de privacidade</p>
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="privacy_policy_accepted" value="1"
                                @checked(old('privacy_policy_accepted'))
                                class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-muted accent-brand focus:ring-2 focus:ring-brand focus:ring-offset-0" required>
                            <span class="text-sm text-zinc-300">Autorizo o uso dos meus dados e documentos para análise da minha locação. Conheça nossa
                                <a href="{{ route('policy.index') }}" target="_blank" rel="noopener" class="text-brand underline hover:text-brand-soft">Política de Privacidade</a>.
                            </span>
                        </label>
                        <p id="privacy_policy_accepted-error" class="field-error" role="alert"
                            @unless ($errors->has('privacy_policy_accepted')) hidden @endunless>{{ $errors->first('privacy_policy_accepted') }}</p>
                    </div>
                </div>

                <div class="md:flex md:justify-center">
                    <p
                        data-submit-warning
                        class="mt-3 hidden rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm font-medium text-amber-200 md:mx-auto md:max-w-md"
                        role="alert"></p>
                </div>
            </section>

            {{-- Etapa 7: Contrato e assinatura --}}
            <section class="step-panel space-y-5 hidden" data-panel="7" data-title="Contrato e assinatura">
                <h2 class="step-heading mb-4 hidden sm:flex">7. Contrato e assinatura</h2>

                <div class="rounded-xl bg-surface-850 p-4 text-sm ring-1 ring-inset ring-line-dark">
                    <p class="mb-1 font-medium text-zinc-400">Contrato do Clube de Mobilidade</p>
                    <p class="text-gray-300">
                        Leia o contrato abaixo. Depois, assine para concluir seu cadastro.
                    </p>
                    <a
                        href="{{ route('client-registrations.contract') }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-line-dark bg-surface-800 px-4 py-2 text-sm font-semibold text-white transition hover:border-brand hover:text-brand sm:w-auto">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        Visualizar o contrato completo
                    </a>
                </div>

                <div>
                    <label for="contract_signer_name" class="form-label">Nome do signatário</label>
                    <input
                        type="text"
                        id="contract_signer_name"
                        name="contract_signer_name"
                        value="{{ old('contract_signer_name', '') }}"
                        maxlength="255"
                        readonly
                        class="form-input cursor-not-allowed opacity-70">
                    <p class="field-hint">O nome é preenchido automaticamente com os dados do cadastro.</p>
                    <p id="contract_signer_name-error" class="field-error" role="alert"
                        @unless ($errors->has('contract_signer_name')) hidden @endunless>{{ $errors->first('contract_signer_name') }}</p>
                </div>

                <div>
                    <p class="form-label">Sua assinatura</p>
                    <div
                        id="signature-canvas-wrap"
                        class="relative overflow-hidden rounded-xl bg-white ring-1 ring-inset ring-line-dark"
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
                    <input type="hidden" id="contract_signature" name="contract_signature"
                        value="{{ old('contract_signature', '') }}">
                    <p class="field-hint">Use o dedo ou o mouse para desenhar sua assinatura. Ela será aplicada ao contrato.</p>
                    <p id="contract_signature-error" class="field-error" role="alert"
                        @unless ($errors->has('contract_signature')) hidden @endunless>{{ $errors->first('contract_signature') }}</p>
                </div>

                <div class="rounded-xl border border-line-dark p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" id="contract_accepted" name="contract_accepted" value="1"
                            @checked(old('contract_accepted'))
                            class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-muted accent-brand focus:ring-2 focus:ring-brand focus:ring-offset-0" required>
                        <span class="text-sm text-zinc-300">Declaro que li e aceito os termos do Contrato do Clube de Mobilidade da VCA.</span>
                    </label>
                    <p id="contract_accepted-error" class="field-error" role="alert"
                        @unless ($errors->has('contract_accepted')) hidden @endunless>{{ $errors->first('contract_accepted') }}</p>
                </div>

                <div class="md:flex md:justify-center">
                    <x-button type="submit" id="submit-btn" size="lg" class="w-full md:min-w-64 md:w-auto"
                        disabled title="Assine o contrato e marque a aceitação para enviar">
                        <svg data-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"
                            aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"/></svg>
                        <span data-submit-label>Enviar cadastro</span>
                    </x-button>
                </div>
                <p
                    data-submit-warning
                    class="mt-3 hidden rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm font-medium text-amber-200 md:mx-auto md:max-w-md"
                    role="alert"></p>
            </section>

            {{-- Navegação entre etapas (somente mobile/tablet < 768px) --}}
            <div class="registration-nav mt-8 flex items-center justify-between gap-3">
                <x-button type="button" id="prev-btn" variant="ghost" class="invisible shrink-0">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 17l-5-5m0 0 5-5m-5 5h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Voltar
                </x-button>
                {{-- <span id="step-counter" class="text-sm font-medium text-zinc-500">1 / {{ count($steps) }}</span> --}}
                <x-button type="button" id="next-btn" class="flex-1 sm:flex-none sm:px-8">
                    <span data-next-label>Continuar</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 7l5 5m0 0-5 5m5-5H6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </x-button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @php
        $serverFieldToStep = [
            'full_name' => 1, 'cpf' => 1, 'birth_date' => 1, 'phone' => 1, 'whatsapp' => 1, 'email' => 1,
            'cep' => 2, 'address' => 2, 'address_number' => 2, 'neighborhood' => 2, 'city' => 2, 'state' => 2,
            'cnh_number' => 3, 'cnh_category' => 3, 'cnh_expiry_date' => 3,
            'vehicle_id' => 3, 'quota_type_id' => 3, 'start_date' => 3, 'end_date' => 3,
            'cnh_front_file' => 4, 'cnh_back_file' => 4, 'proof_of_residence_file' => 4,
            'documentos' => 4,
            'selfie_file' => 5,
            'veracity_declaration_accepted' => 6, 'privacy_policy_accepted' => 6,
            'contract_signature' => 7, 'contract_signer_name' => 7, 'contract_accepted' => 7,
        ];

        $serverErrorSteps = [];

        foreach ($errors->keys() as $errorKey) {
            $step = $serverFieldToStep[$errorKey] ?? 4;
            $serverErrorSteps[$step] = ($serverErrorSteps[$step] ?? 0) + 1;
        }

        $serverFieldErrors = collect($errors->messages())
            ->map(fn (array $messages) => (string) $messages[0])
            ->all();

        $registrationFormConfig = [
            'totalSteps' => count($steps),
            'cepLookupUrl' => route('cep.lookup'),
            'quotaAvailabilityUrl' => route('client-registrations.quota-availability'),
            'quotaDurationMode' => config('quotas.duration_mode', 'exact'),
            'successUrl' => route('client-registrations.success'),
            'fieldToStep' => $serverFieldToStep,
            'serverErrorSteps' => empty($serverErrorSteps) ? new stdClass : $serverErrorSteps,
            'serverFieldErrors' => empty($serverFieldErrors) ? new stdClass : $serverFieldErrors,
        ];
    @endphp

    {{-- Configuração em bloco de dados (não executável): segura sob a CSP atual,
         que bloqueia scripts inline mas permite type="application/json". --}}
    <script type="application/json" id="registration-form-config">
        @json($registrationFormConfig)
    </script>
@endpush