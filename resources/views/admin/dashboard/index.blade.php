@extends('layouts.admin')

@section('title', 'Dashboard &middot; Painel da Locadora')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Visão geral</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $stats['total'] }} {{ $stats['total'] === 1 ? 'cadastro' : 'cadastros' }}.
                </p>
            </div>
            <a href="{{ route('admin.registrations.index') }}" class="back-link self-start sm:self-auto">
                Ver todos os cadastros
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ([
                'total' => ['Total', 'text-slate-900'],
                'new' => ['Novos', 'text-indigo-600'],
                'review' => ['Em análise', 'text-amber-600'],
                'approved' => ['Aprovados', 'text-emerald-600'],
                'rejected' => ['Reprovados', 'text-rose-600'],
            ] as $key => [$label, $tone])
                <div class="card p-4">
                    <p @class(['text-2xl font-semibold tracking-tight', $tone])>{{ number_format($stats[$key]) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        {{-- Acesso rápido: link do formulário --}}
        <section class="card p-5 sm:p-6" aria-labelledby="link-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="link-heading" class="section-title">Link de cadastro</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Copie e envie ao cliente.
                    </p>
                </div>
                <a href="{{ route('admin.registration-link') }}" class="back-link">
                    Abrir página
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            <p id="copy-status-dashboard" class="sr-only" role="status"></p>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                <p class="min-w-0 flex-1 rounded-lg bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 break-all">
                    {{ $publicUrl }}
                </p>
                <x-button type="button" variant="secondary" data-js-copy data-copy-url="{{ $publicUrl }}" data-copy-status="copy-status-dashboard" class="w-full sm:w-auto">
                    Copiar link
                </x-button>
            </div>
        </section>

        {{-- Cadastros recentes --}}
        <section class="space-y-3" aria-labelledby="recent-heading">
            <div class="flex items-center justify-between gap-3">
                <h2 id="recent-heading" class="section-title">Cadastros recentes</h2>
                <a href="{{ route('admin.registrations.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Ver todos</a>
            </div>

            @if ($recent->isEmpty())
                <div class="card p-10 text-center text-sm text-slate-500">
                    Nenhum cadastro ainda.
                </div>
            @else
                <div class="card divide-y divide-slate-100 overflow-hidden">
                    @foreach ($recent as $registration)
                        <a href="{{ route('admin.registrations.show', $registration) }}" class="flex items-center gap-3 px-4 py-3 transition-colors duration-100 hover:bg-slate-50 sm:px-5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $registration->full_name }}</p>
                                <p class="truncate text-xs text-slate-500">
                                    {{ $registration->maskedCpf() }} &middot; {{ $registration->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                @include('components.status-badge', ['status' => $registration->status])
                                <svg class="hidden h-4 w-4 text-slate-300 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8.25 4.5 7.5 7.5-7.5 7.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection