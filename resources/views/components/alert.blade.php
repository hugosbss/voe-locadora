@props(['type' => 'info'])

@php
    $tones = [
        'success' => ['alert-success', 'M9 12l2 2 4-4'],
        'error' => ['alert-error', 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z'],
        'info' => ['alert-info', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ];
    $tone = $tones[$type] ?? $tones['info'];
@endphp

<div class="alert {{ $tone[0] }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">
    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        aria-hidden="true"><path d="{{ $tone[1] }}" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <div class="min-w-0">{{ $slot }}</div>
</div>