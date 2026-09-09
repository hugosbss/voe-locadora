<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e40af">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Painel da Locadora')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body font-sans antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="shadow-lg shadow-indigo-950/10 bg-gradient-to-r from-indigo-950 via-blue-800 to-sky-600">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 pt-[calc(env(safe-area-inset-top)+0.75rem)]">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/20">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M19 7h-1V6a3 3 0 0 0-6 0v1H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Zm-7-1a1 1 0 0 1 2 0v1h-2V6Zm7 14H5v-9h14v9Z" />
                        </svg>
                    </span>
                    <a href="{{ route('admin.registrations.index') }}" class="truncate text-base font-semibold tracking-tight text-white hover:text-indigo-100">
                        Painel da Locadora
                    </a>
                </div>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-indigo-100 ring-1 ring-inset ring-white/20 transition-colors duration-150 hover:bg-white/10 hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="hidden sm:inline">Sair</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 pb-[calc(env(safe-area-inset-bottom)+1rem)]">
            @if (session('success'))
                <div class="mb-4">
                    <x-alert type="success">{{ session('success') }}</x-alert>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4">
                    <x-alert type="error">{{ $errors->first() }}</x-alert>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>