@extends('layouts.admin')

@section('title', 'Link de cadastro')

@section('content')
    <div class="mx-auto w-full max-w-2xl">
        <div class="card p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand/15 text-brand ring-1 ring-inset ring-brand/30">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold tracking-tight text-white">Link de cadastro</h1>
                    <p class="mt-1 text-sm text-zinc-400">
                        Copie e envie ao cliente.
                    </p>
                </div>
            </div>

            <p id="link-copy-status" class="sr-only" role="status"></p>

            <div class="mt-6">
                <p class="form-label mb-1.5">Link público do cadastro</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <p class="min-w-0 flex-1 rounded-lg bg-surface-850 px-3.5 py-3 text-sm font-medium text-gray-200 ring-1 ring-inset ring-line-dark break-all sm:py-2.5">
                        {{ $publicUrl }}
                    </p>
                    <div class="flex shrink-0 gap-2">
                        <x-button type="button" variant="primary" size="lg" data-js-copy data-copy-url="{{ $publicUrl }}" data-copy-status="link-copy-status" class="flex-1 sm:flex-none">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Copiar link
                        </x-button>

                        <x-button type="button" variant="secondary" size="lg" data-js-share data-share-title="Cadastro de cliente" data-share-text="Preencha seus dados para o cadastro (link público e seguro)." data-share-url="{{ $publicUrl }}" class="flex-1 sm:flex-none">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Compartilhar
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection