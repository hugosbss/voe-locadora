@props(['queue' => []])

<div
    data-toaster
    class="toaster"
    aria-live="polite"
    aria-atomic="false"
    tabindex="-1"></div>

@if (count($queue) > 0)
    <script type="application/json" data-toast-queue>@json(array_values($queue))</script>
@endif