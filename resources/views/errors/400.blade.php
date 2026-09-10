@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="400"
    title="Solicitação inválida"
    icon="triangle"
    message="A solicitação não pôde ser processada. Tente novamente."
    :secondary="['label' => 'Voltar para o início', 'url' => ErrorContext::homeUrl($isAdmin)]"
/>