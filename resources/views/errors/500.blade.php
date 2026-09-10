@php
    use App\Support\ErrorContext;

    $isAdmin = ErrorContext::isAdmin();
    $canReload = request()->isMethod('GET');
@endphp

<x-error-display
    :is-admin="$isAdmin"
    code="500"
    title="Algo deu errado"
    icon="server"
    message="Não foi possível concluir esta operação. Tente novamente em alguns instantes."
    :primary="$canReload
        ? ['label' => 'Tentar novamente', 'reload' => true]
        : ['label' => 'Voltar para o início', 'url' => ErrorContext::homeUrl($isAdmin)]"
/>