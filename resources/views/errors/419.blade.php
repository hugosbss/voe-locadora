@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="419"
    title="Sessão expirada"
    icon="clock"
    message="Sua sessão expirou. Atualize a página e tente novamente."
    :primary="['label' => 'Atualizar página', 'reload' => true]"
    :secondary="['label' => 'Voltar para o início', 'url' => ErrorContext::homeUrl($isAdmin)]"
/>