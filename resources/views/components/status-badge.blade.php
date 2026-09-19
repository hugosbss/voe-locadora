@props(['status', 'size' => 'sm'])

@php
    $tones = [
        'novo' => 'bg-brand/15 text-brand ring-brand/30',
        'em_analise' => 'bg-amber-500/15 text-amber-400 ring-amber-500/30',
        'aprovado' => 'bg-emerald-500/15 text-emerald-400 ring-emerald-500/30',
        'reprovado' => 'bg-red-500/15 text-red-400 ring-red-500/30',
        'pending' => 'bg-amber-500/15 text-amber-400 ring-amber-500/30',
        'matched' => 'bg-emerald-500/15 text-emerald-400 ring-emerald-500/30',
        'not_matched' => 'bg-red-500/15 text-red-400 ring-red-500/30',
    ];
    $dot = [
        'novo' => 'bg-brand',
        'em_analise' => 'bg-amber-400',
        'aprovado' => 'bg-emerald-400',
        'reprovado' => 'bg-red-400',
        'pending' => 'bg-amber-400',
        'matched' => 'bg-emerald-400',
        'not_matched' => 'bg-red-400',
    ];
    $tone = $tones[$status->value] ?? 'bg-zinc-500/15 text-zinc-300 ring-zinc-500/30';
@endphp

<span @class(['badge', 'px-3 py-1' => $size === 'md', $tone])>
    <span @class(['badge-dot', $dot[$status->value] ?? 'bg-zinc-500']) aria-hidden="true"></span>
    {{ $status->label() }}
</span>