@props([
    'label' => '',
    'name' => '',
    'placeholder' => 'Selecione...',
    'hint' => '',
    'required' => false,
])

<div class="space-y-1.5" data-field="{{ $name }}">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-red-500" aria-hidden="true">*</span>
        @endif
    </label>

    <div
        class="relative"
        data-custom-select
        data-cs-label="{{ $label }}"
        data-cs-placeholder="{{ $placeholder }}">

        <select
            id="{{ $name }}"
            name="{{ $name }}"
            @if ($required) required @endif
            aria-describedby="{{ $name }}-error"
            @if ($errors->has($name)) aria-invalid="true" @endif
            @class(['form-input', 'input-invalid' => $errors->has($name)])>
            <option value="">{{ $placeholder }}</option>
            {{ $slot }}
        </select>
    </div>

    @if ($hint)
        <p id="{{ $name }}-hint" class="field-hint">{{ $hint }}</p>
    @endif

    <p
        id="{{ $name }}-error"
        class="field-error"
        role="alert"
        @unless ($errors->has($name)) hidden @endunless>{{ $errors->first($name) }}</p>
</div>