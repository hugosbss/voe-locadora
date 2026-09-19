/**
 * Recursos do painel administrativo.
 */
import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';
import { toast } from './toasts';

export const initAdmin = (root = document) => {
    initDocumentGallery(root);
    initVehiclePhotoUploads(root);
    // Após salvar um status, a página de detalhes exibe a confirmação e,
    // passado o intervalo (ex.: 2000 ms), volta automaticamente à listagem.
    const redirectAfter = Number(root.body?.dataset.statusRedirectAfter ?? 0);
    const redirectUrl = root.body?.dataset.statusRedirectUrl ?? '';

    if (redirectAfter > 0 && redirectUrl) {
        window.setTimeout(() => {
            window.location.href = redirectUrl;
        }, redirectAfter);
    }

    // Alteração de status: confirmação via dialog quando o status é "reprovado"
    root.querySelectorAll('#status-form').forEach((form) => {
        const select = form.querySelector('select[name="status"]');
        const dialog = root.querySelector(`[data-confirm-target="#${form.id}"]`);

        if (!select || !dialog) {
            return;
        }

        const concernValue = dialog.dataset.confirmWhen || 'reprovado';

        form.addEventListener('submit', (event) => {
            if (select.value !== concernValue) {
                return;
            }

            event.preventDefault();
            dialog.returnValue = '';
            dialog.showModal();
        });

        dialog.addEventListener('close', () => {
            if (dialog.returnValue === 'confirm') {
                form.submit();
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

/**
 * Handles vehicle photo uploads on the registration edit form.
 *
 * The gallery input is intentionally nameless: the selected file is copied
 * into the named input before the form is submitted.
 */
function initVehiclePhotoUploads(root) {
    root.querySelectorAll('[data-admin-upload-card]').forEach((card) => {
        const input = card.querySelector('[data-admin-upload-input]');
        const gallery = card.querySelector('[data-admin-gallery-input]');
        const preview = card.querySelector('.preview-image');
        const empty = card.querySelector('.preview-empty-icon');
        const fileName = card.querySelector('.file-name');
        const existingSrc = card.dataset.existingSrc ?? '';

        const showFile = (file) => {
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                empty?.classList.add('hidden');
                preview?.classList.remove('hidden');
                preview.src = reader.result;
            };
            reader.readAsDataURL(file);
            card.classList.add('is-filled');
            if (fileName) {
                fileName.textContent = file.name;
            }
        };

        const restorePreview = () => {
            if (input) {
                input.value = '';
            }
            if (gallery) {
                gallery.value = '';
            }

            if (existingSrc) {
                empty?.classList.add('hidden');
                preview?.classList.remove('hidden');
                preview.src = existingSrc;
                card.classList.add('is-filled');
                if (fileName) {
                    fileName.textContent = 'Foto salva';
                }
            } else {
                empty?.classList.remove('hidden');
                preview?.classList.add('hidden');
                preview?.removeAttribute('src');
                card.classList.remove('is-filled');
                if (fileName) {
                    fileName.textContent = 'Nenhuma imagem selecionada';
                }
            }
        };

        input?.addEventListener('change', () => showFile(input.files?.[0]));
        gallery?.addEventListener('change', () => {
            const file = gallery.files?.[0];
            if (!file || !input) {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            showFile(file);
            gallery.value = '';
        });
        card.querySelector('.remove-preview')?.addEventListener('click', (event) => {
            event.preventDefault();
            restorePreview();
        });
    });
}

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

/**
 * Visualização das fotos "Fotos e documentos" em lightbox (Fancybox 5).
 * Os links apontam para a rota protegida admin.registrations.photo;
 * somente documentos enviados participam da galeria.
 */
function initDocumentGallery(root) {
    const group = '[data-fancybox="cadastro-docs"]';

    if (!root.querySelector(group)) {
        return;
    }

    Fancybox.defaults.l10n = {
        PANUP: 'Mover para cima',
        PANDOWN: 'Mover para baixo',
        PANLEFT: 'Mover para a esquerda',
        PANRIGHT: 'Mover para a direita',
        ZOOMIN: 'Ampliar',
        ZOOMOUT: 'Reduzir',
        TOGGLEZOOM: 'Alternar nível de zoom',
        TOGGLE1TO1: 'Alternar nível de zoom',
        ITERATEZOOM: 'Alternar nível de zoom',
        ROTATECCW: 'Girar no sentido anti-horário',
        ROTATECW: 'Girar no sentido horário',
        FLIPX: 'Inverter horizontalmente',
        FLIPY: 'Inverter verticalmente',
        FITX: 'Ajustar horizontalmente',
        FITY: 'Ajustar verticalmente',
        RESET: 'Redefinir',
        TOGGLEFS: 'Alternar tela cheia',
        CLOSE: 'Fechar',
        NEXT: 'Próximo',
        PREV: 'Anterior',
        MODAL: 'Você pode fechar este conteúdo com a tecla ESC',
        ERROR: 'Algo deu errado. Tente novamente mais tarde.',
        IMAGE_ERROR: 'Imagem não encontrada',
        ELEMENT_NOT_FOUND: 'Elemento HTML não encontrado',
        AJAX_NOT_FOUND: 'Erro ao carregar AJAX: não encontrado',
        AJAX_FORBIDDEN: 'Erro ao carregar AJAX: proibido',
        IFRAME_ERROR: 'Erro ao carregar página',
        TOGGLE_ZOOM: 'Alternar nível de zoom',
        TOGGLE_THUMBS: 'Alternar miniaturas',
        TOGGLE_SLIDESHOW: 'Alternar apresentação de slides',
        TOGGLE_FULLSCREEN: 'Alternar modo tela cheia',
        DOWNLOAD: 'Baixar',
    };

    Fancybox.bind(group, {
        closeClickOutside: true,
        Carousel: {
            infinite: false,
        },
        Toolbar: {
            display: {
                left: ['counter'],
                middle: [],
                right: ['zoomIn', 'zoomOut', 'close'],
            },
        },
    });
}
