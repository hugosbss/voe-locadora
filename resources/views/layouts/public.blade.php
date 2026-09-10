<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e40af">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body font-sans text-slate-800 antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="app-header">
            <div class="mx-auto w-full max-w-2xl px-4 pt-[calc(env(safe-area-inset-top)+1.25rem)] pb-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                            <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M19 7h-1V6a3 3 0 0 0-6 0v1H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Zm-7-1a1 1 0 0 1 2 0v1h-2V6Zm7 14H5v-9h14v9Zm-9-6a1 1 0 1 0 2 0v-2a1 1 0 1 0-2 0v2Zm5 0a1 1 0 1 0 0-2h-1.5a1 1 0 1 0 0 2H15Z" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-lg font-semibold leading-tight tracking-tight text-white">Locadora</p>
                            <p class="truncate text-xs text-indigo-200">Cadastro de Clientes</p>
                        </div>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs text-indigo-100 ring-1 ring-white/20">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Dados protegidos
                    </span>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-6">
            @yield('content')
        </main>

        </div>

    @php
        $toastQueue = array_values(array_filter([
            session('success') ? ['type' => 'success', 'message' => (string) session('success')] : null,
            session('error') ? ['type' => 'error', 'message' => (string) session('error')] : null,
            ($errors->any() && ! session('error'))
                ? ['type' => 'error', 'message' => 'Não foi possível enviar o cadastro. Confira os campos destacados.']
                : null,
        ]));
    @endphp

    <x-toaster :queue="$toastQueue" />

    {{-- Aviso de cookies: somente cookies estritamente necessários. --}}
    <div data-cookie-banner hidden class="fixed inset-x-0 bottom-0 z-50 p-4">
        <div class="mx-auto w-full max-w-2xl rounded-2xl bg-slate-900/95 p-4 text-sm text-slate-100 shadow-2xl ring-1 ring-white/10 backdrop-blur">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <p class="flex-1 leading-relaxed">
                    Este site usa apenas cookies necessários ao funcionamento do cadastro.
                    Nenhum cookie de rastreamento é usado.
                    <a href="{{ route('policy.index') }}" class="text-indigo-300 underline underline-offset-2 hover:text-indigo-200">
                        Saiba mais
                    </a>.
                </p>
                <button type="button" data-cookie-banner-accept
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-indigo-500">
                    Entendi
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>