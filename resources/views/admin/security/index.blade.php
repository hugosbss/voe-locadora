@extends('layouts.admin')

@section('title', 'Segurança')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ([
                'Cadastros' => $summary['total'],
                'Novos' => $summary['pending'],
                'Em análise' => $summary['under_review'],
                'Aprovados' => $summary['approved'],
                'Reprovados' => $summary['rejected'],
                'Registros de auditoria' => $summary['audit_entries'],
            ] as $label => $value)
                <div class="card p-4">
                    <p class="text-2xl font-semibold tracking-tight text-white">{{ number_format($value) }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-100">Verificação em duas etapas (2FA)</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        Proteção adicional com o aplicativo autenticador ao entrar no painel.
                    </p>
                </div>

                @if ($enabled)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/30">
                        Ativado
                    </span>
                @elseif ($secretAvailable)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 px-3 py-1 text-xs font-medium text-amber-400 ring-1 ring-amber-500/30">
                        Aguardando confirmação
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-800 px-3 py-1 text-xs font-medium text-zinc-400 ring-1 ring-line-dark">
                        Desativado
                    </span>
                @endif
            </div>

            @if (! $enabled)
                <div class="mt-5 border-t border-line-dark pt-5">
                    @if (! $secretAvailable)
                        <form method="POST" action="{{ route('admin.security.2fa.enable') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            <div class="flex-1 space-y-1.5">
                                <label for="enable-password" class="form-label">Confirme sua senha</label>
                                <input type="password" name="password" id="enable-password"
                                    class="form-input" autocomplete="current-password" required>
                            </div>
                            <x-button type="submit">Ativar 2FA</x-button>
                        </form>
                    @else
                        <div x-data="{ showSecret: false }" class="space-y-5">
                            <div class="flex flex-col gap-5 sm:flex-row">
                                <div class="flex-1">
                                    <h3 class="mb-2 text-sm font-medium text-gray-100">Passo 1 — Cadastre no aplicativo</h3>
                                    <div class="flex items-start gap-4">
                                        <img src="{{ route('admin.security.2fa.qr') }}" alt="QR code para cadastro no autenticador"
                                            class="h-44 w-44 rounded-xl ring-1 ring-line-dark" width="220" height="220">
                                        <div class="text-xs leading-relaxed text-zinc-400">
                                            <ol class="list-decimal space-y-1 pl-4">
                                                <li>Abra seu aplicativo autenticador (Google Authenticator, Authy, 1Password etc.).</li>
                                                <li>Escaneie o QR code ao lado ou insira a chave manualmente.</li>
                                            </ol>
                                            <details class="mt-3">
                                                <summary class="cursor-pointer text-brand underline-offset-2 hover:text-brand-soft">Mostrar chave manual</summary>
                                                <code class="mt-2 block break-all rounded-lg bg-surface-850 px-3 py-2 text-xs ring-1 ring-line-dark">
                                                    {{ $enrollment['secret'] }}
                                                </code>
                                            </details>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <h3 class="mb-2 text-sm font-medium text-gray-100">Passo 2 — Guarde os códigos de recuperação</h3>
                                    @if (count($enrollment['recovery_codes'] ?? []) > 0)
                                        <ul class="grid grid-cols-2 gap-1 rounded-lg bg-surface-850 p-3 text-xs ring-1 ring-line-dark">
                                            @foreach ($enrollment['recovery_codes'] as $code)
                                                <li class="font-mono text-gray-200">{{ $code }}</li>
                                            @endforeach
                                        </ul>
                                        <p class="mt-2 text-xs text-zinc-500">
                                            Cada código é de uso único e serve caso você perca o aplicativo.
                                        </p>
                                    @else
                                        <p class="rounded-lg bg-amber-500/10 p-3 text-xs text-amber-300 ring-1 ring-amber-500/30">
                                            Os códigos de recuperação foram exibidos apenas uma vez,
                                            no momento em que a ativação foi iniciada. Se você não os salvou,
                                            desative e ative novamente o 2FA.
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="border-t border-line-dark pt-4">
                                <h3 class="mb-2 text-sm font-medium text-gray-100">Passo 3 — Confirme o primeiro código</h3>
                                <form method="POST" action="{{ route('admin.security.2fa.confirm') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                    @csrf
                                    <div class="flex-1 space-y-1.5">
                                        <label for="confirm-code" class="form-label">Código do autenticador</label>
                                        <input type="text" name="code" id="confirm-code"
                                            class="form-input text-lg tracking-[0.4em]" inputmode="numeric"
                                            autocomplete="one-time-code" maxlength="16" required>
                                    </div>
                                    <x-button type="submit">Confirmar e ativar</x-button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-5 border-t border-line-dark pt-5">
                    <h3 class="text-sm font-medium text-gray-100">Desativar</h3>
                    <p class="mb-3 mt-0.5 text-xs text-zinc-500">
                        Exige sua senha e o código atual para evitar desativação por terceiros.
                    </p>
                    <form method="POST" action="{{ route('admin.security.2fa.disable') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1 space-y-1.5">
                            <label for="disable-password" class="form-label">Senha</label>
                            <input type="password" name="password" id="disable-password"
                                class="form-input" autocomplete="current-password" required>
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <label for="disable-code" class="form-label">Código atual</label>
                            <input type="text" name="code" id="disable-code"
                                class="form-input text-lg tracking-[0.4em]" inputmode="numeric"
                                autocomplete="one-time-code" maxlength="16" required>
                        </div>
                        <x-button type="submit" class="!bg-rose-600 hover:!bg-rose-500">Desativar</x-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection