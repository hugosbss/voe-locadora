<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e1b4b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Painel da Locadora')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body font-sans antialiased">
    <div class="min-h-screen lg:pl-64">

        {{-- ======================= Sidebar (desktop) ======================= --}}
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-slate-800 bg-slate-900 lg:flex">
            <div class="flex items-center gap-2.5 px-5 py-5">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-sky-500 ring-1 ring-white/10">
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M19 7h-1V6a3 3 0 0 0-6 0v1H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Zm-7-1a1 1 0 0 1 2 0v1h-2V6Zm7 14H5v-9h14v9Z" />
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-base font-semibold leading-tight tracking-tight text-white">Locadora</span>
                    <span class="block truncate text-xs text-slate-400">Painel administrativo</span>
                </span>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2" aria-label="Navegação principal">
                @include('partials.admin-nav', ['variant' => 'sidebar'])
            </nav>

            <div class="border-t border-slate-800 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-xs font-semibold text-indigo-200 ring-1 ring-inset ring-slate-700" aria-hidden="true">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-slate-200">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ auth()->user()->email }}</span>
                    </span>
                </div>
            </div>
        </aside>

        {{-- ======================= Header (mobile) ======================= --}}
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
            <button
                type="button"
                data-mobile-nav-open
                aria-controls="mobile-nav"
                aria-expanded="false"
                aria-label="Abrir menu"
                class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-600 ring-1 ring-inset ring-slate-200 transition-colors duration-150 hover:bg-slate-50 focus-visible:outline-indigo-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
            </button>
            <span class="min-w-0 flex-1 truncate text-center">
                <span class="block truncate text-sm font-semibold tracking-tight text-slate-900">Locadora</span>
                <span class="block truncate text-[11px] text-slate-500">Painel administrativo</span>
            </span>
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-xs font-semibold text-white" aria-hidden="true">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </span>
        </header>

        {{-- ======================= Drawer (mobile) ======================= --}}
        <div
            id="mobile-nav-backdrop"
            data-mobile-nav-backdrop
            class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-[2px]"></div>
        <nav
            id="mobile-nav"
            data-mobile-nav
            aria-label="Navegação principal"
            aria-hidden="true"
            class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85%] flex-col bg-slate-900 shadow-2xl transition-transform duration-200 ease-out lg:hidden -translate-x-full">
            <div class="flex items-center justify-between gap-3 px-4 py-4">
                <span class="flex min-w-0 items-center gap-2.5">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-sky-500">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 7h-1V6a3 3 0 0 0-6 0v1H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Zm-7-1a1 1 0 0 1 2 0v1h-2V6Zm7 14H5v-9h14v9Z" /></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold leading-tight tracking-tight text-white">Locadora</span>
                        <span class="block truncate text-xs text-slate-400">Painel administrativo</span>
                    </span>
                </span>
                <button
                    type="button"
                    data-mobile-nav-close
                    aria-label="Fechar menu"
                    class="inline-flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition-colors duration-150 hover:bg-slate-800 hover:text-white focus-visible:outline-indigo-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto px-3 pb-6">
                @include('partials.admin-nav', ['variant' => 'mobile'])
            </div>

            <div class="border-t border-slate-800 px-4 py-4">
                <p class="truncate text-sm font-medium text-slate-200">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>
        </nav>

        {{-- ======================= Conteúdo ======================= --}}
        <div class="flex min-h-screen flex-col">
            @php
                $sectionTitle = match (true) {
                    request()->routeIs('admin.registrations.*') => 'Cadastros',
                    request()->routeIs('admin.registration-link') => 'Link de cadastro',
                    request()->routeIs('admin.security.*') => 'Segurança',
                    default => 'Dashboard',
                };

                $adminName = auth()->user()->name ?? '';
                $adminEmail = auth()->user()->email ?? '';
            @endphp

            {{-- Header administrativo (desktop) --}}
            <header class="hidden border-b border-slate-200 bg-white lg:flex lg:items-center lg:justify-between lg:gap-4 lg:px-8 lg:py-4">
                <p class="min-w-0">
                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Painel da Locadora</span>
                    <span class="block truncate text-base font-semibold tracking-tight text-slate-900">{{ $sectionTitle }}</span>
                </p>
                <p class="flex shrink-0 items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-xs font-semibold text-white" aria-hidden="true">
                        {{ strtoupper(substr($adminName, 0, 1)) }}
                    </span>
                    <span class="min-w-0 text-left">
                        <span class="block truncate text-sm font-medium text-slate-900">{{ $adminName }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ $adminEmail }}</span>
                    </span>
                </p>
            </header>

            <main class="mx-auto w-full max-w-6xl flex-1 px-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-6 lg:px-8 lg:pt-8">
                @yield('content')
            </main>
        </div>
    </div>

    @php
        $toastQueue = array_values(array_filter([
            session('success') ? ['type' => 'success', 'message' => (string) session('success')] : null,
            session('error') ? ['type' => 'error', 'message' => (string) session('error')] : null,
            session('info') ? ['type' => 'info', 'message' => (string) session('info')] : null,
            ($errors->any() && ! session('error')) ? ['type' => 'error', 'message' => (string) $errors->first()] : null,
        ]));
    @endphp

    <x-toaster :queue="$toastQueue" />

    @stack('scripts')
</body>
</html>