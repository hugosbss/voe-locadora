@extends('layouts.public')

@section('title', 'Como funciona?')

@php
    $whatsappUrl = $whatsappUrl ?? 'https://wa.me/5579991379313';
    $city = $city ?? 'Itabaianinha';
    $priceFrom = $priceFrom ?? '599,00';
    $vehicleYear = $vehicleYear ?? '2027';
    $preLaunchLabel = $preLaunchLabel ?? 'Cotas promocionais — Pré lançamento';
    $instagram = $instagram ?? 'vcaclube';
    $instagramUrl = $instagramUrl ?? null;

    $steps = [
        ['num' => 1, 'title' => 'Escolha sua cota', 'desc' => 'de acordo com o período que deseja utilizar o veículo.'],
        ['num' => 2, 'title' => 'Faça sua adesão', 'desc' => 'e apresente a documentação necessária.'],
        ['num' => 3, 'title' => 'Agende seus dias de uso', 'desc' => 'conforme a disponibilidade do veículo.'],
        ['num' => 4, 'title' => 'Utilize o carro', 'desc' => 'durante o período contratado.'],
        ['num' => 5, 'title' => 'Devolva o veículo', 'desc' => 'seguindo o protocolo de uso e checklist da VCA.'],
    ];
@endphp

@section('content')
    <style>
        .hw-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #050505 0%, #0a0a0a 50%, #101010 100%);
        }
        .hw-hero::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(254,209,6,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .hw-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -15%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(254,209,6,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .hw-step-line {
            position: relative;
            padding-left: 3rem;
        }
        .hw-step-line::before {
            content: '';
            position: absolute;
            left: 1.125rem;
            top: 2.5rem;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #fed106, rgba(254,209,6,0.2));
        }
        .hw-step-line:last-child::before {
            display: none;
        }

        .hw-step-num {
            position: absolute;
            left: 0;
            top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.375rem;
            height: 2.375rem;
            border-radius: 9999px;
            background: #fed106;
            color: #050505;
            font-weight: 700;
            font-size: 0.875rem;
            z-index: 1;
        }

        .hw-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.025em;
        }

        @media (min-width: 768px) {
            .hw-step-grid {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 1rem;
            }
            .hw-step-grid .hw-step-line {
                padding-left: 0;
                padding-top: 3.5rem;
            }
            .hw-step-grid .hw-step-line::before {
                left: 1.125rem;
                top: 2.5rem;
                right: -1rem;
                bottom: auto;
                width: auto;
                height: 2px;
            }
            .hw-step-grid .hw-step-line:last-child::before {
                display: block;
            }
        }
    </style>

    <div class="space-y-0">

        {{-- ============================================ --}}
        {{-- Seção 1 — Hero institucional                  --}}
        {{-- ============================================ --}}
        <section class="hw-hero rounded-2xl p-6 sm:p-10 md:p-14 text-center" aria-label="Apresentação do VCA Clube">
            <div class="relative z-10 mx-auto max-w-2xl">
                <img
                    src="{{ asset('images/brand/vca-logo.jpeg') }}"
                    alt="VCA Clube"
                    width="72"
                    height="72"
                    class="mx-auto mb-6 h-16 w-16 rounded-2xl object-cover ring-2 ring-brand/30 sm:h-20 sm:w-20"
                    loading="eager"
                >

                {{-- <p class="hw-badge mx-auto mb-4 bg-brand/15 text-brand ring-1 ring-brand/25">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                    Chegou em {{ $city }}
                </p> --}}

                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl md:text-5xl">
                    VCA <span class="text-brand">|</span> CARROS POR COTAS
                </h1>

                <p class="mt-4 text-base font-medium text-brand-soft sm:text-lg">
                    Mobilidade inteligente ao seu alcance.
                </p>

                <p class="mt-4 mx-auto max-w-xl text-sm leading-relaxed text-zinc-400 sm:text-base"></p>

                <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <a
                        href="{{ route('client-registrations.create') }}"
                        class="btn btn-primary btn-lg w-full sm:w-auto"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </a>
                    <a
                        href="{{ $whatsappUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-secondary btn-lg w-full sm:w-auto"
                        aria-label="FALE AGORA PELO WHATSAPP"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                        FALE AGORA PELO WHATSAPP
                    </a>
                </div>
            </div>
        </section>

        {{-- ============================================ --}}
        {{-- Seção 2 — O que é o VCA Clube                --}}
        {{-- ============================================ --}}
        <section class="mt-6 rounded-2xl bg-brand p-6 ring-1 ring-black/20 sm:p-8" aria-labelledby="hw-about">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand"></p>
                <h2 id="hw-about" class="mt-2 text-2xl font-bold tracking-tight text-black sm:text-3xl">
                    CARROS POR COTAS
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-black/70 sm:text-base">
                    Uma forma prática, econômica e flexível de ter acesso a veículos {{ $vehicleYear }} por meio de cotas.
                </p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                {{-- Card 1 --}}
                <div class="rounded-xl border border-line-dark bg-surface-850 p-5 text-center transition-colors duration-150 hover:border-black/40">
                    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-brand/10 ring-1 ring-brand/20">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0H6.375c-.621 0-1.125-.504-1.125-1.125V14.25m17.25 4.5V6.75a3 3 0 0 0-3-3H6.375a3 3 0 0 0-3 3v8.25m16.5 0h1.5" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">Cotas para semana e fim de semana</h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500"></p>
                </div>

                {{-- Card 2 --}}
                <div class="rounded-xl border border-line-dark bg-surface-850 p-5 text-center transition-colors duration-150 hover:border-black/40">
                    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-brand/10 ring-1 ring-brand/20">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">Faça parte da nossa associação</h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500"></p>
                </div>

                {{-- Card 3 --}}
                <div class="rounded-xl border border-line-dark bg-surface-850 p-5 text-center transition-colors duration-150 hover:border-black/40">
                    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-brand/10 ring-1 ring-brand/20">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white">Não precisa comprar o carro por patrimônio, e sim ter apenas o custo</h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500"></p>
                </div>
            </div>
        </section>

        {{-- ============================================ --}}
        {{-- Seção 3 — Como funciona (5 passos)           --}}
        {{-- ============================================ --}}
        <section class="mt-6 rounded-2xl bg-surface-900 p-6 ring-1 ring-line-dark sm:p-8" aria-labelledby="hw-steps">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand"></p>
                <h2 id="hw-steps" class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Como funciona?
                </h2>
                <p class="mt-3 mx-auto max-w-xl text-sm text-zinc-400 sm:text-base">
                    É simples, rápido e sem burocracia. Veja como é fácil fazer parte da VCA Clube:
                </p>
            </div>

            {{-- Mobile: timeline vertical --}}
            <div class="mt-8 space-y-6 md:hidden">
                @foreach ($steps as $step)
                    <div class="hw-step-line relative">
                        <div class="hw-step-num">{{ $step['num'] }}</div>
                        <div class="rounded-xl border border-line-dark bg-surface-850 p-4">
                            <h3 class="text-sm font-bold text-brand">{{ $step['title'] }}</h3>
                            <p class="mt-1 text-xs leading-relaxed text-zinc-400">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop: horizontal connected --}}
            <div class="mt-8 hw-step-grid hidden items-stretch md:grid">
                @foreach ($steps as $step)
                    <div class="hw-step-line relative flex h-full">
                        <div class="hw-step-num">{{ $step['num'] }}</div>
                        <div class="mt-2 flex h-full w-full flex-col rounded-xl border border-line-dark bg-surface-850 p-4 text-center">
                            <h3 class="text-sm font-bold text-brand">{{ $step['title'] }}</h3>
                            <p class="mt-1.5 flex-1 text-xs leading-relaxed text-zinc-400">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ============================================ --}}
        {{-- Seção 4 — Cotas, veículo e oferta             --}}
        {{-- ============================================ --}}
        <section class="mt-6 overflow-hidden rounded-2xl ring-1 ring-black/20" aria-labelledby="hw-offer">
            <div class="grid gap-0 md:grid-cols-2">
                {{-- Lado esquerdo: destaque da oferta --}}
                <div class="flex flex-col justify-center bg-brand p-6 sm:p-8 md:p-10">
                    <span class="hw-badge mb-4 w-fit bg-black/15 text-black/80 ring-1 ring-black/10">
                        {{ $preLaunchLabel }}
                    </span>

                    <h2 id="hw-offer" class="text-2xl font-bold tracking-tight text-black sm:text-3xl">
                        Cotas a partir de
                    </h2>
                    <p class="mt-1 text-4xl font-extrabold tracking-tight text-black sm:text-5xl">
                        R$ {{ $priceFrom }}.
                    </p>
                    <p class="mt-3 text-sm font-medium text-black/70 sm:text-base">
                        Venha fazer parte da associação.
                    </p>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        {{-- <a
                            href="{{ route('client-registrations.create') }}"
                            class="btn inline-flex w-full items-center justify-center rounded-lg bg-black px-5 py-3 text-sm font-semibold text-brand transition-colors duration-150 hover:bg-surface-950 sm:w-auto"
                        >
                        </a> --}}
                        <a
                            href="{{ $whatsappUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn inline-flex w-full items-center justify-center rounded-lg border-2 border-black/20 bg-transparent px-5 py-3 text-sm font-semibold text-black transition-colors duration-150 hover:bg-black/10 sm:w-auto"
                            aria-label="FALE AGORA PELO WHATSAPP"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                            FALE AGORA PELO WHATSAPP
                        </a>
                    </div>
                </div>

                {{-- Lado direito: imagem do veículo --}}
                <div class="flex items-center justify-center border-t border-black/20 bg-brand p-6 sm:p-8 md:border-l md:border-t-0">
                    <img
                        src="{{ asset('images/brand/vca-polo.jpg') }}"
                        alt="Volkswagen Polo — Veículo disponível no VCA Clube"
                        width="280"
                        height="280"
                        class="h-auto w-full max-w-[280px] rounded-2xl object-contain"
                        loading="lazy"
                    >
                </div>
            </div>
        </section>

        {{-- ============================================ --}}
        {{-- Seção 5 — CTA final e canais de contato       --}}
        {{-- ============================================ --}}
        {{-- <section class="mt-6 rounded-2xl bg-surface-900 p-6 ring-1 ring-line-dark sm:p-8" aria-labelledby="hw-contact">
            <div class="text-center">
                <h2 id="hw-contact" class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    FALE AGORA PELO WHATSAPP
                </h2>
                <p class="mt-3 text-sm text-zinc-400 sm:text-base"></p>
            </div> --}}

            {{-- <div class="mt-8 flex flex-col items-center gap-6 sm:flex-row sm:justify-center sm:gap-10">
                {{-- QR Code --}}
                {{-- <div class="flex flex-col items-center gap-3">
                    <div class="rounded-2xl bg-white p-3 ring-1 ring-line-dark">
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($whatsappUrl) }}&bgcolor=ffffff&color=050505&margin=8"
                            alt="QR Code para WhatsApp do VCA Clube"
                            width="160"
                            height="160"
                            class="h-32 w-32 sm:h-40 sm:w-40"
                            loading="lazy"
                        >
                    </div>
                    <p class="text-xs font-medium text-zinc-500">{{ $instagram }}</p>
                </div> --}}

                {{-- Botões --}}
                {{-- <div class="flex flex-col items-center gap-3">
                    <a
                        href="{{ $whatsappUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-primary btn-lg w-full sm:w-auto"
                        aria-label="FALE AGORA PELO WHATSAPP"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                        FALE AGORA PELO WHATSAPP
                    </a>

                    <a
                        href="{{ route('client-registrations.create') }}"
                        class="btn btn-secondary btn-lg w-full sm:w-auto"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </a>

                    @if ($instagramUrl)
                        <a
                            href="{{ $instagramUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs text-zinc-500 hover:text-brand transition-colors duration-150"
                        >
                            {{ $instagram }}
                        </a>
                    @else
                        <span class="text-xs text-zinc-500">{{ $instagram }}</span>
                    @endif
                </div> --}}
            {{-- </div>
        </section> --}}

        {{-- ============================================ --}}
        {{-- Barra fixa inferior (somente mobile)          --}}
        {{-- ============================================ --}}
        {{-- <div
            class="fixed inset-x-0 bottom-0 z-40 border-t border-line-dark bg-surface-900/95 px-4 py-3 backdrop-blur-sm md:hidden"
            style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 0.75rem);"
        >
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('client-registrations.create') }}"
                    class="btn btn-primary min-h-11 flex-1 text-xs"
                >
                </a>
                <a
                    href="{{ $whatsappUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-secondary min-h-11 flex-1 text-xs"
                    aria-label="FALE AGORA PELO WHATSAPP"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                    FALE AGORA PELO WHATSAPP
                </a>
            </div>
        </div> --}}

        {{-- Espaçamento para a barra fixa no mobile --}}
        {{-- <div class="h-20 md:hidden"></div> --}}
    </div>
@endsection
