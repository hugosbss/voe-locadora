/**
 * Toasts — notificações efêmeras (success | info | error).
 *
 * A pilha de mensagens da sessão é serializada pelo Blade em
 * <script type="application/json" data-toast-queue> e anunciada aqui após o
 * primeiro paint. A mesma API pode ser usada por outros módulos via
 * `toast(type, message)`.
 */

export const DURATIONS = {
    success: 3500,
    info: 3500,
    error: 5000,
};

const MAX_STACK = 4;
const EXIT_MS = 180;
const BURST_MS = 1200;

const ICONS = {
    success:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" /></svg>',
    error:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>',
};

const REDUCES_MOTION = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const HOVERABLE = () => window.matchMedia('(hover: hover)').matches;

const state = {
    region: null,
    active: [],
    pending: [],
    recent: new Map(),
};

const types = ['success', 'error', 'info'];

const safeType = (type) => (types.includes(type) ? type : 'info');

const resolveRegion = () => {
    if (!state.region) {
        state.region = document.querySelector('[data-toaster]');
    }
    return state.region;
};

const createToastEl = ({ type, message }) => {
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.setAttribute('data-toast', '');

    el.innerHTML = `
        <span class="toast-icon" aria-hidden="true">${ICONS[type]}</span>
        <p class="toast-message"></p>
        <button type="button" class="toast-close" aria-label="Fechar notificação">${ICONS.close}</button>
        <span class="toast-progress" aria-hidden="true"></span>`;

    const msg = el.querySelector('.toast-message');
    msg.textContent = String(message).trim();

    return el;
};

const removeToast = (entry, restoreFocus) => {
    const index = state.active.indexOf(entry);
    if (index !== -1) {
        state.active.splice(index, 1);
    }

    entry.el.remove();

    if (restoreFocus) {
        const next = state.active.find((item) => item.el?.isConnected);
        if (next?.el) {
            next.el.querySelector('.toast-close')?.focus();
        } else {
            state.region?.focus();
        }
    }

    drainPending();
};

const dismiss = (entry, { fromTimer = false } = {}) => {
    if (entry.removing) {
        return;
    }
    entry.removing = true;

    clearTimeout(entry.timer);
    entry.el.classList.add('is-leaving');
    entry.el.classList.remove('is-paused');

    const restoreFocus = entry.el.contains(document.activeElement);
    window.setTimeout(
        () => removeToast(entry, restoreFocus),
        REDUCES_MOTION() || fromTimer ? 0 : EXIT_MS,
    );
};

const pause = (entry) => {
    if (entry.paused || entry.removing) {
        return;
    }
    entry.remaining -= Date.now() - entry.startedAt;
    entry.paused = true;
    clearTimeout(entry.timer);
    entry.el.classList.add('is-paused');
};

const resume = (entry) => {
    if (!entry.paused || entry.removing) {
        return;
    }
    entry.paused = false;
    startTimer(entry);
};

const startTimer = (entry) => {
    entry.startedAt = Date.now();
    entry.timer = window.setTimeout(() => dismiss(entry, { fromTimer: true }), entry.remaining);
};

const bindPauseOnInteraction = (entry) => {
    const el = entry.el;
    const flags = { over: false, focus: false };

    const sync = () => {
        if (flags.over || flags.focus) {
            pause(entry);
        } else {
            resume(entry);
        }
    };

    if (HOVERABLE()) {
        el.addEventListener('mouseenter', () => {
            flags.over = true;
            sync();
        });
        el.addEventListener('mouseleave', () => {
            flags.over = false;
            sync();
        });
    }

    el.addEventListener('focusin', () => {
        flags.focus = true;
        sync();
    });
    el.addEventListener('focusout', () => {
        flags.focus = false;
        sync();
    });

    el.addEventListener('click', (event) => {
        if (event.target.closest('.toast-close')) {
            dismiss(entry);
        }
    });
};

const drainPending = () => {
    while (state.active.length < MAX_STACK && state.pending.length > 0) {
        const item = state.pending.shift();
        show(item.type, item.message, item.duration, item.key);
    }
};

/**
 * Exibe uma notificação. Mensagens idênticas em curto intervalo são
 * suprimidas (deduplicação por tipo + texto).
 */
export const toast = (type = 'info', message = '', { duration } = {}) => {
    const region = resolveRegion();
    if (!region) {
        return;
    }

    const resolvedType = safeType(type);
    const text = String(message).trim();

    if (!text) {
        return;
    }

    const normalized = duration || DURATIONS[resolvedType];
    const key = `${resolvedType}\u0000${text}`;
    const now = Date.now();

    if (state.active.some((entry) => entry.key === key)) {
        return;
    }

    for (const [recentKey, at] of state.recent) {
        if (now - at > BURST_MS) {
            state.recent.delete(recentKey);
        }
    }
    if (state.recent.has(key)) {
        return;
    }
    state.recent.set(key, now);

    if (state.active.length >= MAX_STACK) {
        state.pending.push({ type: resolvedType, message: text, duration: normalized, key });
        return;
    }

    show(resolvedType, text, normalized, key);
};

const show = (type, message, duration, key) => {
    const region = state.region;

    const el = createToastEl({ type, message });
    el.style.setProperty('--toast-duration', `${duration}ms`);

    const entry = {
        key,
        el,
        timer: 0,
        remaining: duration,
        startedAt: 0,
        paused: false,
        removing: false,
    };

    region.appendChild(el);
    state.active.push(entry);
    bindPauseOnInteraction(entry);
    startTimer(entry);
};

/**
 * Inicializa a região de toasts e anuncia mensagens da sessão (flash)
 * serializadas no HTML pelo Blade.
 */
export const initToasts = (root = document) => {
    if (!resolveRegion()) {
        return;
    }

    const queueEl = root.querySelector('script[type="application/json"][data-toast-queue]');
    if (queueEl) {
        let queue = [];
        try {
            queue = JSON.parse(queueEl.textContent);
        } catch {
            queue = [];
        }
        queueEl.remove();

        requestAnimationFrame(() => {
            if (REDUCES_MOTION()) {
                queue.forEach((item) => toast(item?.type, item?.message));
            } else {
                queue.forEach((item, index) => {
                    window.setTimeout(() => toast(item?.type, item?.message), index * 60);
                });
            }
        });
    }
};