@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
    $canReload = request()->isMethod('GET');
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="429"
    title="Muitas solicitações"
    icon="gauge"
    message="Muitas tentativas em pouco tempo. Aguarde alguns instantes e tente novamente."
    :primary="$canReload
        ? ['label' => 'Tentar novamente', 'reload' => true]
        : ['label' => 'Voltar para o início', 'url' => ErrorContext::homeUrl($isAdmin)]"
/>