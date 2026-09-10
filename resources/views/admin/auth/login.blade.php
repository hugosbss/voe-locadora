<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e40af">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Entrar &middot; Painel da Locadora</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-6 flex flex-col items-center gap-2 text-center">
                <svg class="h-11 w-11 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M19 7h-1V6a3 3 0 0 0-6 0v1H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z" />
                </svg>
                <span class="text-xl font-semibold tracking-tight text-white">Painel da Locadora</span>
            </div>

            <div class="card p-6 sm:p-8">
                <h1 class="mb-1 text-lg font-semibold tracking-tight text-slate-900">Acesso restrito</h1>
                <p class="mb-6 text-sm text-slate-500">Faça login para acessar o painel.</p>

                @if ($errors->any())
                    <x-toaster :queue="[['type' => 'error', 'message' => (string) $errors->first()]]" />
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            class="form-input" autocomplete="username" required autofocus>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="form-label">Senha</label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                class="form-input pr-11" autocomplete="current-password" required>
                            <button
                                type="button"
                                id="password-toggle"
                                aria-pressed="false"
                                aria-label="Mostrar senha"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition-colors duration-150 hover:text-slate-600">
                                <svg data-icon-eye class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <svg data-icon-eye-off class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1"
                            class="h-4 w-4 rounded border-slate-300 accent-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Manter conectado
                    </label>

                    <x-button type="submit" class="w-full">
                        Entrar
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>