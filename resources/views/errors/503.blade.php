@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
    $canReload = request()->isMethod('GET');
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="503"
    title="Sistema temporariamente indisponível"
    icon="cloud"
    message="O sistema está temporariamente indisponível. Tente novamente em alguns instantes."
    :primary="$canReload
        ? ['label' => 'Tentar novamente', 'reload' => true]
        : ['label' => 'Voltar para o início', 'url' => ErrorContext::homeUrl($isAdmin)]"
/>