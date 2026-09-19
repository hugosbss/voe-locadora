@props([
    'doc' => '',
    'src' => null,
    'alt' => 'Pré-visualização',
])

<div class="preview-wrap" data-preview="{{ $doc }}">
    <div @class(['preview-empty-icon', 'hidden' => $src])>
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 2h8v2H8V9Zm0 4h5v2H8v-2Z"/></svg>
    </div>
    <img src="{{ $src ?? '' }}" alt="{{ $alt }}" @class(['preview-image', 'hidden' => ! $src]) loading="lazy">
    <button
        type="button"
        class="remove-preview"
        data-doc="{{ $doc }}"
        aria-label="Remover imagem">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
            aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
    </button>
</div>
