/**
 * Select customizado — dropdown no desktop e bottom sheet no mobile.
 *
 * Cada wrapper `[data-custom-select]` contém o <select> nativo (fonte da
 * verdade, usado na submissão e nos eventos de `change`). O JS esconde o
 * nativo, cria um botão trigger + lista de opções e mantém o valor sincronizado.
 */
const MOBILE_QUERY = '(max-width: 639px)';

const isMobile = () => window.matchMedia(MOBILE_QUERY).matches;
const uid = () => `cs-${Math.random().toString(36).slice(2, 9)}`;

const CHEVRON = `
    <svg class="cs-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
    </svg>`;

const CHECK = `
    <svg class="cs-option-check" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
        aria-hidden="true"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

const buildOptions = (select) =>
    Array.from(select.options).map((option, index) => ({
        value: option.value,
        label: option.textContent,
        index,
    }));

const highlightOption = (option) => {
    option.closest('.cs-menu, .cs-sheet-list')?.querySelectorAll('.is-highlighted')
        .forEach((el) => el.classList.remove('is-highlighted'));
    option.classList.add('is-highlighted');
};

const enhance = (wrapper) => {
    const select = wrapper.querySelector('select');
    const label = wrapper.dataset.csLabel || select.name || '';
    const placeholder = wrapper.dataset.csPlaceholder || 'Selecione...';
    const options = buildOptions(select);

    /* ---------- Trigger ---------- */
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'cs-trigger';
    trigger.id = select.id ? `${select.id}-cs` : uid();
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-autocomplete', 'none');
    if (label) trigger.setAttribute('aria-label', label);
    if (select.getAttribute('aria-describedby')) {
        trigger.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
    }

    const associatedLabel = select.id ? document.querySelector(`label[for="${select.id}"]`) : null;
    if (associatedLabel) {
        associatedLabel.setAttribute('for', trigger.id);
        associatedLabel.addEventListener('click', (event) => {
            event.preventDefault();
            trigger.focus();
            open();
        });
    }

    const value = document.createElement('span');
    value.className = 'cs-value';

    const chevronShell = document.createElement('span');
    chevronShell.innerHTML = CHEVRON;

    trigger.append(value, chevronShell);

    /* ---------- Menu desktop ---------- */
    const menu = document.createElement('ul');
    menu.className = 'cs-menu hidden';
    menu.setAttribute('role', 'listbox');
    menu.setAttribute('aria-label', label || undefined);
    const menuId = uid();
    menu.id = menuId;

    options.forEach((option) => {
        const li = document.createElement('li');
        li.id = `${menuId}-opt-${option.index}`;
        li.className = 'cs-option';
        li.dataset.value = option.value;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', String(option.value === select.value));
        li.textContent = option.label;

        const check = document.createElement('span');
        check.innerHTML = CHECK;
        check.hidden = !(option.value === select.value);
        li.appendChild(check);

        li.addEventListener('click', () => {
            pick(option);
            closeMenu();
        });
        li.addEventListener('mousemove', () => setHighlighted(li));
        menu.appendChild(li);
    });

    document.body.appendChild(menu);

    const menuItems = () => Array.from(menu.children);
    const sheetItems = () => Array.from(sheetList?.children ?? []);

    const renderTriggerValue = () => {
        const option = select.options[select.selectedIndex];
        value.textContent = option && option.value !== '' ? option.textContent : placeholder;
        value.classList.toggle('is-empty', !option || option.value === '');
    };

    const syncSelected = () => {
        menuItems().forEach((li) => {
            const isSelected = li.dataset.value === select.value;
            li.classList.toggle('is-selected', isSelected);
            li.setAttribute('aria-selected', String(isSelected));
            const check = li.querySelector('.cs-option-check');
            if (check) check.hidden = !isSelected;
        });
        sheetItems().forEach((li) => {
            const isSelected = li.dataset.value === select.value;
            li.classList.toggle('is-selected', isSelected);
            li.setAttribute('aria-selected', String(isSelected));
        });
    };

    const setHighlighted = (li) => {
        if (!li) return;
        highlightOption(li);
        trigger.setAttribute('aria-activedescendant', li.id);
    };

    const highlightedIndex = () => {
        const current = menuItems().findIndex((li) => li.classList.contains('is-highlighted'));
        return current === -1 ? select.selectedIndex : current;
    };

    const highlightByIndex = (delta) => {
        const items = menuItems();
        if (!items.length) return;
        const current = highlightedIndex();
        const length = items.length;
        const next = ((current + delta) % length + length) % length;
        setHighlighted(items[next]);
    };

    const measureMenuHeight = () => {
        const previous = menu.classList.contains('hidden');
        const hadWidth = menu.style.width;
        menu.classList.remove('hidden');
        menu.style.visibility = 'hidden';
        menu.style.width = `${Math.max(trigger.getBoundingClientRect().width, 220)}px`;
        const height = menu.scrollHeight;
        menu.style.visibility = '';
        if (previous) menu.classList.add('hidden');
        menu.style.width = hadWidth;
        return height;
    };

    const positionMenu = () => {
        const rect = trigger.getBoundingClientRect();
        const gap = 6;
        const maxHeight = 256;
        const height = Math.min(measureMenuHeight(), maxHeight);
        const below = window.innerHeight - rect.bottom;
        const openUp = below < height + gap && rect.top > height + gap;

        menu.style.width = `${Math.max(rect.width, 220)}px`;
        menu.style.left = `${rect.left}px`;
        if (openUp) {
            menu.style.top = `${Math.max(8, rect.top - height - gap)}px`;
            menu.style.maxHeight = `${Math.max(120, rect.top - gap - 8)}px`;
        } else {
            menu.style.top = `${rect.bottom + gap}px`;
            menu.style.maxHeight = `${Math.max(120, below - gap)}px`;
        }
    };

    const openMenu = () => {
        syncSelected();
        setHighlighted(menuItems()[select.selectedIndex] || menuItems()[0]);
        positionMenu();
        menu.classList.remove('hidden');
        trigger.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
    };

    const closeMenu = () => {
        menu.classList.add('hidden');
        trigger.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.removeAttribute('aria-activedescendant');
    };

    /* ---------- Bottom sheet (mobile) ---------- */
    const backdrop = document.createElement('div');
    backdrop.className = 'cs-backdrop hidden';

    const sheet = document.createElement('div');
    sheet.className = 'cs-sheet';
    sheet.setAttribute('role', 'dialog');
    sheet.setAttribute('aria-modal', 'true');
    sheet.setAttribute('aria-label', label || undefined);

    const sheetTitleId = `${menuId}-sheet-title`;
    const sheetTitle = document.createElement('p');
    sheetTitle.className = 'cs-sheet-title';
    sheetTitle.id = sheetTitleId;
    sheetTitle.textContent = label;

    const sheetClose = document.createElement('button');
    sheetClose.type = 'button';
    sheetClose.className = 'btn btn-ghost btn-sm';
    sheetClose.textContent = 'Fechar';
    sheetClose.setAttribute('aria-label', 'Fechar seleção');

    const sheetHead = document.createElement('div');
    sheetHead.className = 'cs-sheet-head';
    sheetHead.append(sheetTitle, sheetClose);

    const sheetHandle = document.createElement('div');
    sheetHandle.className = 'cs-sheet-handle';

    const sheetList = document.createElement('div');
    sheetList.className = 'cs-sheet-list';
    sheetList.setAttribute('role', 'listbox');
    sheetList.setAttribute('aria-labelledby', sheetTitleId);

    options.forEach((option) => {
        const li = document.createElement('li');
        li.className = 'cs-sheet-option';
        li.dataset.value = option.value;
        li.textContent = option.label;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        li.addEventListener('click', () => {
            pick(option);
            closeSheet();
        });
        sheetList.appendChild(li);
    });

    sheet.append(sheetHandle, sheetHead, sheetList);
    document.body.append(backdrop, sheet);

    const openSheet = () => {
        syncSelected();
        backdrop.classList.remove('hidden');
        requestAnimationFrame(() => sheet.classList.add('is-open'));
        document.body.style.overflow = 'hidden';
        const selected = select.selectedIndex;
        const target = sheetItems()[selected];
        if (target) target.scrollIntoView({ block: 'nearest' });
    };

    const closeSheet = () => {
        sheet.classList.remove('is-open');
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    };

    const dismiss = () => (isMobile() ? closeSheet() : closeMenu());
    const open = () => {
        syncSelected();
        if (isMobile()) {
            openSheet();
        } else {
            openMenu();
        }
    };

    /* ---------- Ação compartilhada ---------- */
    const pick = (option) => {
        select.value = option.value;
        renderTriggerValue();
        syncSelected();
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    /* ---------- Eventos ---------- */
    trigger.addEventListener('click', () => {
        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        expanded ? dismiss() : open();
    });

    trigger.addEventListener('keydown', (event) => {
        const expanded = trigger.getAttribute('aria-expanded') === 'true';

        if (!expanded) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                open();
            }
            return;
        }

        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                dismiss();
                break;
            case 'ArrowDown':
                event.preventDefault();
                highlightByIndex(1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                highlightByIndex(-1);
                break;
            case 'Home':
                event.preventDefault();
                setHighlighted(menuItems()[0]);
                break;
            case 'End':
                event.preventDefault();
                setHighlighted(menuItems()[menuItems().length - 1]);
                break;
            case 'Enter':
            case ' ':
            case 'Tab': {
                const active = menuItems().find((li) => li.classList.contains('is-highlighted'));
                dismiss();
                if (event.key !== 'Tab' && active) {
                    event.preventDefault();
                    const option = options.find((opt) => opt.value === active.dataset.value);
                    if (option) pick(option);
                }
                break;
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target === backdrop) {
            closeSheet();
            return;
        }
        if (
            !event.target.closest('.cs-menu') &&
            !event.target.closest('.cs-trigger') &&
            !event.target.closest('.cs-sheet')
        ) {
            closeMenu();
        }
    });

    window.addEventListener('scroll', () => closeMenu(), { passive: true });
    window.addEventListener('resize', () => closeMenu());

    sheetClose.addEventListener('click', closeSheet);

    select.addEventListener('change', renderTriggerValue);

    wrapper.insertBefore(trigger, select);
    select.classList.add('hidden');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    wrapper.dataset.csReady = '1';

    renderTriggerValue();
};

export const initCustomSelects = (scope = document) => {
    scope.querySelectorAll('[data-custom-select]:not([data-cs-ready])').forEach(enhance);
};