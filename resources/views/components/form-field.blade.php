@props([
    'label' => '',
    'name' => '',
    'value' => '',
    'type' => 'text',
    'placeholder' => '',
    'hint' => '',
    'min' => null,
    'max' => null,
    'inputmode' => null,
    'autocomplete' => null,
    'dataMask' => null,
    'required' => false,
])

<div class="space-y-1.5" data-field="{{ $name }}">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-red-500" aria-hidden="true">*</span>
        @endif
    </label>

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $type === 'date' ? $value : old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if ($min) min="{{ $min }}" @endif
        @if ($max) max="{{ $max }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($dataMask) data-mask="{{ $dataMask }}" @endif
        @if ($required) required @endif
        aria-describedby="{{ trim((($hint ? $name . '-hint' : '') . ' ' . $name . '-error')) }}"
        @if ($errors->has($name)) aria-invalid="true" @endif
        @class(['form-input', 'input-invalid' => $errors->has($name)])>

    @if ($hint)
        <p id="{{ $name }}-hint" class="field-hint">{{ $hint }}</p>
    @endif

    <p
        id="{{ $name }}-error"
        class="field-error"
        role="alert"
        @unless ($errors->has($name)) hidden @endunless>{{ $errors->first($name) }}</p>
</div>