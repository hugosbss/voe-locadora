@props([
    'id' => 'confirm-dialog',
    'title' => 'Confirmar',
    'message' => '',
    'confirmLabel' => 'Confirmar',
    'cancelLabel' => 'Cancelar',
    'variant' => 'primary',
    'icon' => 'triangle',
    'confirmTarget' => null,
    'confirmWhen' => null,
])

@php
    $tones = [
        'danger' => [
            'chip' => 'bg-red-50 text-red-600 ring-red-100',
            'button' => 'btn-danger',
        ],
        'primary' => [
            'chip' => 'bg-indigo-50 text-indigo-600 ring-indigo-100',
            'button' => 'btn-primary',
        ],
    ];
    $tone = $tones[$variant] ?? $tones['primary'];

    $icons = [
        'triangle' => 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'shield' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
    ];
    $iconPath = $icons[$icon] ?? $icons['triangle'];
@endphp

<dialog
    id="{{ $id }}"
    class="confirm-dialog"
    aria-labelledby="{{ $id }}-title"
    aria-describedby="{{ $id }}-message"
    @if ($confirmTarget) data-confirm-target="{{ $confirmTarget }}" @endif
    @if ($confirmWhen) data-confirm-when="{{ $confirmWhen }}" @endif>
    <div class="confirm-dialog-body">
        <span class="confirm-dialog-icon {{ $tone['chip'] }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
            </svg>
        </span>
        <h2 id="{{ $id }}-title" class="confirm-dialog-title">{{ $title }}</h2>
        <p id="{{ $id }}-message" class="confirm-dialog-message">{{ $message }}</p>
    </div>

    <form method="dialog" class="confirm-dialog-actions">
        <button type="submit" value="cancel" class="btn btn-secondary">
            {{ $cancelLabel }}
        </button>
        <button type="submit" value="confirm" autofocus class="btn {{ $tone['button'] }}">
            {{ $confirmLabel }}
        </button>
    </form>
</dialog>