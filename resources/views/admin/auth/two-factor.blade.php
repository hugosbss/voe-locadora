<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e40af">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Verificação em duas etapas &middot; Painel da Locadora</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                    <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </span>
                <span class="mt-3 block text-xl font-semibold tracking-tight text-white">Verificação em duas etapas</span>
            </div>

            <div class="card p-6 sm:p-8">
                <h1 class="mb-1 text-lg font-semibold tracking-tight text-slate-900">Código de verificação</h1>
                <p class="mb-6 text-sm text-slate-500">
                    Informe o código do seu aplicativo autenticador ou um código de recuperação.
                </p>

                @if ($errors->any())
                    <x-toaster :queue="[['type' => 'error', 'message' => (string) $errors->first()]]" />
                @endif

                <form method="POST" action="{{ route('admin.login.two-factor.verify') }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="code" class="form-label">Código</label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}"
                            class="form-input text-center text-lg tracking-[0.4em]" inputmode="numeric"
                            autocomplete="one-time-code" maxlength="32" autofocus required>
                    </div>

                    <x-button type="submit" class="w-full">
                        Verificar e entrar
                    </x-button>
                </form>
            </div>

            <div class="mt-5 text-center">
                <a href="{{ route('admin.login') }}" class="text-sm text-indigo-100 underline-offset-2 hover:underline">
                    Voltar ao login
                </a>
            </div>
        </div>
    </div>
</body>
</html>