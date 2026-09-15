<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#050505">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Definir nova senha | VCA</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('img/logo-vca.jpeg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand/15 ring-1 ring-brand/30">
                    <svg class="h-6 w-6 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <span class="mt-3 block text-xl font-semibold tracking-tight text-white">Definir nova senha</span>
            </div>

            <div class="card p-6 sm:p-8">
                <h1 class="mb-1 text-lg font-semibold tracking-tight text-white">Nova senha</h1>
                <p class="mb-6 text-sm text-zinc-400">
                    Informe a nova senha de acesso ao painel.
                </p>

                @if ($errors->any())
                    <x-toaster :queue="[['type' => 'error', 'message' => (string) $errors->first()]]" />
                @endif

                <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-4">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="space-y-1.5">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ $email }}"
                            class="form-input" autocomplete="username" readonly required>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="form-label">Nova senha</label>
                        <input type="password" name="password" id="password"
                            class="form-input" autocomplete="new-password" minlength="8" required autofocus>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="form-input" autocomplete="new-password" minlength="8" required>
                    </div>

                    <x-button type="submit" class="w-full">
                        Salvar nova senha
                    </x-button>
                </form>
            </div>

            <div class="mt-5 text-center">
                <a href="{{ route('admin.login') }}" class="text-sm text-brand underline-offset-2 hover:underline">
                    Voltar ao login
                </a>
            </div>
        </div>
    </div>
</body>
</html>