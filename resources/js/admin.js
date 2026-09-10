/**
 * Recursos do painel administrativo.
 */
import { toast } from './toasts';

export const initAdmin = (root = document) => {
    // Filtro por status (envia ao trocar a opção)
    const statusFilter = root.getElementById('status-filter');
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
    root.querySelectorAll('#status-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const select = form.querySelector('select[name="status"]');

            if (select?.value === 'reprovado') {
                const confirmed = window.confirm(
                    'Reprovar este cadastro?'
                );
                if (!confirmed) {
                    event.preventDefault();
                }
            }
        });
    });

    // Mostrar/ocultar senha no login
    const toggle = root.getElementById('password-toggle');
    const password = root.getElementById('password');
    if (toggle && password) {
        toggle.addEventListener('click', () => {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            toggle.setAttribute('aria-pressed', String(type === 'text'));
            toggle.querySelector('[data-icon-eye]')?.classList.toggle('hidden', type === 'text');
            toggle.querySelector('[data-icon-eye-off]')?.classList.toggle('hidden', type !== 'text');
        });
    }

    // ---------------------- Drawer de navegação (mobile) ----------------------
    const nav = root.querySelector('[data-mobile-nav]');
    const backdrop = root.querySelector('[data-mobile-nav-backdrop]');
    const openBtn = root.querySelector('[data-mobile-nav-open]');
    const closeBtn = root.querySelector('[data-mobile-nav-close]');

    if (nav) {
        const setNav = (open) => {
            nav.classList.toggle('-translate-x-full', !open);
            backdrop?.classList.toggle('hidden', !open);
            nav.setAttribute('aria-hidden', String(!open));
            openBtn?.setAttribute('aria-expanded', String(open));
            document.body.style.overflow = open ? 'hidden' : '';
            if (open) {
                closeBtn?.focus();
            } else {
                openBtn?.focus();
            }
        };

        openBtn?.addEventListener('click', () => setNav(true));
        closeBtn?.addEventListener('click', () => setNav(false));
        backdrop?.addEventListener('click', () => setNav(false));
        nav.querySelectorAll('[data-mobile-nav-link]').forEach((link) => {
            link.addEventListener('click', () => setNav(false));
        });
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setNav(false);
            }
        });
    }

    // ---------------------- Copiar link (Clipboard API + fallback) ----------------------
    root.querySelectorAll('[data-js-copy]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            const url = button.getAttribute('data-copy-url') ?? '';
            const originalHtml = button.innerHTML;
            const success = await copyText(url);

            const statusId = button.getAttribute('data-copy-status');
            const statusEl = statusId ? root.getElementById(statusId) : null;

            if (statusEl) {
                statusEl.textContent = success
                    ? 'Link copiado.'
                    : 'Não foi possível copiar o link.';
            }

            if (success) {
                button.innerHTML = `${checkIcon()} <span>Copiado</span>`;
                button.classList.add('is-copied');
                toast('success', 'Link copiado');
                window.setTimeout(() => {
                    button.innerHTML = originalHtml;
                    button.classList.remove('is-copied');
                }, 2600);
            } else {
                toast('error', 'Não foi possível copiar o link');
            }
        });
    });

    // ---------------------- Compartilhar (Web Share API) ----------------------
    root.querySelectorAll('[data-js-share]').forEach((button) => {
        if (typeof navigator.share !== 'function') {
            button.classList.add('hidden');
            return;
        }

        button.addEventListener('click', async (event) => {
            event.preventDefault();
            try {
                await navigator.share({
                    title: button.getAttribute('data-share-title') ?? '',
                    text: button.getAttribute('data-share-text') ?? '',
                    url: button.getAttribute('data-share-url') ?? '',
                });
            } catch {
                // Usuário cancelou; nenhum erro técnico deve aparecer.
            }
        });
    });
};

function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard
            .writeText(text)
            .then(() => true)
            .catch(() => legacyCopy(text));
    }

    return Promise.resolve(legacyCopy(text));
}

function legacyCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.top = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, text.length);

    let ok = false;
    try {
        ok = document.execCommand('copy');
    } catch {
        ok = false;
    }

    document.body.removeChild(textarea);

    return ok;
}

function checkIcon() {
    return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
}