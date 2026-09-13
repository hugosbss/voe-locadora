/* ============================================================================
   VCA — Cadastro de Clientes (Protótipos estáticos)
   Interações mínimas de demonstração (copy, toggle de senha e menu mobile).
   ========================================================================== */

(function () {
    'use strict';

    var copiedButtons = new WeakMap();

    function sendStatus(targetId, text) {
        var status = document.getElementById(targetId);
        if (status) {
            status.textContent = text;
        }
    }

    function flashCopied(button, timeout) {
        var original = button.getAttribute('data-original-label') || button.textContent.trim();
        if (!button.hasAttribute('data-original-label')) {
            button.setAttribute('data-original-label', original);
        }
        button.classList.add('is-copied');
        button.setAttribute('aria-live', 'polite');
        button.textContent = 'Copiado!';

        if (copiedButtons.get(button)) {
            clearTimeout(copiedButtons.get(button));
        }

        copiedButtons.set(button, setTimeout(function () {
            button.classList.remove('is-copied');
            button.textContent = original;
        }, timeout || 2200));
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
        } catch (e) {
            /* sem clipboard — apenas demonstração */
        }
        document.body.removeChild(textarea);
        return Promise.resolve();
    }

    document.addEventListener('click', function (event) {
        var copyBtn = event.target.closest('[data-js-copy]');
        if (copyBtn) {
            var url = copyBtn.getAttribute('data-copy-url');
            var statusId = copyBtn.getAttribute('data-copy-status');
            if (url) {
                copyToClipboard(url).then(function () {
                    flashCopied(copyBtn);
                    if (statusId) {
                        sendStatus(statusId, 'Link copiado para a área de transferência.');
                    }
                });
            }
            return;
        }

        var shareBtn = event.target.closest('[data-js-share]');
        if (shareBtn) {
            var shareData = {
                title: shareBtn.getAttribute('data-share-title') || 'Cadastro de cliente',
                text: shareBtn.getAttribute('data-share-text') || '',
                url: shareBtn.getAttribute('data-share-url') || window.location.href,
            };
            if (navigator.share) {
                navigator.share(shareData).catch(function () {});
            } else {
                flashCopied(shareBtn, 1500);
            }
            return;
        }

        var toggle = event.target.closest('[data-password-toggle]');
        if (toggle) {
            var input = document.getElementById(toggle.getAttribute('data-password-toggle'));
            if (input) {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                toggle.querySelector('[data-icon-eye]').classList.toggle('is-hidden', show);
                toggle.querySelector('[data-icon-eye-off]').classList.toggle('is-hidden', !show);
            }
            return;
        }

        var menuOpen = event.target.closest('[data-mobile-nav-open]');
        if (menuOpen) {
            document.body.classList.add('mobile-nav-open');
            return;
        }

        var close = event.target.closest('[data-mobile-nav-close]') || event.target.closest('body.mobile-nav-open::after');
        if (close) {
            document.body.classList.remove('mobile-nav-open');
        }
    });

    document.body.addEventListener('click', function (event) {
        if (document.body.classList.contains('mobile-nav-open')) {
            var insideSidebar = event.target.closest('.admin-sidebar');
            if (!insideSidebar) {
                document.body.classList.remove('mobile-nav-open');
            }
        }
    });
})();