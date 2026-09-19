<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#050505">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Cadastro') | VCA</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('img/logo-vca.jpeg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="app-header">
            <div class="mx-auto w-full max-w-2xl px-4 pt-[calc(env(safe-area-inset-top)+1.25rem)] pb-5 md:max-w-4xl">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="inline-flex h-11 w-11 shrink-0 overflow-hidden rounded-xl ring-1 ring-white/10">
                            <img src="{{ asset('images/brand/vca-logo.jpeg') }}" alt="VCA Locadora" width="44" height="44" class="h-full w-full object-cover">
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-lg font-semibold leading-tight tracking-tight text-white">VCA</p>
                            <p class="truncate text-xs text-gray-400">Formulário de cadastro</p>
                        </div>
                    </div>
                    <nav class="hidden items-center gap-4 text-sm text-zinc-300 md:flex" aria-label="Navegação pública">
                        <a href="{{ route('public.how-it-works') }}" class="hover:text-white">Como funciona</a>
                        <a href="{{ route('client-registrations.create') }}" class="hover:text-white">Cadastro</a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-6 md:max-w-4xl">
            @yield('content')
        </main>

        <footer class="border-t border-line-dark bg-surface-900/80">
            <div class="mx-auto flex w-full max-w-2xl flex-col items-center justify-between gap-3 px-4 py-4 text-sm text-zinc-400 md:max-w-4xl md:flex-row">
                <p>VCA Clube de Mobilidade</p>
                <nav class="flex items-center gap-4" aria-label="Rodapé público">
                    <a href="{{ route('public.how-it-works') }}" class="hover:text-white">Como funciona</a>
                    <a href="{{ route('client-registrations.create') }}" class="hover:text-white">Cadastro</a>
                </nav>
            </div>
        </footer>

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
        <div class="mx-auto w-full max-w-2xl rounded-2xl bg-surface-900/95 p-4 text-sm text-gray-200 shadow-2xl shadow-black/40 ring-1 ring-line-dark backdrop-blur">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <p class="flex-1 leading-relaxed">
                    Este site usa apenas cookies necessários ao funcionamento do cadastro.
                    Nenhum cookie de rastreamento é usado.
                    <a href="{{ route('policy.index') }}" class="text-brand underline underline-offset-2 hover:text-brand-soft">
                        Saiba mais
                    </a>.
                </p>
                <button type="button" data-cookie-banner-accept
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-black transition-colors duration-150 hover:bg-brand-hover">
                    Entendi
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>