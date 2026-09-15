@props([
    'code' => '',
    'title' => '',
    'message' => '',
    'icon' => 'search',
    'primary' => null,
    'secondary' => null,
    'isAdmin' => null,
])

@php
    use App\Support\ErrorContext;

    $isAdmin = $isAdmin ?? ErrorContext::isAdmin();
    $homeUrl = ErrorContext::homeUrl($isAdmin);

    $icons = [
        'triangle' => ['chip' => 'bg-amber-500/15 text-amber-400 ring-amber-500/30', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        'lock' => ['chip' => 'bg-brand/15 text-brand ring-brand/30', 'path' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z'],
        'shield' => ['chip' => 'bg-rose-500/15 text-rose-400 ring-rose-500/30', 'path' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
        'search' => ['chip' => 'bg-zinc-500/15 text-zinc-300 ring-zinc-500/30', 'path' => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z'],
        'clock' => ['chip' => 'bg-orange-500/15 text-orange-400 ring-orange-500/30', 'path' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        'gauge' => ['chip' => 'bg-amber-500/15 text-amber-400 ring-amber-500/30', 'path' => 'M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z'],
        'server' => ['chip' => 'bg-rose-500/15 text-rose-400 ring-rose-500/30', 'path' => 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5'],
        'cloud' => ['chip' => 'bg-zinc-500/15 text-zinc-300 ring-zinc-500/30', 'path' => 'M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z'],
    ];

    $iconCfg = $icons[$icon] ?? $icons['search'];
    $primary ??= null;
    $secondary ??= null;
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#050505">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} | {{ $title }} | {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'font-sans antialiased',
    'admin-body' => $isAdmin,
    'app-body' => ! $isAdmin,
])>
    <div class="flex min-h-screen flex-col">

        @if ($isAdmin)
            <header class="border-b border-line-dark bg-surface-900">
                <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 pt-[calc(env(safe-area-inset-top)+0.75rem)]">
                    <a href="{{ $homeUrl }}" class="flex min-w-0 items-center gap-2.5">
                        <span class="inline-flex h-9 w-9 shrink-0 overflow-hidden rounded-lg ring-1 ring-white/10">
                            <img src="{{ asset('images/brand/vca-logo.jpeg') }}" alt="VCA Locadora" width="36" height="36" class="h-full w-full object-cover">
                        </span>
                        <span class="truncate text-base font-semibold tracking-tight text-gray-100 hover:text-brand-soft">
                            VCA
                        </span>
                    </a>
                </div>
            </header>
        @else
            <header class="app-header">
                <div class="mx-auto w-full max-w-2xl px-4 pt-[calc(env(safe-area-inset-top)+1.25rem)] pb-5">
                    <a href="{{ $homeUrl }}" class="flex items-center gap-3">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 overflow-hidden rounded-xl ring-1 ring-white/10">
                                <img src="{{ asset('images/brand/vca-logo.jpeg') }}" alt="VCA Locadora" width="40" height="40" class="h-full w-full object-cover">
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-lg font-semibold leading-tight tracking-tight text-white">VCA</span>
                                <span class="block truncate text-xs text-gray-400">Formulário de cadastro</span>
                            </span>
                        </span>
                    </a>
                </div>
            </header>
        @endif

        <main id="main" aria-labelledby="error-title"
            class="mx-auto flex w-full max-w-2xl flex-1 items-center justify-center px-4 py-10 sm:py-14">
            <div class="error-enter card w-full max-w-md p-6 text-center sm:p-10">
                <span @class(['error-icon-chip', $iconCfg['chip']])>
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconCfg['path'] }}" />
                    </svg>
                </span>

                <p class="error-code mt-5 select-none" aria-hidden="true">{{ $code }}</p>

                <h1 id="error-title" class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">
                    {{ $title }}
                </h1>

                <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-zinc-400 sm:text-base">
                    {{ $message }}
                </p>

                @if ($primary || $secondary)
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-center">
                        @if ($primary)
                            @if (($primary['reload'] ?? false) === true)
                                <button type="button" data-error-reload
                                    class="btn btn-primary w-full sm:w-auto">
                                    {{ $primary['label'] }}
                                </button>
                            @else
                                <a href="{{ $primary['url'] }}" class="btn btn-primary w-full sm:w-auto">
                                    {{ $primary['label'] }}
                                </a>
                            @endif
                        @endif

                        @if ($secondary)
                            @if (($secondary['back'] ?? false) === true)
                                <a href="{{ $secondary['url'] ?? $homeUrl }}" data-error-back
                                    data-fallback-url="{{ $secondary['url'] ?? $homeUrl }}"
                                    class="btn btn-secondary w-full sm:w-auto">
                                    {{ $secondary['label'] }}
                                </a>
                            @else
                                <a href="{{ $secondary['url'] }}" class="btn btn-secondary w-full sm:w-auto">
                                    {{ $secondary['label'] }}
                                </a>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </main>

        @if (! $isAdmin)
            <footer class="pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-6 text-center text-xs text-zinc-500">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </footer>
        @endif
    </div>
</body>
</html>