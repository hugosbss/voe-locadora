<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#050505">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') | VCA</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('img/logo-vca.jpeg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="admin-body font-sans antialiased"
    @if (session('status_updated_redirect'))
        data-status-redirect-after="2000"
        data-status-redirect-url="{{ route('admin.registrations.index', ['status_updated' => 1]) }}"
    @endif>
    <div class="min-h-screen lg:pl-64">

        {{-- ======================= Sidebar (desktop) ======================= --}}
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-line-dark bg-surface-900 lg:flex">
            <div class="flex items-center gap-2.5 px-5 py-5">
                <span class="inline-flex h-10 w-10 shrink-0 overflow-hidden rounded-xl ring-1 ring-white/10">
                    <img src="{{ asset('images/brand/vca-logo.jpeg') }}" alt="VCA Locadora" width="40" height="40" class="h-full w-full object-cover">
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-base font-semibold leading-tight tracking-tight text-white">VCA</span>
                    <span class="block truncate text-xs text-zinc-500">Painel administrativo</span>
                </span>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2" aria-label="Navegação principal">
                @include('partials.admin-nav', ['variant' => 'sidebar'])
            </nav>

            <div class="mt-auto border-t border-line-dark px-5 py-4">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-400 transition-colors duration-100 hover:bg-white/5 hover:text-white">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- ======================= Header (mobile) ======================= --}}
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-line-dark bg-surface-900 px-4 py-3 lg:hidden">
            <button
                type="button"
                data-mobile-nav-open
                aria-controls="mobile-nav"
                aria-expanded="false"
                aria-label="Abrir menu"
                class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-gray-300 ring-1 ring-inset ring-line-dark transition-colors duration-150 hover:bg-white/5 focus-visible:outline-brand">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
            </button>
            <span class="min-w-0 flex-1 truncate text-center">
                <span class="block truncate text-sm font-semibold tracking-tight text-white">VCA</span>
                <span class="block truncate text-[11px] text-zinc-500">Painel administrativo</span>
            </span>
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand text-xs font-semibold text-black" aria-hidden="true">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </span>
        </header>

        {{-- ======================= Drawer (mobile) ======================= --}}
        <div
            id="mobile-nav-backdrop"
            data-mobile-nav-backdrop
            class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-[2px]"></div>
        <nav
            id="mobile-nav"
            data-mobile-nav
            aria-label="Navegação principal"
            aria-hidden="true"
            class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85%] flex-col bg-surface-900 ring-1 ring-line-dark transition-transform duration-200 ease-out lg:hidden -translate-x-full">
            <div class="flex items-center justify-between gap-3 px-4 py-4">
                <span class="flex min-w-0 items-center gap-2.5">
                    <span class="inline-flex h-9 w-9 shrink-0 overflow-hidden rounded-lg ring-1 ring-white/10">
                        <img src="{{ asset('images/brand/vca-logo.jpeg') }}" alt="VCA Locadora" width="36" height="36" class="h-full w-full object-cover">
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold leading-tight tracking-tight text-white">VCA</span>
                        <span class="block truncate text-xs text-zinc-500">Painel administrativo</span>
                    </span>
                </span>
                <button
                    type="button"
                    data-mobile-nav-close
                    aria-label="Fechar menu"
                    class="inline-flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-lg text-zinc-500 transition-colors duration-150 hover:bg-white/5 hover:text-white focus-visible:outline-brand">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto px-3 pb-6">
                @include('partials.admin-nav', ['variant' => 'mobile'])
            </div>

            <div class="mt-auto border-t border-line-dark px-4 py-4">
                <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-400 transition-colors duration-100 hover:bg-white/5 hover:text-white">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </nav>

        {{-- ======================= Conteúdo ======================= --}}
        <div class="flex min-h-screen flex-col">
            @php
                $sectionTitle = match (true) {
                    request()->routeIs('admin.registrations.*') => 'Cadastros',
                    request()->routeIs('admin.registration-link') => 'Link de cadastro',
                    request()->routeIs('admin.security.*') => 'Segurança',
                    request()->routeIs('admin.users.*') => 'Usuários',
                    default => 'Dashboard',
                };

                $adminName = auth()->user()->name ?? '';
                $adminEmail = auth()->user()->email ?? '';
            @endphp

            {{-- Header administrativo (desktop) --}}
            <header class="hidden border-b border-line-dark bg-surface-900 lg:flex lg:items-center lg:justify-between lg:gap-4 lg:px-8 lg:py-4">
                <p class="min-w-0">
                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-500">VCA</span>
                    <span class="block truncate text-base font-semibold tracking-tight text-white">{{ $sectionTitle }}</span>
                </p>
                <p class="flex shrink-0 items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand text-xs font-semibold text-black" aria-hidden="true">
                        {{ strtoupper(substr($adminName, 0, 1)) }}
                    </span>
                    <span class="min-w-0 text-left">
                        <span class="block truncate text-sm font-medium text-gray-200">{{ $adminName }}</span>
                        <span class="block truncate text-xs text-zinc-500">{{ $adminEmail }}</span>
                    </span>
                </p>
            </header>

            <main class="mx-auto w-full max-w-6xl flex-1 px-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-6 lg:px-8 lg:pt-8">
                @yield('content')
            </main>
        </div>
    </div>

    @php
        // Confirmação herdada do redirecionamento automático pós-status
        // (realizado no navegador ~2s após salvar): a listagem repõe o toast.
        $statusUpdatedToast = request()->routeIs('admin.registrations.index') && request()->boolean('status_updated')
            ? ['type' => 'success', 'message' => 'Status atualizado com sucesso.']
            : null;

        $toastQueue = array_values(array_filter([
            $statusUpdatedToast,
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