@props(['status', 'size' => 'sm'])

@php
    $tones = [
        'novo' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'em_analise' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'aprovado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'reprovado' => 'bg-red-50 text-red-700 ring-red-200',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'matched' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'not_matched' => 'bg-red-50 text-red-700 ring-red-200',
    ];
    $dot = [
        'novo' => 'bg-blue-500',
        'em_analise' => 'bg-amber-500',
        'aprovado' => 'bg-emerald-500',
        'reprovado' => 'bg-red-500',
        'pending' => 'bg-amber-500',
        'matched' => 'bg-emerald-500',
        'not_matched' => 'bg-red-500',
    ];
    $tone = $tones[$status->value] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
@endphp

<span @class(['badge', 'px-3 py-1' => $size === 'md', $tone])>
    <span @class(['badge-dot', $dot[$status->value] ?? 'bg-slate-400']) aria-hidden="true"></span>
    {{ $status->label() }}
</span>