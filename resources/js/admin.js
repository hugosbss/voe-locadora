/**
 * Recursos do painel administrativo.
 */
export const initAdmin = () => {
    // Filtro por status (envia ao trocar a opção)
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            const url = new URL(window.location.href);
            if (statusFilter.value) {
                url.searchParams.set('status', statusFilter.value);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        });
    }

    // Alteração de status: confirma quando escolher "reprovado"
    document.querySelectorAll('#status-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const select = form.querySelector('select[name="status"]');

            if (select?.value === 'reprovado') {
                const confirmed = window.confirm(
                    'Reprovar este cadastro? O cliente não poderá ter a locação analisada.'
                );
                if (!confirmed) {
                    event.preventDefault();
                }
            }
        });
    });

    // Mostrar/ocultar senha no login
    const toggle = document.getElementById('password-toggle');
    const password = document.getElementById('password');
    if (toggle && password) {
        toggle.addEventListener('click', () => {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            toggle.setAttribute('aria-pressed', String(type === 'text'));
            toggle.querySelector('[data-icon-eye]')?.classList.toggle('hidden', type === 'text');
            toggle.querySelector('[data-icon-eye-off]')?.classList.toggle('hidden', type !== 'text');
        });
    }
};