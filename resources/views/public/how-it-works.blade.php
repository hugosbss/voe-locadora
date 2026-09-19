@extends('layouts.public')

@section('title', 'Como funciona')

@section('content')
    <div class="space-y-6">
        <div class="card panel-card p-5 sm:p-8">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-brand">VCA Clube de Mobilidade</p>
            <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Como funciona</h1>
            <p class="mt-4 max-w-2xl text-base text-zinc-300">
                O VCA Clube de Mobilidade oferece acesso compartilhado a veículos por meio de cotas, com regras claras de disponibilidade, período de adesão e utilização do veículo.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <section class="card panel-card p-5 sm:p-6">
                <h2 class="section-title mb-3">1. O que é o VCA</h2>
                <p class="text-sm text-zinc-300">
                    O clube conecta clientes a veículos disponíveis em um sistema de cotas, com o acompanhamento administrativo da locadora e regras de uso definidas pela própria configuração do veículo e do tipo de cota.
                </p>
            </section>

            <section class="card panel-card p-5 sm:p-6">
                <h2 class="section-title mb-3">2. Como escolher um veículo</h2>
                <p class="text-sm text-zinc-300">
                    O cadastro público exibe os veículos ativos com suas cotas disponíveis. A lista é dinâmica e vem diretamente do banco conforme a disponibilidade real do momento.
                </p>
            </section>

            <section class="card panel-card p-5 sm:p-6">
                <h2 class="section-title mb-3">3. Como funcionam as cotas</h2>
                <p class="text-sm text-zinc-300">
                    Cada veículo pode ter diferentes tipos de cotas e quantidades independentes. A disponibilidade é calculada a partir da quantidade configurada menos as cotas efetivamente vendidas, preservando o histórico das adesões.
                </p>
            </section>

            <section class="card panel-card p-5 sm:p-6">
                <h2 class="section-title mb-3">4. Tipos de cota</h2>
                <div class="space-y-3">
                    @forelse ($quotaTypes as $quotaType)
                        <div class="rounded-xl border border-line-dark bg-surface-850 p-3">
                            <p class="text-sm font-semibold text-white">{{ $quotaType->code }} — {{ $quotaType->name }}</p>
                            <p class="text-xs text-zinc-400">{{ $quotaType->days }} dias</p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">Ainda não há tipos de cota ativos no sistema.</p>
                    @endforelse
                </div>
            </section>

            <section class="card panel-card p-5 sm:p-6 md:col-span-2">
                <h2 class="section-title mb-3">5. Como funciona a adesão</h2>
                <p class="text-sm text-zinc-300">
                    O cliente seleciona o veículo, o tipo de cota disponível e o período da adesão. O sistema registra a data de início e de término, preservando a regra de dias vigente no momento da contratação para manter o histórico correto.
                </p>
            </section>

            <section class="card panel-card p-5 sm:p-6">
                <h2 class="section-title mb-3">6. Escolha do período</h2>
                <p class="text-sm text-zinc-300">
                    A adesão pode ser contratada por um período determinado, com início e término definidos. As datas são validadas no backend para manter a consistência e preservar o histórico comercial.
                </p>
            </section>

            <section class="card panel-card p-5 sm:p-6">
                <h2 class="section-title mb-3">7. Uso do veículo</h2>
                <p class="text-sm text-zinc-300">
                    Durante a vigência da adesão, o cliente pode utilizar o veículo conforme a regra do clube. As utilizações são registradas pela equipe administrativa no momento em que ocorrem, sem criação automática de registros fictícios.
                </p>
            </section>

            <section class="card panel-card p-5 sm:p-6">
                <h2 class="section-title mb-3">8. Controle de utilizações</h2>
                <p class="text-sm text-zinc-300">
                    Cada utilização tem entrada, saída, fotos quando necessário e observação opcional. Isso permite rastrear o uso do veículo de forma segura e organizada.
                </p>
            </section>

            <section class="card panel-card p-5 sm:p-6 md:col-span-2">
                <h2 class="section-title mb-3">9. Entrada e saída</h2>
                <p class="text-sm text-zinc-300">
                    O controle de entrada e saída do veículo fica sob gestão administrativa. As fotos de uso não são enviadas pelo cliente no cadastro público: elas são registradas por administradores/autorizados, com armazenamento privado e proteção de acesso.
                </p>
            </section>
        </div>

        <div class="card panel-card p-5 sm:p-8 text-center">
            <h2 class="text-xl font-semibold tracking-tight text-white">Pronto para participar?</h2>
            <p class="mt-3 text-sm text-zinc-300">Faça seu cadastro e escolha a melhor cota para o seu veículo.</p>
            <a href="{{ route('client-registrations.create') }}" class="btn btn-primary mt-5 inline-flex items-center justify-center">
                Quero fazer meu cadastro
            </a>
        </div>
    </div>
@endsection
