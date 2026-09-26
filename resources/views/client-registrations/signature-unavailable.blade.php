@extends('layouts.public')

@section('title', $title)

@section('content')
    <div class="mx-auto w-full max-w-2xl">
        <div class="card panel-card p-6 text-center sm:p-10">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/15 ring-1 ring-inset ring-amber-500/30" aria-hidden="true">
                <svg class="h-8 w-8 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h1 class="text-2xl font-semibold tracking-tight text-white">{{ $title }}</h1>
            <p class="mt-2 text-zinc-400">{{ $message }}</p>

            <a href="{{ route('public.how-it-works') }}" class="btn btn-ghost mt-8">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11.17 8.89 7.41 12.66a2 2 0 0 0 0 2.83l3.76 3.76M2.5 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Como funciona
            </a>
        </div>
    </div>
@endsection
