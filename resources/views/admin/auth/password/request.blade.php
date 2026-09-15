<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#050505">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Recuperar senha | VCA</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('img/logo-vca.jpeg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-6 flex flex-col items-center gap-3 text-center">
                <span class="inline-flex h-14 w-14 overflow-hidden rounded-2xl ring-1 ring-white/10">
                    <img src="{{ asset('images/brand/vca-logo.jpeg') }}" alt="VCA Locadora" width="56" height="56" class="h-full w-full object-cover">
                </span>
                <span class="text-xl font-semibold tracking-tight text-white">VCA</span>
            </div>

            <div class="card p-6 sm:p-8">
                <h1 class="mb-1 text-lg font-semibold tracking-tight text-white">Recuperar senha</h1>
                <p class="mb-6 text-sm text-zinc-400">
                    Informe o e-mail utilizado no acesso administrativo.
                </p>

                @if (session('status'))
                    <x-toaster :queue="[['type' => 'success', 'message' => (string) session('status')]]" />
                @endif

                @if ($errors->any())
                    <x-toaster :queue="[['type' => 'error', 'message' => (string) $errors->first()]]" />
                @endif

                <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            class="form-input" autocomplete="email" autofocus required>
                    </div>

                    <x-button type="submit" class="w-full">
                        Enviar link de recuperação
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