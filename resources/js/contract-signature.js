/**
 * Página pública de assinatura do contrato.
 *
 * O traço do canvas é enviado como data URL PNG no campo oculto
 * `contract_signature` e revalidado no servidor (ValidSignatureData +
 * ContractStorageService), que nunca confia no navegador. O botão só é
 * liberado com assinatura desenhada e aceite dos termos; o envio é um POST
 * normal, com os erros exibidos pelo Blade.
 */
export const initContractSignature = (root = document) => {
    const form = root.querySelector('[data-signature-form]');

    if (!form) {
        return;
    }

    const canvas = form.querySelector('#signature-canvas');
    const hidden = form.querySelector('#contract_signature');
    const clearBtn = form.querySelector('#signature-clear');
    const hint = form.querySelector('#signature-hint');
    const wrap = form.querySelector('#signature-canvas-wrap');
    const submitBtn = form.querySelector('[data-signature-submit]');
    const accepted = form.querySelector('#contract_accepted');

    if (!canvas || !hidden) {
        return;
    }

    const DEFAULT_HEIGHT = 200;
    const dpr = Math.max(window.devicePixelRatio || 1, 1);

    let ctx = null;
    let drawing = false;
    let hasInk = false;
    let initialized = false;
    let lastX = 0;
    let lastY = 0;

    const isReady = () => (hidden.value !== '' && (accepted?.checked ?? false));

    const updateSubmitState = () => {
        if (submitBtn) {
            submitBtn.disabled = !isReady();
        }
    };

    const showError = (message) => {
        const el = form.querySelector('#contract_signature-error');

        if (el) {
            el.textContent = message;
            el.hidden = false;
        }

        wrap?.classList.add('has-error');
    };

    const clearError = () => {
        const el = form.querySelector('#contract_signature-error');

        if (el) {
            el.textContent = '';
            el.hidden = true;
        }

        wrap?.classList.remove('has-error');
    };

    const initCanvas = () => {
        initialized = true;

        const rect = canvas.getBoundingClientRect();
        const cssWidth = Math.max(rect.width || canvas.clientWidth || 300, 1);

        canvas.width = Math.round(cssWidth * dpr);
        canvas.height = Math.round(DEFAULT_HEIGHT * dpr);

        ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#111827';
        ctx.lineWidth = Math.max(2.5, 2.5 * dpr);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    };

    const resizeCanvas = () => {
        if (!initialized) {
            initCanvas();

            return;
        }

        if (hasInk) {
            return; // preserva o traço já desenhado
        }

        initCanvas();
    };

    const toCanvasCoords = (event) => {
        const rect = canvas.getBoundingClientRect();

        return [
            (event.clientX - rect.left) * (canvas.width / (rect.width || 1)),
            (event.clientY - rect.top) * (canvas.height / (rect.height || 1)),
        ];
    };

    const detectInk = () => {
        if (!ctx) {
            return false;
        }

        const image = ctx.getImageData(0, 0, canvas.width, canvas.height).data;

        for (let i = 0; i < image.length; i += 4) {
            if (image[i + 3] > 0 && (image[i] < 250 || image[i + 1] < 250 || image[i + 2] < 250)) {
                return true;
            }
        }

        return false;
    };

    const syncHidden = () => {
        hasInk = detectInk();
        hidden.value = hasInk ? canvas.toDataURL('image/png') : '';

        updateSubmitState();
    };

    const start = (event) => {
        if (event.button !== undefined && event.button !== 0) return;

        event.preventDefault();
        if (!initialized) initCanvas();

        canvas.setPointerCapture?.(event.pointerId);
        drawing = true;
        [lastX, lastY] = toCanvasCoords(event);
        hint?.classList.add('hidden');
    };

    const move = (event) => {
        if (!drawing || !ctx) return;

        event.preventDefault();
        const [x, y] = toCanvasCoords(event);

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.stroke();
        lastX = x;
        lastY = y;
    };

    const end = (event) => {
        if (!drawing) return;

        drawing = false;
        canvas.releasePointerCapture?.(event.pointerId);
        syncHidden();
        clearError();
    };

    const clear = () => {
        drawing = false;
        hasInk = false;
        lastX = 0;
        lastY = 0;

        if (ctx) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        hidden.value = '';
        hint?.classList.remove('hidden');
        clearError();
        updateSubmitState();
    };

    canvas.addEventListener('pointerdown', start);
    canvas.addEventListener('pointermove', move);
    canvas.addEventListener('pointerup', end);
    canvas.addEventListener('pointercancel', end);
    canvas.addEventListener('pointerleave', end);
    clearBtn?.addEventListener('click', clear);
    accepted?.addEventListener('change', updateSubmitState);
    window.addEventListener('resize', resizeCanvas);

    form.addEventListener('submit', (event) => {
        if (isReady()) {
            return;
        }

        // Bloqueio apenas de interface: o servidor valida tudo de novo.
        event.preventDefault();
        showError(
            accepted?.checked
                ? 'Desenhe sua assinatura antes de continuar.'
                : 'Desenhe sua assinatura e confirme que aceita o contrato.',
        );
    });

    initCanvas();
    updateSubmitState();
};
