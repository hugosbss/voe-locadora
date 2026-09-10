/**
 * Ações das páginas de erro, sem depender de scripts inline (CSP).
 *
 * - "Voltar": usa o histórico do navegador quando existe; caso contrário
 *   navega para o destino seguro (data-fallback-url).
 * - "Tentar novamente" / "Atualizar página": recarrega a página atual.
 */
export function initErrorPage(doc = document) {
    const back = doc.querySelector('[data-error-back]');

    if (back) {
        back.addEventListener('click', (event) => {
            event.preventDefault();

            if (window.history.length > 1) {
                window.history.back();

                return;
            }

            const fallback = back.getAttribute('data-fallback-url');

            if (fallback) {
                window.location.assign(fallback);
            }
        });
    }

    const reload = doc.querySelector('[data-error-reload]');

    if (reload) {
        reload.addEventListener('click', () => window.location.reload());
    }
}