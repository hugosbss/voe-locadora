@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="401"
    title="Acesso necessário"
    icon="lock"
    message="Faça login para acessar esta página."
    :primary="$isAdmin
        ? ['label' => 'Fazer login', 'url' => route('admin.login')]
        : ['label' => 'Voltar para o início', 'url' => ErrorContext::homeUrl(false)]"
/>