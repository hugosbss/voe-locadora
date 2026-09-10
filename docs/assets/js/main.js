/* ============================================================================
   Protótipo — interações (sem validação real)
   ========================================================================== */
(function () {
    'use strict';

    var MOBILE_QUERY = '(max-width: 639px)';
    var isMobile = function () { return window.matchMedia(MOBILE_QUERY).matches; };
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var uid = function () { return 'cs-' + Math.random().toString(36).slice(2, 9); };

    var CHEVRON =
        '<svg class="cs-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
        '<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>';
    var CHECK =
        '<svg class="cs-option-check" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' +
        '<path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    function buildOptions(select) {
        return Array.prototype.map.call(select.options, function (option, index) {
            return { value: option.value, label: option.textContent, index: index };
        });
    }

    function highlightOption(option) {
        var list = option.closest('.cs-menu, .cs-sheet-list');
        if (list) {
            Array.prototype.forEach.call(list.querySelectorAll('.is-highlighted'), function (el) {
                el.classList.remove('is-highlighted');
            });
        }
        option.classList.add('is-highlighted');
    }

    /* ---------- Select customizado ---------- */
    function enhanceSelect(wrapper) {
        var select = wrapper.querySelector('select');
        if (select.__csBound) return;
        select.__csBound = true;

        var label = wrapper.getAttribute('data-cs-label') || select.name || '';
        var placeholder = wrapper.getAttribute('data-cs-placeholder') || 'Selecione...';
        var options = buildOptions(select);

        select.addEventListener('change', function () {
            renderValue();
            syncSelected();
        });

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'cs-trigger';
        trigger.id = select.id ? select.id + '-cs' : uid();
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-autocomplete', 'none');
        if (label) trigger.setAttribute('aria-label', label);

        var valueSpan = document.createElement('span');
        valueSpan.className = 'cs-value';
        var chevronShell = document.createElement('span');
        chevronShell.innerHTML = CHEVRON;
        trigger.appendChild(valueSpan);
        trigger.appendChild(chevronShell);

        var associatedLabel = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        if (associatedLabel) {
            associatedLabel.setAttribute('for', trigger.id);
            associatedLabel.addEventListener('click', function (event) {
                event.preventDefault();
                trigger.focus();
                openSelect();
            });
        }

        /* Desktop */
        var menu = document.createElement('ul');
        menu.className = 'cs-menu hidden';
        menu.setAttribute('role', 'listbox');
        menu.setAttribute('aria-label', label || '');
        var menuId = uid();
        menu.id = menuId;

        options.forEach(function (option) {
            var li = document.createElement('li');
            li.id = menuId + '-opt-' + option.index;
            li.className = 'cs-option';
            li.setAttribute('role', 'option');
            li.setAttribute('data-value', option.value);
            li.setAttribute('aria-selected', option.value === select.value ? 'true' : 'false');
            li.textContent = option.label;
            var check = document.createElement('span');
            check.innerHTML = CHECK;
            check.hidden = !(option.value === select.value);
            li.appendChild(check);
            li.addEventListener('click', function () { pick(option); closeMenu(); });
            li.addEventListener('mousemove', function () { setHighlighted(li); });
            menu.appendChild(li);
        });
        document.body.appendChild(menu);

        var menuItems = function () { return Array.prototype.slice.call(menu.children); };
        var sheetRef = null;

        function renderValue() {
            var option = select.options[select.selectedIndex];
            valueSpan.textContent = option && option.value !== '' ? option.textContent : placeholder;
            valueSpan.classList.toggle('is-empty', !option || option.value === '');
        }

        function syncSelected() {
            menuItems().forEach(function (li) {
                var isSel = li.getAttribute('data-value') === select.value;
                li.classList.toggle('is-selected', isSel);
                li.setAttribute('aria-selected', isSel ? 'true' : 'false');
                var check = li.querySelector('.cs-option-check');
                if (check) check.hidden = !isSel;
            });
            sheetRef && Array.prototype.forEach.call(sheetRef.children, function (li) {
                var isSel = li.getAttribute('data-value') === select.value;
                li.classList.toggle('is-selected', isSel);
                li.setAttribute('aria-selected', isSel ? 'true' : 'false');
            });
        }

        function setHighlighted(li) {
            if (!li) return;
            highlightOption(li);
            trigger.setAttribute('aria-activedescendant', li.id);
        }

        function currentIndex() {
            var items = menuItems();
            var current = items.findIndex(function (li) { return li.classList.contains('is-highlighted'); });
            return current === -1 ? select.selectedIndex : current;
        }

        function moveHighlight(delta) {
            var items = menuItems();
            if (!items.length) return;
            var len = items.length;
            var next = ((currentIndex() + delta) % len + len) % len;
            setHighlighted(items[next]);
        }

        function measureMenu() {
            var wasHidden = menu.classList.contains('hidden');
            var prevWidth = menu.style.width;
            menu.classList.remove('hidden');
            menu.style.visibility = 'hidden';
            menu.style.width = Math.max(trigger.getBoundingClientRect().width, 220) + 'px';
            var height = menu.scrollHeight;
            menu.style.visibility = '';
            if (wasHidden) menu.classList.add('hidden');
            menu.style.width = prevWidth;
            return height;
        }

        function positionMenu() {
            var rect = trigger.getBoundingClientRect();
            var gap = 6, maxH = 256;
            var height = Math.min(measureMenu(), maxH);
            var below = window.innerHeight - rect.bottom;
            var openUp = below < height + gap && rect.top > height + gap;
            menu.style.width = Math.max(rect.width, 220) + 'px';
            menu.style.left = rect.left + 'px';
            if (openUp) {
                menu.style.top = Math.max(8, rect.top - height - gap) + 'px';
                menu.style.maxHeight = Math.max(120, rect.top - gap - 8) + 'px';
            } else {
                menu.style.top = rect.bottom + gap + 'px';
                menu.style.maxHeight = Math.max(120, below - gap) + 'px';
            }
        }

        function openMenu() {
            syncSelected();
            setHighlighted(menuItems()[select.selectedIndex] || menuItems()[0]);
            positionMenu();
            menu.classList.remove('hidden');
            trigger.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
            menu.classList.add('hidden');
            trigger.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.removeAttribute('aria-activedescendant');
        }

        /* Bottom sheet (mobile) */
        var backdrop = document.createElement('div');
        backdrop.className = 'cs-backdrop hidden';
        var sheet = document.createElement('div');
        sheet.className = 'cs-sheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.setAttribute('aria-label', label || '');

        var titleId = menuId + '-sheet-title';
        var sheetTitle = document.createElement('p');
        sheetTitle.className = 'cs-sheet-title';
        sheetTitle.id = titleId;
        sheetTitle.textContent = label;

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn btn-ghost btn-sm-cs';
        closeBtn.textContent = 'Fechar';
        closeBtn.setAttribute('aria-label', 'Fechar seleção');

        var sheetHead = document.createElement('div');
        sheetHead.className = 'cs-sheet-head';
        sheetHead.appendChild(sheetTitle);
        sheetHead.appendChild(closeBtn);

        var handle = document.createElement('div');
        handle.className = 'cs-sheet-handle';

        var sheetList = document.createElement('div');
        sheetList.className = 'cs-sheet-list';
        sheetList.setAttribute('role', 'listbox');
        sheetList.setAttribute('aria-labelledby', titleId);
        sheetRef = sheetList;

        options.forEach(function (option) {
            var li = document.createElement('div');
            li.className = 'cs-sheet-option';
            li.setAttribute('role', 'option');
            li.setAttribute('data-value', option.value);
            li.setAttribute('aria-selected', 'false');
            li.textContent = option.label;
            li.addEventListener('click', function () { pick(option); closeSheet(); });
            sheetList.appendChild(li);
        });

        sheet.appendChild(handle);
        sheet.appendChild(sheetHead);
        sheet.appendChild(sheetList);
        document.body.appendChild(backdrop);
        document.body.appendChild(sheet);

        function openSheet() {
            syncSelected();
            backdrop.classList.remove('hidden');
            requestAnimationFrame(function () { sheet.classList.add('is-open'); });
            document.body.style.overflow = 'hidden';
            var target = sheetList.children[select.selectedIndex];
            if (target) target.scrollIntoView({ block: 'nearest' });
        }

        function closeSheet() {
            sheet.classList.remove('is-open');
            backdrop.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function pick(option) {
            select.value = option.value;
            renderValue();
            syncSelected();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function openSelect() {
            syncSelected();
            if (isMobile()) openSheet();
            else openMenu();
        }

        function dismiss() {
            if (isMobile()) closeSheet();
            else closeMenu();
        }

        trigger.addEventListener('click', function () {
            if (trigger.getAttribute('aria-expanded') === 'true') dismiss();
            else openSelect();
        });

        trigger.addEventListener('keydown', function (event) {
            var expanded = trigger.getAttribute('aria-expanded') === 'true';
            if (!expanded) {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(event.key) !== -1) {
                    event.preventDefault();
                    openSelect();
                }
                return;
            }
            switch (event.key) {
                case 'Escape': event.preventDefault(); dismiss(); break;
                case 'ArrowDown': event.preventDefault(); moveHighlight(1); break;
                case 'ArrowUp': event.preventDefault(); moveHighlight(-1); break;
                case 'Home': event.preventDefault(); setHighlighted(menuItems()[0]); break;
                case 'End': event.preventDefault(); setHighlighted(menuItems()[menuItems().length - 1]); break;
                case 'Enter':
                case ' ':
                case 'Tab': {
                    var active = menuItems().find(function (li) { return li.classList.contains('is-highlighted'); });
                    dismiss();
                    if (event.key !== 'Tab' && active) {
                        event.preventDefault();
                        var found = options.find(function (opt) { return opt.value === active.getAttribute('data-value'); });
                        if (found) pick(found);
                    }
                    break;
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target === backdrop) { closeSheet(); return; }
            var t = event.target;
            if (!t.closest('.cs-menu') && !t.closest('.cs-trigger') && !t.closest('.cs-sheet')) closeMenu();
        });

        window.addEventListener('scroll', closeMenu, { passive: true });
        window.addEventListener('resize', closeMenu);
        closeBtn.addEventListener('click', closeSheet);

        /* Esconde o select nativo (mantém como fonte do valor) */
        wrapper.insertBefore(trigger, select);
        select.classList.add('hidden');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        renderValue();
    }

    function initCustomSelects() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-custom-select]'), enhanceSelect);
    }

    /* ---------- Formulário em etapas (protótipo) ---------- */
    function initRegistration() {
        var form = document.getElementById('registration-form');
        if (!form) return;

        var total = parseInt(form.getAttribute('data-total'), 10) || 6;
        var current = 1;

        var dots = Array.prototype.slice.call(document.querySelectorAll('.step-dot'));
        var labels = Array.prototype.slice.call(document.querySelectorAll('.step-label'));
        var panels = Array.prototype.slice.call(document.querySelectorAll('.step-panel'));
        var countEl = document.getElementById('step-count');
        var titleEl = document.getElementById('step-title');
        var fillEl = document.getElementById('stepbar-fill');
        var counterEl = document.getElementById('step-counter');
        var prevBtn = document.getElementById('prev-btn');
        var nextBtn = document.getElementById('next-btn');

        function goto(step) {
            if (step < 1 || step > total) return;
            current = step;
            panels.forEach(function (panel, index) {
                panel.classList.toggle('hidden', index + 1 !== current);
            });
            dots.forEach(function (dot, index) {
                var n = index + 1;
                dot.setAttribute('data-active', n === current ? 'true' : 'false');
                dot.setAttribute('data-done', n < current ? 'true' : 'false');
                dot.textContent = n < current ? '✓' : String(n);
            });
            labels.forEach(function (label, index) {
                var n = index + 1;
                label.setAttribute('data-active', n === current ? 'true' : 'false');
                label.setAttribute('data-done', n < current ? 'true' : 'false');
            });
            if (countEl) countEl.textContent = 'Etapa ' + current + ' de ' + total;
            if (fillEl) fillEl.style.width = (current / total) * 100 + '%';
            if (counterEl) counterEl.textContent = current + ' / ' + total;
            if (titleEl && panels[current - 1]) {
                titleEl.textContent = panels[current - 1].getAttribute('data-title') || 'Etapa ' + current;
            }
            if (prevBtn) prevBtn.classList.toggle('invisible', current === 1);
            if (nextBtn) nextBtn.classList.toggle('invisible', current === total);
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        }

        prevBtn && prevBtn.addEventListener('click', function () { goto(current - 1); });
        nextBtn && nextBtn.addEventListener('click', function () { goto(current + 1); });

        maskInputs(form);
        initUploads(form);
        initSummary(form);
        initCepMock();
        goto(1);
    }

    /* ---------- Máscaras simples (só exibição) ---------- */
    function maskInputs(scope) {
        var masks = {
            cpf: function (v) {
                return v.replace(/\D/g, '').slice(0, 11)
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
            },
            phone: function (v) {
                var d = v.replace(/\D/g, '').slice(0, 11);
                return d.length > 10
                    ? d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
                    : d.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            },
            cep: function (v) { return v.replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'); }
        };
        Array.prototype.forEach.call(scope.querySelectorAll('[data-mask]'), function (input) {
            var fn = masks[input.getAttribute('data-mask')];
            if (!fn) return;
            input.addEventListener('input', function () {
                var next = fn(input.value);
                if (input.value !== next) input.value = next;
            });
        });
    }

    /* ---------- Uploads (pré-visualização) ---------- */
    function initUploads(form) {
        function showPreview(doc, file) {
            var wrap = document.querySelector('[data-preview="' + doc + '"]');
            if (!wrap || !file) return;
            var placeholder = wrap.querySelector('.preview-empty-icon');
            var image = wrap.querySelector('.preview-image');
            var reader = new FileReader();
            reader.onload = function () {
                placeholder.classList.add('hidden');
                image.classList.remove('hidden');
                image.src = reader.result;
                var card = wrap.closest('.upload-card');
                if (card) card.classList.add('is-filled');
                var nameEl = card && card.querySelector('.file-name');
                if (nameEl) { nameEl.classList.remove('hidden'); nameEl.textContent = file.name; }
            };
            reader.readAsDataURL(file);
        }

        Array.prototype.forEach.call(document.querySelectorAll('.document-input'), function (input) {
            input.addEventListener('change', function () { showPreview(input.getAttribute('data-doc'), input.files[0]); });
        });

        Array.prototype.forEach.call(document.querySelectorAll('.gallery-input'), function (input) {
            input.addEventListener('change', function () {
                var doc = input.getAttribute('data-doc');
                var target = form.elements[doc + '_file'];
                if (target && input.files[0]) {
                    var transfer = new DataTransfer();
                    transfer.items.add(input.files[0]);
                    target.files = transfer.files;
                    showPreview(doc, input.files[0]);
                    input.value = '';
                }
            });
        });

        Array.prototype.forEach.call(document.querySelectorAll('.remove-preview'), function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                var doc = btn.getAttribute('data-doc');
                var target = form.elements[doc + '_file'];
                if (target) target.value = '';
                var wrap = document.querySelector('[data-preview="' + doc + '"]');
                var card = wrap && wrap.closest('.upload-card');
                var image = wrap && wrap.querySelector('.preview-image');
                var placeholder = wrap && wrap.querySelector('.preview-empty-icon');
                if (image) { image.classList.add('hidden'); image.removeAttribute('src'); }
                if (placeholder) placeholder.classList.remove('hidden');
                if (card) card.classList.remove('is-filled');
                var nameEl = card && card.querySelector('.file-name');
                if (nameEl) { nameEl.classList.add('hidden'); nameEl.textContent = ''; }
            });
        });
    }

    /* ---------- Resumo (etapa final) ---------- */
    function initSummary(form) {
        function update() {
            var v = function (name) { var el = form.elements[name]; return el ? el.value || '' : ''; };
            var name = v('full_name');
            var cpf = v('cpf');
            var phone = v('phone');
            var email = v('email');
            var city = v('city');
            var state = v('state');
            document.getElementById('summary-name').textContent = name;
            document.getElementById('summary-cpf').textContent = cpf ? 'CPF: ' + cpf : '';
            document.getElementById('summary-phone').textContent = phone ? 'Telefone: ' + phone : '';
            document.getElementById('summary-email').textContent = email || '';
            document.getElementById('summary-city').textContent = city ? 'Endereço: ' + city + (state ? ' - ' + state : '') : '';
        }
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        update();
    }

    /* ---------- CEP (mock) ---------- */
    function initCepMock() {
        var cepInput = document.getElementById('cep');
        var btn = document.getElementById('cep-search');
        var feedback = document.getElementById('cep-feedback');
        if (!cepInput || !btn || !feedback) return;
        var spinner = btn.querySelector('[data-cep-spinner]');
        var label = btn.querySelector('[data-cep-label]');
        var state = document.getElementById('state');

        function setFeedback(mode) {
            var messages = {
                idle: '',
                looking: 'Buscando...',
                success: 'Endereço preenchido.',
                error: 'CEP não encontrado. Verifique e tente novamente.',
                incomplete: 'Informe um CEP válido.'
            };
            if (mode === 'idle' || !messages[mode]) {
                feedback.hidden = true;
                feedback.textContent = '';
                return;
            }
            feedback.hidden = false;
            feedback.style.color = mode === 'success'
                ? 'var(--emerald-600)'
                : mode === 'error' || mode === 'incomplete'
                    ? 'var(--red-600)'
                    : 'var(--slate-500)';
            feedback.textContent = messages[mode];
        }

        function lookup() {
            if (cepInput.value.replace(/\D/g, '').length !== 8) { setFeedback('incomplete'); return; }
            setFeedback('looking');
            btn.disabled = true;
            if (spinner) spinner.classList.remove('hidden');
            if (label) label.classList.add('hidden');
            setTimeout(function () {
                var fields = { address: 'Av. Paulista', neighborhood: 'Bela Vista', city: 'São Paulo' };
                Object.keys(fields).forEach(function (name) {
                    var el = document.getElementById(name);
                    if (el) el.value = fields[name];
                });
                if (state) {
                    state.value = 'SP';
                    state.dispatchEvent(new Event('change', { bubbles: true }));
                }
                setFeedback('success');
                btn.disabled = false;
                if (spinner) spinner.classList.add('hidden');
                if (label) label.classList.remove('hidden');
            }, 500);
        }

        btn.addEventListener('click', lookup);
        cepInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); lookup(); }
        });
        var timer = null;
        cepInput.addEventListener('input', function () {
            clearTimeout(timer);
            if (cepInput.value.replace(/\D/g, '').length === 8) timer = setTimeout(lookup, 350);
        });
    }

    /* ---------- Nav admin (drawer mobile) ---------- */
    function initAdminNav() {
        var drawer = document.getElementById('admin-nav-drawer');
        if (!drawer) return;
        var backdrop = document.getElementById('admin-nav-backdrop');
        var openBtn = document.getElementById('admin-nav-open');
        var closeBtn = document.getElementById('admin-nav-close');

        function setNav(open) {
            drawer.classList.toggle('is-open', open);
            drawer.setAttribute('aria-hidden', String(!open));
            if (backdrop) backdrop.hidden = !open;
            document.body.style.overflow = open ? 'hidden' : '';
        }

        openBtn && openBtn.addEventListener('click', function () { setNav(true); });
        closeBtn && closeBtn.addEventListener('click', function () { setNav(false); });
        backdrop && backdrop.addEventListener('click', function () { setNav(false); });
        Array.prototype.forEach.call(drawer.querySelectorAll('[data-mobile-nav-link]'), function (link) {
            link.addEventListener('click', function () { setNav(false); });
        });

        setNav(false);
    }

    /* ---------- Login (toggle senha) ---------- */
    function initLogin() {
        var toggle = document.getElementById('password-toggle');
        var password = document.getElementById('password');
        if (toggle && password) {
            toggle.addEventListener('click', function () {
                var isText = password.type === 'text';
                password.type = isText ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(!isText));
                var eye = toggle.querySelector('[data-icon-eye]');
                var eyeOff = toggle.querySelector('[data-icon-eye-off]');
                if (eye) eye.classList.toggle('hidden', !isText);
                if (eyeOff) eyeOff.classList.toggle('hidden', isText);
            });
        }
    }

    /* ---------- Init ---------- */
    initCustomSelects();
    initRegistration();
    initLogin();
    initAdminNav();
})();