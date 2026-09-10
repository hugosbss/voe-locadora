@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="403"
    title="Acesso negado"
    icon="shield"
    message="Você não tem permissão para acessar este conteúdo."
    :secondary="['label' => 'Voltar', 'url' => ErrorContext::homeUrl($isAdmin), 'back' => true]"
/>