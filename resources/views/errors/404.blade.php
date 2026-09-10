@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="404"
    title="Página não encontrada"
    icon="search"
    message="A página que você procura não existe ou foi movida."
    :primary="['label' => 'Voltar para o início', 'url' => ErrorContext::homeUrl($isAdmin)]"
    :secondary="['label' => 'Voltar', 'url' => ErrorContext::homeUrl($isAdmin), 'back' => true]"
/>