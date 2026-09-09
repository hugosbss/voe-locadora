@extends('layouts.public')

@section('title', 'Cadastro enviado')

@section('content')
    <div class="card p-6 text-center sm:p-10">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-8 w-8 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Cadastro enviado com sucesso</h1>
        <p class="mt-2 text-base leading-relaxed text-slate-600">
            Seus dados serão analisados pela nossa equipe.
        </p>

        <p class="mt-6 text-sm text-slate-400">
            Você receberá o retorno pelos canais informados no cadastro.
        </p>

        <a href="{{ route('client-registrations.create') }}" class="btn btn-ghost mt-8">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 17l-5-5m0 0 5-5m-5 5h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Voltar ao início
        </a>
    </div>
@endsection