@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'id' => null,
    'class' => '',
])

@php
    $variants = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
    ];

    $sizes = [
        'sm' => 'btn-sm',
        'md' => '',
        'lg' => 'btn-lg',
    ];
@endphp

<button
    @if ($type) type="{{ $type }}" @endif
    @if ($id) id="{{ $id }}" @endif
    {{ $attributes->merge([
        'class' => trim('btn ' . ($variants[$variant] ?? 'btn-primary') . ' ' . ($sizes[$size] ?? '') . ' ' . $class),
    ]) }}>
    {{ $slot }}
</button>